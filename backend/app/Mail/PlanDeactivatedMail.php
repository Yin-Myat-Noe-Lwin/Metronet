<?php

namespace App\Mail;

use App\Models\IspPlan;
use App\Models\Customer;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlanDeactivatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $plan;
    public $customer;
    public $subscription;
    public $customerName;
    public $isPending;
    public $isActive;
    public $endDate;
    public $companyName;
    public $companyAddress;
    public $companyPhone;
    public $companyEmail;

    public function __construct(IspPlan $plan, Customer $customer, $subscription = null, $isPending = false, $isActive = false)
    {
        $this->plan = $plan;
        $this->customer = $customer;
        $this->customerName = $customer->name;
        $this->subscription = $subscription;
        $this->isPending = $isPending;
        $this->isActive = $isActive;
        $this->endDate = $subscription?->end_date ? date('F d, Y', strtotime($subscription->end_date)) : 'N/A';
        $this->companyName = config('app.name', 'MetroNet');
        $this->companyAddress = 'Yangon, Myanmar';
        $this->companyPhone = '+95 9 123 456 789';
        $this->companyEmail = 'info@metronet.com';
    }

    public function envelope(): Envelope
    {
        $subject = $this->isPending
            ? 'Subscription Cancelled - Plan Discontinued'
            : 'Plan Discontinued - Action Required';

        return new Envelope(
            subject: $subject . ' - ' . $this->companyName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.plan-deactivated',
            with: [
                'plan' => $this->plan,
                'customer' => $this->customer,
                'customerName' => $this->customerName,
                'subscription' => $this->subscription,
                'isPending' => $this->isPending,
                'isActive' => $this->isActive,
                'endDate' => $this->endDate,
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
