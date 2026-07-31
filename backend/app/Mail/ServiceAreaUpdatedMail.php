<?php

namespace App\Mail;

use App\Models\ServiceArea;
use App\Models\Customer;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ServiceAreaUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public ServiceArea $serviceArea;
    public Customer $customer;
    public ?Subscription $subscription;
    public array $data;
    public string $customerName;
    public string $companyName;
    public string $areaName;
    public string $oldStatusLabel;
    public string $newStatusLabel;
    public bool $regionChanged;
    public bool $cityChanged;
    public bool $townshipChanged;
    public bool $statusChanged;
    public string $planName;
    public string $supportEmail;
    public string $subscriptionId;

    public function __construct(
        ServiceArea $serviceArea,
        Customer $customer,
        array $data,
        ?Subscription $subscription = null
    ) {
        $this->serviceArea = $serviceArea;
        $this->customer = $customer;
        $this->data = $data;
        $this->subscription = $subscription;
        $this->customerName = $customer->name;
        $this->companyName = config('app.name', 'MetroNet');
        $this->areaName = "{$serviceArea->township}, {$serviceArea->city}, {$serviceArea->region}";
        $this->supportEmail = config('app.support_email', 'support@metronet.com');

        // Status labels
        $statusLabels = [
            0 => 'Inactive',
            1 => 'Active'
        ];

        $this->oldStatusLabel = $statusLabels[$data['old_status'] ?? 0] ?? 'Unknown';
        $this->newStatusLabel = $statusLabels[$data['new_status'] ?? 0] ?? 'Unknown';

        // Change flags
        $this->regionChanged = $data['region_changed'] ?? false;
        $this->cityChanged = $data['city_changed'] ?? false;
        $this->townshipChanged = $data['township_changed'] ?? false;
        $this->statusChanged = $data['status_changed'] ?? false;

        // Subscription details
        $this->planName = $subscription?->plan?->name ?? 'N/A';
        $this->subscriptionId = $subscription?->id ? '#' . str_pad($subscription->id, 4, '0', STR_PAD_LEFT) : 'N/A';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Service Area Update - ' . $this->companyName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.service-area-updated',
            with: [
                'serviceArea' => $this->serviceArea,
                'customer' => $this->customer,
                'customerName' => $this->customerName,
                'companyName' => $this->companyName,
                'areaName' => $this->areaName,
                'data' => $this->data,
                'subscription' => $this->subscription,
                'planName' => $this->planName,
                'subscriptionId' => $this->subscriptionId,
                'oldStatusLabel' => $this->oldStatusLabel,
                'newStatusLabel' => $this->newStatusLabel,
                'regionChanged' => $this->regionChanged,
                'cityChanged' => $this->cityChanged,
                'townshipChanged' => $this->townshipChanged,
                'statusChanged' => $this->statusChanged,
                'supportEmail' => $this->supportEmail,
                'oldRegion' => $data['old_region'] ?? null,
                'newRegion' => $data['new_region'] ?? null,
                'oldCity' => $data['old_city'] ?? null,
                'newCity' => $data['new_city'] ?? null,
                'oldTownship' => $data['old_township'] ?? null,
                'newTownship' => $data['new_township'] ?? null,
                'hasChanges' => $this->regionChanged || $this->cityChanged || $this->townshipChanged || $this->statusChanged,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
