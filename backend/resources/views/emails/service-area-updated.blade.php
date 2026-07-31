<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Area Update - {{ $companyName }}</title>
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
        }
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 2px solid #f1f5f9;
            margin-bottom: 24px;
        }
        .header h1 {
            color: #ff6b35;
            font-size: 28px;
            margin: 0;
        }
        .header .subtitle {
            color: #94a3b8;
            font-size: 14px;
            margin: 4px 0 0;
        }
        .message {
            text-align: center;
            margin: 20px 0;
        }
        .message h2 {
            color: #0f172a;
            margin: 0 0 8px;
        }
        .message p {
            color: #64748b;
            margin: 5px 0;
        }
        .alert-box {
            background: #fef3c7;
            border: 1px solid #fcd34d;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .alert-box .icon {
            font-size: 48px;
            text-align: center;
            display: block;
            margin-bottom: 8px;
        }
        .alert-box h3 {
            color: #92400e;
            margin: 0 0 8px;
            text-align: center;
        }
        .alert-box p {
            color: #92400e;
            margin: 8px 0;
        }
        .info-box {
            background: #f8fafc;
            border-radius: 8px;
            padding: 16px 20px;
            margin: 16px 0;
        }
        .info-box h4 {
            color: #0f172a;
            margin: 0 0 12px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: #94a3b8;
            font-size: 14px;
        }
        .detail-value {
            font-weight: 600;
            color: #0f172a;
        }
        .detail-value.old {
            text-decoration: line-through;
            color: #94a3b8;
        }
        .detail-value.changed {
            color: #ff6b35;
        }
        .detail-value.inactive {
            color: #dc2626;
        }
        .message-box {
            background: #fff;
            padding: 16px;
            margin: 16px 0;
            border-left: 4px solid #ff6b35;
        }
        .message-box p {
            margin: 4px 0;
            color: #475569;
            line-height: 1.8;
        }
        .subscription-box {
            background: #ecfdf5;
            border: 1px solid #86efac;
            border-radius: 8px;
            padding: 16px;
            margin: 16px 0;
        }
        .subscription-box h4 {
            color: #166534;
            margin: 0 0 12px;
        }
        .subscription-box .detail-value {
            color: #166534;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #ff6b35;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin-top: 16px;
        }
        .btn:hover {
            background: #e85a2a;
        }
        .support-box {
            background: #eef2ff;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            margin: 16px 0;
        }
        .support-box p {
            margin: 4px 0;
            color: #4f46e5;
        }
        .support-box a {
            color: #ff6b35;
            text-decoration: none;
            font-weight: 600;
        }
        .support-box a:hover {
            text-decoration: underline;
        }
        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 2px solid #f1f5f9;
            margin-top: 24px;
            color: #94a3b8;
            font-size: 12px;
        }
        .footer p {
            margin: 4px 0;
        }
        .changes-list {
            background: #f8fafc;
            border-radius: 8px;
            padding: 12px 20px;
            margin: 12px 0;
        }
        .changes-list ul {
            margin: 4px 0;
            padding-left: 20px;
            color: #475569;
        }
        .changes-list ul li {
            padding: 4px 0;
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

        <!-- Message -->
        <div class="message">
            <h2>Service Area Update</h2>
            <p>Dear {{ $customerName }},</p>
            <p>We are writing to inform you about an important update regarding our service availability in your area.</p>
        </div>

        <!-- Alert Box -->
        <div class="alert-box">
            <span class="icon">⚠️</span>
            <h3>Service Area No Longer Available</h3>
            <p>We regret to inform you that our service in <strong>{{ $areaName }}</strong> is no longer available.</p>
        </div>

        <!-- Affected Area Details -->
        <div class="info-box">
            <h4>📍 Affected Service Area</h4>

            @if($regionChanged)
            <div class="detail-row">
                <span class="detail-label">Region</span>
                <span class="detail-value">
                    <span class="old">{{ $oldRegion }}</span>
                    → <span class="detail-value changed">{{ $newRegion }}</span>
                </span>
            </div>
            @else
            <div class="detail-row">
                <span class="detail-label">Region</span>
                <span class="detail-value">{{ $serviceArea->region }}</span>
            </div>
            @endif

            @if($cityChanged)
            <div class="detail-row">
                <span class="detail-label">City</span>
                <span class="detail-value">
                    <span class="old">{{ $oldCity }}</span>
                    → <span class="detail-value changed">{{ $newCity }}</span>
                </span>
            </div>
            @else
            <div class="detail-row">
                <span class="detail-label">City</span>
                <span class="detail-value">{{ $serviceArea->city }}</span>
            </div>
            @endif

            @if($townshipChanged)
            <div class="detail-row">
                <span class="detail-label">Township</span>
                <span class="detail-value">
                    <span class="old">{{ $oldTownship }}</span>
                    → <span class="detail-value changed">{{ $newTownship }}</span>
                </span>
            </div>
            @else
            <div class="detail-row">
                <span class="detail-label">Township</span>
                <span class="detail-value">{{ $serviceArea->township }}</span>
            </div>
            @endif

            @if($statusChanged)
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value">
                    <span class="old">{{ $oldStatusLabel }}</span>
                    → <span class="detail-value inactive">{{ $newStatusLabel }}</span>
                </span>
            </div>
            @endif
        </div>

        <!-- Subscription Details -->
        @if($subscription)
        <div class="subscription-box">
            <h4>📋 Your Subscription Details</h4>
            <div class="detail-row">
                <span class="detail-label">Subscription ID</span>
                <span class="detail-value">{{ $subscriptionId }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Plan</span>
                <span class="detail-value">{{ $planName }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value" style="color: #dc2626;">Affected</span>
            </div>
            @if($subscription->end_date)
            <div class="detail-row">
                <span class="detail-label">End Date</span>
                <span class="detail-value">{{ date('F d, Y', strtotime($subscription->end_date)) }}</span>
            </div>
            @endif
        </div>
        @endif

        <!-- Changes Summary -->
        @if($hasChanges)
        <div class="changes-list">
            <p style="margin: 0 0 8px; font-weight: 600; color: #0f172a;">Summary of Changes:</p>
            <ul>
                @if($regionChanged)
                <li>Region: <span class="old">{{ $oldRegion }}</span> → <span style="color: #ff6b35; font-weight: 600;">{{ $newRegion }}</span></li>
                @endif
                @if($cityChanged)
                <li>City: <span class="old">{{ $oldCity }}</span> → <span style="color: #ff6b35; font-weight: 600;">{{ $newCity }}</span></li>
                @endif
                @if($townshipChanged)
                <li>Township: <span class="old">{{ $oldTownship }}</span> → <span style="color: #ff6b35; font-weight: 600;">{{ $newTownship }}</span></li>
                @endif
                @if($statusChanged)
                <li>Status: <span class="old">{{ $oldStatusLabel }}</span> → <span style="color: #dc2626; font-weight: 600;">{{ $newStatusLabel }}</span></li>
                @endif
            </ul>
        </div>
        @endif

        <!-- Message -->
        <div class="message-box">
            <p>We understand this may be inconvenient and we apologize for any disruption this may cause.</p>
            <p>Your current subscription will be affected. We are here to help you transition smoothly.</p>
        </div>

        <!-- Next Steps -->
        <div style="background: #f8fafc; border-radius: 8px; padding: 16px; margin: 16px 0; border: 1px solid #e2e8f0;">
            <p style="margin: 0 0 8px; font-weight: 600; color: #0f172a;">📌 Next Steps:</p>
            <ul style="margin: 0; padding-left: 20px; color: #475569;">
                <li>Contact our support team for assistance</li>
                <li>We will help you find alternative plans available in your area</li>
                <li>We will assist with any billing adjustments</li>
                <li>We are here to help you transition smoothly</li>
            </ul>
        </div>

        <!-- Support Contact -->
        <div class="support-box">
            <p><strong>📧 Need Help?</strong></p>
            <p>Email: <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a></p>
            <p style="font-size: 13px; color: #4f46e5;">Our support team is available 24/7 to assist you.</p>
        </div>

        <!-- Action Button -->
        <div style="text-align: center;">
            <a href="{{ url('/subscriptions') }}" class="btn">View My Subscriptions</a>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $companyName }}. All rights reserved.</p>
            <p>If you have any questions, contact our support team.</p>
            <p style="font-size: 11px; color: #cbd5e1; margin-top: 4px;">
                This email was sent to {{ $customer->email }} regarding your subscription.
            </p>
        </div>
    </div>
</body>
</html>
