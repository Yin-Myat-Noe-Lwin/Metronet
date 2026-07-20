<?php

namespace App\Kafka\Consumers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Mail\InvoiceCreatedMail;
use App\Services\EmailService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotificationConsumer
{

    public function __construct(
        private EmailService $emailService,
        private NotificationService $notificationService
    ) {
    }

    public function handle($message): void
    {
        try {
            Log::info('Notification Consumer started');

            $data = $message->getBody();

            Log::info('Kafka message received', [
                'data' => $data
            ]);

            // if there is no invoice id in data
            if (!isset($data['invoice_id'])) {
                Log::error('Missing invoice_id');
                return;
            }

            $invoice = Invoice::find($data['invoice_id']);

            // if no invoice data in db
            if (!$invoice) {
                Log::error('Invoice not found', [
                    'invoice_id' => $data['invoice_id']
                ]);
                return;
            }

            // find customer
            $customer = Customer::find($data['customer_id']);

            // if no customer found
            if (!$customer) {
                Log::error('Customer not found', [
                    'customer_id' => $customer->id
                ]);
                return;
            }

            $this->emailService->send(
                $customer,
                new InvoiceCreatedMail($invoice)
            );

            $this->notificationService->create([
                'customer_id' => $customer->id,
                'event_type' => 1,
                'channel' => 1,
                'title' => 'Invoice Created',
                'message' =>
                    'Your invoice #' .
                    $invoice->invoice_number .
                    ' has been generated for ' .
                    number_format($invoice->amount,2) .
                    ' MMK.'
            ]);

            Log::info('Invoice notification process completed', [
                'invoice_id' => $invoice->id,
                'customer_id' => $customer->id
            ]);
        } catch (Throwable $e) {
            Log::error('Notification Consumer failed', [

                'error' => $e->getMessage(),

                'trace' => $e->getTraceAsString()

            ]);

            throw $e;
        }

    }

}
