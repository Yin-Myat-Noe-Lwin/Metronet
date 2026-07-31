<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public Invoice $invoice;
    public Customer $customer;
    public int $daysLeft;
    public string $customerName;
    public string $companyName;
    public string $planName;

    public function __construct(Invoice $invoice, Customer $customer, int $daysLeft)
    {
        $this->invoice = $invoice;
        $this->customer = $customer;
        $this->daysLeft = $daysLeft;
        $this->customerName = $customer->name;
        $this->companyName = config('app.name', 'MetroNet');
        $this->planName = $invoice->subscription?->plan?->name ?? 'N/A';
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
                'customer' => $this->customer,
                'customerName' => $this->customerName,
                'daysLeft' => $this->daysLeft,
                'companyName' => $this->companyName,
                'planName' => $this->planName,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
