<?php

    use Illuminate\Support\Facades\Log;
    use App\Models\Subscription;
    use App\Models\Customer;
    use App\Models\Invoice;
    use App\Models\Notification;
    use Illuminate\Support\Facades\Mail;
    class NotificationConsumer
    {
        public function handle($message)
        {
            $data = $message->getBody();

            $invoice = Invoice::where('id', $data['invoice_id'])->first();

            if (!$invoice) {
                return;
            }

            $subscription = Subscription::where('id', $invoice->subscription_id)->first();

            if (!$subscription) {
                return;
            }

            $customer = Customer::where('id', $subscription->customer_id)->first();

            if (!$customer) {
                return;
            }

            // send mail to customer about invoice
            Mail::to($customer->email)
                ->send(new InvoiceCreatedMail($invoice));

            // create noti
            Notification::create([
                'customer_id' => $customer->id,
                'type' => 1,
                'title' => 'Invoice Created',
                'message' => 'Your invoice has been generated.',
                'status' => 1
            ]);
        }
    }
