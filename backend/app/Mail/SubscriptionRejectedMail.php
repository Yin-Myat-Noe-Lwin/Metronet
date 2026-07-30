<?php

namespace App\Mail;

use App\Models\Customer;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subscription;
    public $customer;
    public $plan;
    public $customerName;
    public $reason;
    public $companyName;
    public $companyAddress;
    public $companyPhone;
    public $companyEmail;
    public $supportEmail;

    public function __construct(Subscription $subscription, Customer $customer, string $reason)
    {
        $this->subscription = $subscription;
        $this->customer = $customer;
        $this->customerName = $customer->name;
        $this->plan = $subscription->plan;
        $this->reason = $reason;

        // Company details
        $this->companyName = config('app.name', 'MetroNet');
        $this->companyAddress = 'Yangon, Myanmar';
        $this->companyPhone = '+95 9 123 456 789';
        $this->companyEmail = 'info@metronet.com';
        $this->supportEmail = config('app.support_email', 'support@metronet.com');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Subscription Update - ' . $this->companyName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription-rejected',
            with: [
                'subscription' => $this->subscription,
                'customer' => $this->customer,
                'customerName' => $this->customerName,
                'plan' => $this->plan,
                'reason' => $this->reason,
                'companyName' => $this->companyName,
                'companyAddress' => $this->companyAddress,
                'companyPhone' => $this->companyPhone,
                'companyEmail' => $this->companyEmail,
                'supportEmail' => $this->supportEmail,
                'startDate' => $this->subscription->start_date?->format('F d, Y') ?? 'N/A',
                'endDate' => $this->subscription->end_date?->format('F d, Y') ?? 'N/A',
                'submittedDate' => $this->subscription->created_at?->format('F d, Y') ?? 'N/A',
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
