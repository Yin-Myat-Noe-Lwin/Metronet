<?php

namespace App\Mail;

use App\Models\Cpe;
use App\Models\Customer;
use App\Models\CpeAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CpeUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Cpe $cpe;
    public Customer $customer;
    public string $customerName;
    public string $companyName;
    public ?string $oldSerialNumber;
    public ?string $newSerialNumber;
    public ?string $oldMacAddress;
    public ?string $newMacAddress;
    public ?int $oldStatus;
    public ?int $newStatus;
    public ?string $oldStatusLabel;
    public ?string $newStatusLabel;
    public bool $serialChanged;
    public bool $macChanged;
    public bool $statusChanged;
    public bool $hasChanges;
    public ?CpeAssignment $assignment;
    public ?string $assignedAt;
    public ?string $subscriptionEndDate;

    public function __construct(Cpe $cpe, Customer $customer, array $data, ?CpeAssignment $assignment = null)
    {
        $this->cpe = $cpe;
        $this->customer = $customer;
        $this->customerName = $customer->name;
        $this->companyName = config('app.name', 'MetroNet');

        // CPE data
        $this->oldSerialNumber = $data['old_serial_number'] ?? null;
        $this->newSerialNumber = $data['new_serial_number'] ?? null;
        $this->oldMacAddress = $data['old_mac_address'] ?? null;
        $this->newMacAddress = $data['new_mac_address'] ?? null;
        $this->oldStatus = $data['old_status'] ?? null;
        $this->newStatus = $data['new_status'] ?? null;
        $this->oldStatusLabel = $data['old_status_label'] ?? null;
        $this->newStatusLabel = $data['new_status_label'] ?? null;

        // Change flags
        $this->serialChanged = $data['serial_changed'] ?? false;
        $this->macChanged = $data['mac_changed'] ?? false;
        $this->statusChanged = $data['status_changed'] ?? false;

        // Assignment info
        $this->assignment = $assignment;
        $this->assignedAt = $assignment?->assigned_at?->format('F d, Y H:i');
        $this->subscriptionEndDate = $assignment?->subscription?->end_date
            ? date('F d, Y', strtotime($assignment->subscription->end_date))
            : 'N/A';

        // Check if any changes exist
        $this->hasChanges = $this->serialChanged || $this->macChanged || $this->statusChanged;
    }

    public function envelope(): Envelope
    {
        $subject = 'Your Device Information Has Been Updated - ' . $this->companyName;

        // subject based on what changed
        if ($this->statusChanged && $this->newStatusLabel) {
            $subject = 'Device Status Changed to ' . $this->newStatusLabel . ' - ' . $this->companyName;
        } elseif ($this->serialChanged) {
            $subject = 'Device Serial Number Updated - ' . $this->companyName;
        } elseif ($this->macChanged) {
            $subject = 'Device MAC Address Updated - ' . $this->companyName;
        }

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        // Build change summary for email view
        $changes = $this->buildChangesArray();

        return new Content(
            view: 'emails.cpe-updated',
            with: [
                'cpe' => $this->cpe,
                'customer' => $this->customer,
                'customerName' => $this->customerName,
                'companyName' => $this->companyName,
                'oldSerialNumber' => $this->oldSerialNumber,
                'newSerialNumber' => $this->newSerialNumber,
                'oldMacAddress' => $this->oldMacAddress,
                'newMacAddress' => $this->newMacAddress,
                'oldStatus' => $this->oldStatus,
                'newStatus' => $this->newStatus,
                'oldStatusLabel' => $this->oldStatusLabel,
                'newStatusLabel' => $this->newStatusLabel,
                'serialChanged' => $this->serialChanged,
                'macChanged' => $this->macChanged,
                'statusChanged' => $this->statusChanged,
                'hasChanges' => $this->hasChanges,
                'changes' => $changes,
                'assignedAt' => $this->assignedAt,
                'subscriptionEndDate' => $this->subscriptionEndDate,
                'isAssigned' => $this->assignment !== null && $this->newStatus === 1,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

    /**
     * Build an array of changes for the email view
     */
    private function buildChangesArray(): array
    {
        $changes = [];

        if ($this->serialChanged) {
            $changes[] = [
                'field' => 'Serial Number',
                'old' => $this->oldSerialNumber,
                'new' => $this->newSerialNumber,
            ];
        }

        if ($this->macChanged) {
            $changes[] = [
                'field' => 'MAC Address',
                'old' => $this->oldMacAddress,
                'new' => $this->newMacAddress,
            ];
        }

        if ($this->statusChanged) {
            $changes[] = [
                'field' => 'Status',
                'old' => $this->oldStatusLabel,
                'new' => $this->newStatusLabel,
            ];
        }

        return $changes;
    }
}
