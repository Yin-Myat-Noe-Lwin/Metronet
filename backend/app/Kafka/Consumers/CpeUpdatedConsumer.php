<?php

namespace App\Kafka\Consumers;

use App\Models\Cpe;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\CpeUpdatedMail;
use App\Services\EmailService;
use App\Services\NotificationService;
use App\Services\SubscriptionService;
use App\Services\CustomerService;
use App\Services\CpeService;
use Throwable;

class CpeUpdatedConsumer
{
    public function __construct(
        private CpeService $cpeService,
        private EmailService $emailService,
        private NotificationService $notificationService,
        private CustomerService $customerService,
        private SubscriptionService $subscriptionService
    ) {}

    public function handle($message)
    {
        try {
            $data = $message->getBody();

            Log::info('CpeUpdatedConsumer received', ['data' => $data]);

            // Get CPE
            $cpe = $this->cpeService->getCpe($data['cpe_id']);

            if (!$cpe) {
                Log::error('CPE not found', ['cpe_id' => $data['cpe_id']]);
                return;
            }

            // Check if we should notify customers
            if (!$this->shouldNotifyCustomers($data)) {
                Log::info('No notification needed for CPE update', [
                    'cpe_id' => $cpe->id,
                    'status_changed' => $data['status_changed'] ?? false,
                    'customer_id' => $data['customer_id'] ?? null
                ]);
                return;
            }

            // Get customers using this CPE
            $customers = $this->getCustomersToNotify($cpe, $data);

            if ($customers->isEmpty()) {
                Log::info('No customers to notify for CPE update', ['cpe_id' => $cpe->id]);
                return;
            }

            // Build message
            $message = $this->cpeService->buildUpdateMessage($cpe, $data);
            $title = $this->cpeService->getNotificationTitle($data);

            // Create notification for each customer
            foreach ($customers as $customer) {
                try {
                    // Get subscription
                    $assignment = $this->cpeService->getSubscriptionByCpeAndCustomer($cpe->id, $customer->id);

                    // Send email
                    $this->emailService->send(
                        $customer,
                        new CpeUpdatedMail(
                            $cpe,
                            $customer,
                            $data,
                            $assignment
                        )
                    );

                    Log::info('CPE update email sent', [
                        'customer_id' => $customer->id,
                        'email' => $customer->email,
                        'cpe_id' => $cpe->id
                    ]);

                    // Create in-app notification
                    $this->notificationService->create([
                        'customer_id' => $customer->id,
                        'event_type' => 8, // cpe_updated
                        'channel' => 3, // in_app
                        'title' => $title,
                        'message' => $this->cpeService->getCustomerNotificationMessage($cpe, $data),
                    ]);

                    Log::info('CPE update notification created', [
                        'customer_id' => $customer->id,
                        'cpe_id' => $cpe->id
                    ]);

                } catch (Throwable $e) {
                    Log::error('Failed to notify customer: ' . $customer->id . ' - ' . $e->getMessage(), [
                        'cpe_id' => $cpe->id,
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            Log::info('CpeUpdatedConsumer completed', [
                'cpe_id' => $cpe->id,
                'customers_notified' => $customers->count()
            ]);

        } catch (Throwable $e) {
            Log::error('CpeUpdatedConsumer failed: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Determine if we should notify customers
     */
    private function shouldNotifyCustomers(array $data): bool
    {
        // Notify when status changes
        if ($data['status_changed'] ?? false) {
            return true;
        }

        // Notify when serial number or MAC address changes
        if (($data['serial_changed'] ?? false) || ($data['mac_changed'] ?? false)) {
            return true;
        }

        return false;
    }

    /**
     * Get customers to notify
     */
    private function getCustomersToNotify(Cpe $cpe, array $data): \Illuminate\Support\Collection
    {
        // If CPE is assigned to a specific customer, notify only them
        if (isset($data['customer_id']) && !empty($data['customer_id'])) {
            $customer = $this->customerService->getCustomer($data['customer_id']);
            if ($customer) {
                return collect([$customer]);
            }
        }

        // Otherwise, get all customers using this CPE
        return $this->cpeService->getCustomersByCpe($cpe->id);
    }
}
