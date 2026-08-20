<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\PlanDiscount;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use App\Services\KafkaProducerService;

class CreateInvoiceJob implements ShouldQueue
{
    use Queueable;

    public function handle(KafkaProducerService $kafkaProducer): void
    {
        Log::info("Invoice creation job started");

        // Get active subscriptions with their plans
        $subscriptions = Subscription::where('status', 1) // Active only
                                    ->where('end_date', '>', now()) // Not expired
                                    ->get();

        Log::info('Active subscriptions found', $subscriptions->count());

        foreach ($subscriptions as $subscription) {
            $this->processSubscription($subscription, $kafkaProducer);
        }

        Log::info("Invoice creation job completed");
    }

    /**
     * Process a single subscription
     */
    private function processSubscription($subscription, $kafkaProducer): void
    {
        Log::info('Processing subscription', [
            'subscription_id' => $subscription->id,
            'billing_cycle' => $subscription->billing_cycle,
        ]);

        // Determine the next billing period
        $lastInvoice = $subscription->invoices()
                                    ->orderBy('billing_period_end', 'desc')
                                    ->first();

        if ($lastInvoice) {
            // if there is any last invoice, start from where last invoice ended
            $nextPeriodStart = $lastInvoice->billing_period_end;

            // Check if subscription will end before next cycle
            if ($nextPeriodStart->copy()->addMonths($subscription->billing_cycle) > $subscription->end_date) {
                Log::info('Subscription will end before next billing cycle', [
                    'subscription_id' => $subscription->id,
                    'end_date' => $subscription->end_date,
                    'next_cycle_end' => $nextPeriodStart->copy()->addMonths($subscription->billing_cycle)
                ]);
                return;
            }
        } else {
            // First invoice (start from subscription start date)
            $nextPeriodStart = $subscription->start_date;
        }

        $nextPeriodEnd = $nextPeriodStart->copy()->addMonths($subscription->billing_cycle);

        // Check if invoice already exists for this period
        $existingInvoice = $subscription->invoices()
                                        ->where('billing_period_start', $nextPeriodStart)
                                        ->where('billing_period_end', $nextPeriodEnd)
                                        ->first();

        if ($existingInvoice) {
            Log::info('Invoice already exists for this period', [
                'subscription_id' => $subscription->id,
                'invoice_id' => $existingInvoice->id,
                'period' => $nextPeriodStart->format('Y-m-d') . ' to ' . $nextPeriodEnd->format('Y-m-d')
            ]);
            return;
        }

        // Calculate invoice amount with discount
        $invoiceAmount = $this->calculateInvoiceAmount($subscription);

        // Create invoice
        $invoice = Invoice::create([
            'invoice_number' => $this->generateInvoiceNumber($subscription->id),
            'subscription_id' => $subscription->id,
            'amount' => $invoiceAmount,
            'due_date' => $nextPeriodStart->copy()->addDays(7),
            'billing_period_start' => $nextPeriodStart,
            'billing_period_end' => $nextPeriodEnd,
            'status' => 0, // pending
            'created_at' => now(),
            'updated_at' => now()
        ]);

        Log::info('Invoice created successfully', [
            'invoice_id' => $invoice->id,
            'subscription_id' => $subscription->id,
            'amount' => $invoice->amount,
            'period' => $nextPeriodStart->format('Y-m-d') . ' to ' . $nextPeriodEnd->format('Y-m-d')
        ]);

        // Dispatch Kafka event
        $this->publishInvoiceEvent($invoice, $subscription, $kafkaProducer);
    }

    /**
     * Calculate invoice amount with discount applied
     */
    private function calculateInvoiceAmount($subscription): float
    {
        // original plan price without any discount
        $basePrice = $subscription->plan->price;
        $billingCycle = $subscription->billing_cycle;

        // Get discount for the subscription duration
        $discount = $this->getDiscountForSubscription($subscription);

        // Calculate monthly price after discount
        $monthlyPrice = $basePrice * (1 - ($discount / 100));

        // Calculate invoice amount for the billing cycle
        $invoiceAmount = $monthlyPrice * $billingCycle;

        Log::info('Invoice amount calculated', [
            'subscription_id' => $subscription->id,
            'base_price' => $basePrice,
            'billing_cycle' => $billingCycle,
            'discount' => $discount,
            'monthly_price' => $monthlyPrice,
            'invoice_amount' => $invoiceAmount
        ]);

        return round($invoiceAmount, 2);
    }

    /**
     * Get discount for subscription based on duration
     */
    private function getDiscountForSubscription($subscription): float
    {
        // Get discount from plan_discounts table
        $discount = PlanDiscount::where('plan_id', $subscription->plan_id)
                                ->where('duration_months', $subscription->duration_months)
                                ->where('is_active', 1)
                                ->first();

        // only if the plan has the discount
        if ($discount) {
            return (float) $discount->discount_percentage;
        }

        return 0;
    }

    /**
     * Generate unique invoice number
     */
    private function generateInvoiceNumber(int $subscriptionId): string
    {
        return 'INV-' . date('Ymd') . '-' . str_pad($subscriptionId, 4, '0', STR_PAD_LEFT) . '-' . rand(100, 999);
    }

    /**
     * Publish invoice created event to Kafka
     */
    private function publishInvoiceEvent($invoice, $subscription, $kafkaProducer): void
    {
        try {
            $topic = config('kafka.consumers.invoice_created.topic', 'invoice.created');

            $kafkaProducer->publish(
                $topic,
                [
                    'invoice_id' => $invoice->id,
                    'customer_id' => $subscription->customer_id,
                    'subscription_id' => $subscription->id,
                    'amount' => $invoice->amount,
                    'due_date' => $invoice->due_date,
                    'billing_period_start' => $invoice->billing_period_start,
                    'billing_period_end' => $invoice->billing_period_end,
                    'plan_name' => $subscription->plan->name,
                    'billing_cycle' => $subscription->billing_cycle
                ]
            );

            Log::info('Kafka event published', [
                'invoice_id' => $invoice->id,
                'topic' => $topic
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to publish Kafka event', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
