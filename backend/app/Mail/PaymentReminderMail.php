<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $invoice;
    public $subscription;
    public $customer;
    public $customerName;
    public $daysLeft;
    public $companyName;

    public function __construct(Invoice $invoice, Subscription $subscription, Customer $customer, int $daysLeft)
    {
        $this->invoice = $invoice;
        $this->subscription = $subscription;
        $this->customer = $customer;
        $this->customerName = $customer->name;
        $this->daysLeft = $daysLeft;
        $this->companyName = config('app.name', 'MetroNet');
    }

    public function envelope(): Envelope
    {
        $urgency = $this->daysLeft <= 1 ? 'URGENT: ' : '';
        return new Envelope(
            subject: $urgency . 'Payment Reminder - ' . $this->companyName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-reminder',
            with: [
                'invoice' => $this->invoice,
                'subscription' => $this->subscription,
                'customer' => $this->customer,
                'customerName' => $this->customerName,
                'daysLeft' => $this->daysLeft,
                'companyName' => $this->companyName,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
