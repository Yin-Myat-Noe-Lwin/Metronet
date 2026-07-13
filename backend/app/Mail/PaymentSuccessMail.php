<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public $payment;
    public $customerName;
    public $amount;
    public $transactionRef;
    public $paidAt;
    public $companyName;
    public $companyAddress;
    public $companyPhone;
    public $companyEmail;

    public function __construct(Payment $payment)
    {
        $this->payment = $payment;

        // Get customer name
        $customer = \App\Models\Customer::find($payment->customer_id);
        $this->customerName = $customer ? $customer->name : 'Customer';

        // Format data
        $this->amount = number_format($payment->amount, 2);
        $this->transactionRef = $payment->transaction_ref ?? 'N/A';
        $this->paidAt = $payment->paid_at ? date('F d, Y H:i:s', strtotime($payment->paid_at)) : 'N/A';

        // Company details
        $this->companyName = config('app.name', 'MetroNet');
        $this->companyAddress = 'Yangon, Myanmar';
        $this->companyPhone = '+95 9 123 456 789';
        $this->companyEmail = 'info@metronet.com';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Successful - ' . $this->companyName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-success',
            with: [
                'payment' => $this->payment,
                'customerName' => $this->customerName,
                'amount' => $this->amount,
                'transactionRef' => $this->transactionRef,
                'paidAt' => $this->paidAt,
                'companyName' => $this->companyName,
                'companyAddress' => $this->companyAddress,
                'companyPhone' => $this->companyPhone,
                'companyEmail' => $this->companyEmail,
            ]
        );
    }
}
