<?php

namespace App\Mail;

use App\Models\Customer;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subscription;
    public $customer;
    public $plan;
    public $customerName;
    public $companyName;
    public $companyAddress;
    public $companyPhone;
    public $companyEmail;

    public function __construct(Subscription $subscription, Customer $customer)
    {
        $this->subscription = $subscription;
        $this->customer = $customer;
        $this->customerName = $customer->name;
        $this->plan = $subscription->plan; // ✅ Load the plan relationship

        // Company details
        $this->companyName = config('app.name', 'MetroNet');
        $this->companyAddress = 'Yangon, Myanmar';
        $this->companyPhone = '+95 9 123 456 789';
        $this->companyEmail = 'info@metronet.com';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🎉 Your Internet Service is Now Active! - ' . $this->companyName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription-success',
            with: [
                'subscription' => $this->subscription,
                'customer' => $this->customer,
                'customerName' => $this->customerName,
                'plan' => $this->plan,
                'companyName' => $this->companyName,
                'companyAddress' => $this->companyAddress,
                'companyPhone' => $this->companyPhone,
                'companyEmail' => $this->companyEmail,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
