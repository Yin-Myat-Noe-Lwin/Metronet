<?php

namespace App\Mail;

use App\Models\IspPlan;
use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlanUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $plan;
    public $customer;
    public $oldPrice;
    public $newPrice;
    public $oldName;
    public $newName;
    public $oldDownloadSpeed;
    public $newDownloadSpeed;
    public $oldUploadSpeed;
    public $newUploadSpeed;
    public $customerName;
    public $companyName;
    public $subscriptionEndDate;
    public $hasChanges;

    public function __construct(IspPlan $plan, Customer $customer, $data, $subscriptionEndDate = null)
    {
        $this->plan = $plan;
        $this->customer = $customer;
        $this->customerName = $customer->name;
        $this->oldPrice = $data['old_price'] ?? null;
        $this->newPrice = $data['new_price'] ?? null;
        $this->oldName = $data['old_name'] ?? null;
        $this->newName = $data['new_name'] ?? null;
        $this->oldDownloadSpeed = $data['old_download_speed'] ?? null;
        $this->newDownloadSpeed = $data['new_download_speed'] ?? null;
        $this->oldUploadSpeed = $data['old_upload_speed'] ?? null;
        $this->newUploadSpeed = $data['new_upload_speed'] ?? null;
        $this->companyName = config('app.name', 'MetroNet');
        $this->subscriptionEndDate = $subscriptionEndDate
            ? date('F d, Y', strtotime($subscriptionEndDate))
            : 'N/A';

        // Check if any changes exist
        $this->hasChanges = (
            ($this->oldPrice && $this->newPrice && $this->oldPrice != $this->newPrice) ||
            ($this->oldName && $this->newName && $this->oldName != $this->newName) ||
            ($this->oldDownloadSpeed && $this->newDownloadSpeed && $this->oldDownloadSpeed != $this->newDownloadSpeed) ||
            ($this->oldUploadSpeed && $this->newUploadSpeed && $this->oldUploadSpeed != $this->newUploadSpeed)
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '📋 Plan Updated - ' . $this->companyName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.plan-updated',
            with: [
                'plan' => $this->plan,
                'customer' => $this->customer,
                'customerName' => $this->customerName,
                'oldPrice' => $this->oldPrice,
                'newPrice' => $this->newPrice,
                'oldName' => $this->oldName,
                'newName' => $this->newName,
                'oldDownloadSpeed' => $this->oldDownloadSpeed,
                'newDownloadSpeed' => $this->newDownloadSpeed,
                'oldUploadSpeed' => $this->oldUploadSpeed,
                'newUploadSpeed' => $this->newUploadSpeed,
                'companyName' => $this->companyName,
                'subscriptionEndDate' => $this->subscriptionEndDate,
                'hasChanges' => $this->hasChanges,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
