<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $invoice;
    public $invoiceNumber;
    public $amount;
    public $dueDate;
    public $status;
    public $customer;
    public $plan;
    public $createdDate;
    public $companyName;
    public $companyAddress;
    public $companyPhone;
    public $companyEmail;

    /**
     * Create a new message instance.
     */
    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;

        // Get related data
        $this->customer = $invoice->subscription?->customer;
        $this->plan = $invoice->subscription?->plan;

        // Invoice details
        $this->invoiceNumber = $invoice->invoice_number ?? 'INV-' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT);
        $this->amount = number_format($invoice->amount, 2);
        $this->dueDate = $invoice->due_date ? date('F d, Y', strtotime($invoice->due_date)) : 'N/A';
        $this->createdDate = $invoice->created_at ? date('F d, Y', strtotime($invoice->created_at)) : 'N/A';
        $this->status = $this->getStatusText($invoice->status);

        // Company details
        $this->companyName = config('app.name', 'MetroNet');
        $this->companyAddress = 'Yangon, Myanmar';
        $this->companyPhone = '+95 9 123 456 789';
        $this->companyEmail = 'info@metronet.com';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Invoice #' . $this->invoiceNumber . ' from ' . $this->companyName,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-created',
            with: [
                'invoice' => $this->invoice,
                'invoice_number' => $this->invoiceNumber,
                'amount' => $this->amount,
                'due_date' => $this->dueDate,
                'created_date' => $this->createdDate,
                'status' => $this->status,
                'customer' => $this->customer,
                'plan' => $this->plan,
                'company_name' => $this->companyName,
                'company_address' => $this->companyAddress,
                'company_phone' => $this->companyPhone,
                'company_email' => $this->companyEmail,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Get status text from status code
     */
    private function getStatusText($status)
    {
        $map = [
            0 => 'Pending',
            1 => 'Paid',
            2 => 'Overdue',
            3 => 'Cancelled'
        ];
        return $map[$status] ?? 'Unknown';
    }
}
