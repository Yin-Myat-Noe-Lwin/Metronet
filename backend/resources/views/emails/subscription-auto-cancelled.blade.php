<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Auto-Cancelled - {{ $companyName }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: #f8fafc;
        }
        .container {
            background: #fff;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e0e0e0;
        }
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
            margin-bottom: 24px;
        }
        .header h1 {
            color: #ff6b35;
            font-size: 28px;
            margin: 0;
        }
        .header .subtitle {
            color: #888;
            font-size: 14px;
            margin: 4px 0 0;
        }
        .alert-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .alert-box .icon {
            font-size: 48px;
            display: block;
            margin-bottom: 8px;
        }
        .alert-box h2 {
            color: #dc2626;
            margin: 0 0 8px;
        }
        .alert-box p {
            color: #991b1b;
            margin: 4px 0;
        }
        .details-box {
            background: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .details-box h3 {
            margin-top: 0;
            color: #0f172a;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e8e8e8;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: #888;
        }
        .detail-value {
            font-weight: 600;
            color: #0f172a;
        }
        .detail-value.cancelled {
            color: #dc2626;
        }
        .support-box {
            background: #eef2ff;
            border: 1px solid #c7d2fe;
            border-radius: 8px;
            padding: 16px;
            margin: 20px 0;
            text-align: center;
        }
        .support-box p {
            margin: 4px 0;
            color: #4f46e5;
        }
        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            margin-top: 20px;
            color: #888;
            font-size: 12px;
        }
        .footer p {
            margin: 4px 0;
        }
        .footer .contact {
            font-size: 11px;
            color: #aaa;
        }
        @media (max-width: 480px) {
            .container {
                padding: 20px;
            }
            .detail-row {
                flex-direction: column;
                gap: 2px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>{{ $companyName }}</h1>
            <p class="subtitle">Internet Service Provider</p>
        </div>

        <!-- Alert Box -->
        <div class="alert-box">
            <span class="icon">⚠️</span>
            <h2>Subscription Auto-Cancelled</h2>
            <p>Your subscription has been automatically cancelled.</p>
        </div>

        <!-- Message -->
        <p>Dear {{ $customerName }},</p>

        <p>
            We are writing to inform you that your subscription has been <strong>automatically cancelled</strong>.
        </p>

        <!-- Subscription Details -->
        <div class="details-box">
            <h3>Cancelled Subscription Details</h3>
            <div class="detail-row">
                <span class="detail-label">Subscription ID</span>
                <span class="detail-value">#{{ str_pad($subscription->id, 4, '0', STR_PAD_LEFT) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Plan</span>
                <span class="detail-value">{{ $plan->name ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Price</span>
                <span class="detail-value">{{ number_format($plan->price ?? 0, 2) }} MMK</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Start Date</span>
                <span class="detail-value">{{ $subscription->start_date ? date('F d, Y', strtotime($subscription->start_date)) : 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">End Date</span>
                <span class="detail-value">{{ $subscription->end_date ? date('F d, Y', strtotime($subscription->end_date)) : 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value cancelled">Auto-Cancelled</span>
            </div>
        </div>

        <!-- Support Box -->
        <div class="support-box">
            <p><strong>Need Help?</strong></p>
            <p>If you have any questions about this cancellation, please contact our support team.</p>
            <p style="margin-top: 8px;">
                {{ $companyEmail }} &nbsp;|&nbsp; 📞 {{ $companyPhone }}
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $companyName }}. All rights reserved.</p>
            <p class="contact">
                {{ $companyEmail }} | {{ $companyPhone }} | {{ $companyAddress }}
            </p>
            <p style="font-size: 11px; color: #aaa; margin-top: 4px;">
                This email was sent to {{ $customer->email }} regarding your subscription cancellation.
            </p>
        </div>
    </div>
</body>
</html>
