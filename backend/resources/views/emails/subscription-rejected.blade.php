<!-- resources/views/emails/subscription-rejected.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Subscription Rejected</title>
    <style>
        /* Your styles here */
    </style>
</head>
<body>
    <div>
        <h2>Subscription Rejected</h2>

        <p>Dear {{ $customerName }},</p>

        <p>We regret to inform you that your subscription request for <strong>{{ $plan->name ?? 'N/A' }}</strong> has been rejected.</p>

        <div style="background: #fef2f2; border-left: 4px solid #dc2626; padding: 15px; margin: 15px 0;">
            <strong>Reason:</strong> {{ $reason }}
        </div>

        <h3>Rejected Subscription Details</h3>
        <p><strong>Subscription ID:</strong> #{{ str_pad($subscription->id, 4, '0', STR_PAD_LEFT) }}</p>
        <p><strong>Plan:</strong> {{ $plan->name ?? 'N/A' }}</p>
        <p><strong>Submitted On:</strong> {{ $submittedDate }}</p>
        <p><strong>Status:</strong> Rejected</p>

        <h3>What Can You Do?</h3>
        <ul>
            <li>Review the rejection reason above</li>
            <li>Contact our support team for assistance</li>
            <li>Submit a new subscription after addressing the issues</li>
        </ul>

        <p>
            Thank you for choosing <strong>{{ $companyName }}</strong>.
            We hope to serve you in the future.
        </p>

        <p>
            If you have any questions, contact us at
            <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>
        </p>

        <hr>
        <p>© {{ date('Y') }} {{ $companyName }}. All rights reserved.</p>
    </div>
</body>
</html>
