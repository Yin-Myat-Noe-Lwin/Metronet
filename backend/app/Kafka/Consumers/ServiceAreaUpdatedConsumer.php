<?php

namespace App\Kafka\Consumers;

use App\Models\ServiceArea;
use App\Mail\ServiceAreaUpdatedMail;
use App\Services\EmailService;
use App\Services\NotificationService;
use App\Services\ServiceAreaService;
use Illuminate\Support\Facades\Log;
use Throwable;

class ServiceAreaUpdatedConsumer
{
    public function __construct(
        private ServiceAreaService $serviceAreaService,
        private EmailService $emailService,
        private NotificationService $notificationService
    ) {}

    public function handle($message): void
    {
        try {
            $data = $message->getBody();

            Log::info('Service area updated event received', [
                'data' => $data
            ]);

            // Only notify if status changed to inactive (0)
            if (!$this->shouldNotifyCustomers($data)) {
                Log::info('No notification needed for service area update', [
                    'service_area_id' => $data['service_area_id'] ?? null,
                    'status_changed' => $data['status_changed'] ?? false,
                    'new_status' => $data['new_status'] ?? null
                ]);
                return;
            }

            // Get service area using service
            $serviceArea = $this->serviceAreaService->getServiceArea($data['service_area_id']);

            if (!$serviceArea) {
                Log::error('Service area not found', [
                    'service_area_id' => $data['service_area_id']
                ]);
                return;
            }

            Log::info('Service area found', [
                'service_area_id' => $serviceArea->id,
                'region' => $serviceArea->region,
                'city' => $serviceArea->city,
                'township' => $serviceArea->township,
                'status' => $serviceArea->status
            ]);

            // Get customers with active subscriptions in this service area using service
            $customers = $this->serviceAreaService->getCustomersByServiceArea($serviceArea);

            if ($customers->isEmpty()) {
                Log::info('No customers to notify for service area deactivation', [
                    'service_area_id' => $serviceArea->id,
                    'region' => $serviceArea->region,
                    'city' => $serviceArea->city,
                    'township' => $serviceArea->township
                ]);
                return;
            }

            Log::info('Found customers to notify', [
                'service_area_id' => $serviceArea->id,
                'customer_count' => $customers->count()
            ]);

            // Process each customer
            foreach ($customers as $customer) {
                try {
                    // Get customer's subscription in this service area using service
                    $subscription = $this->serviceAreaService->getCustomerSubscription($customer, $serviceArea);

                    Log::info('Customer and subscription found', [
                        'customer_id' => $customer->id,
                        'email' => $customer->email,
                        'subscription_id' => $subscription?->id,
                        'plan_id' => $subscription?->plan_id
                    ]);

                    // Build message using service
                    $message = $this->serviceAreaService->buildDeactivationMessage(
                        $serviceArea,
                        $customer,
                        $subscription,
                        $data
                    );

                    // Send email
                    $this->emailService->send(
                        $customer,
                        new ServiceAreaUpdatedMail(
                            $serviceArea,
                            $customer,
                            $data,
                            $subscription
                        )
                    );

                    Log::info('Service area update email sent', [
                        'customer_id' => $customer->id,
                        'email' => $customer->email,
                        'service_area_id' => $serviceArea->id
                    ]);

                    // Create in-app notification
                    $this->notificationService->create([
                        'customer_id' => $customer->id,
                        'event_type' => 9, // service_area_updated
                        'channel' => 3, // in_app
                        'title' => 'Service Area Unavailable',
                        'message' => $message,
                    ]);

                    Log::info('Service area update notification created', [
                        'customer_id' => $customer->id,
                        'service_area_id' => $serviceArea->id
                    ]);

                } catch (Throwable $e) {
                    Log::error('Failed to notify customer: ' . $customer->id . ' - ' . $e->getMessage(), [
                        'service_area_id' => $serviceArea->id,
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            Log::info('Service area update completed', [
                'service_area_id' => $serviceArea->id,
                'customers_notified' => $customers->count()
            ]);

        } catch (Throwable $e) {
            Log::error('Service area update consumer failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Determine if we should notify customers
     */
    private function shouldNotifyCustomers(array $data): bool
    {
        return ($data['status_changed'] ?? false)
            && ($data['new_status'] ?? null) == 0;
    }
}
