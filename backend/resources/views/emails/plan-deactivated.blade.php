<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plan Discontinued - {{ $companyName }}</title>
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
        .warning-icon {
            text-align: center;
            font-size: 64px;
            margin: 20px 0;
        }
        .warning-icon span {
            background: #fef2f2;
            display: inline-block;
            padding: 20px;
            border-radius: 50%;
            line-height: 1;
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
            margin: 0;
        }
        .details {
            background: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            margin: 24px 0;
        }
        .details h3 {
            color: #0f172a;
            font-size: 16px;
            margin: 0 0 16px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
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
        .detail-value.cancelled {
            color: #dc2626;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-badge.pending {
            background: #fef3c7;
            color: #d97706;
        }
        .status-badge.active {
            background: #dcfce7;
            color: #16a34a;
        }
        .status-badge.cancelled {
            background: #fee2e2;
            color: #dc2626;
        }
        .notice-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 16px;
            margin: 20px 0;
        }
        .notice-box p {
            color: #991b1b;
            margin: 4px 0;
        }
        .notice-box.warning {
            background: #fef3c7;
            border-color: #fcd34d;
        }
        .notice-box.warning p {
            color: #92400e;
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
        .btn-secondary {
            display: inline-block;
            padding: 12px 24px;
            background: #f1f5f9;
            color: #64748b;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin-top: 16px;
            margin-left: 8px;
        }
        .btn-secondary:hover {
            background: #e2e8f0;
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
        @media (max-width: 480px) {
            .container {
                padding: 20px;
            }
            .detail-row {
                flex-direction: column;
                gap: 2px;
            }
            .btn, .btn-secondary {
                display: block;
                margin: 8px 0;
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

        <!-- Warning Icon -->
        <div class="warning-icon">
            <span>⚠️</span>
        </div>

        <!-- Message -->
        <div class="message">
            @if($isPending)
                <h2>Subscription Cancelled</h2>
                <p>Dear {{ $customerName }},</p>
                <p>Your subscription request for <strong>{{ $plan->name }}</strong> has been cancelled because this plan is no longer available.</p>
            @else
                <h2>Plan Discontinued</h2>
                <p>Dear {{ $customerName }},</p>
                <p>We regret to inform you that your internet plan <strong>{{ $plan->name }}</strong> has been discontinued.</p>
            @endif
        </div>

        <!-- Plan Details -->
        <div class="details">
            <h3>Plan Details</h3>
            <div class="detail-row">
                <span class="detail-label">Plan Name</span>
                <span class="detail-value cancelled">{{ $plan->name }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Price</span>
                <span class="detail-value">{{ number_format($plan->price, 2) }} MMK</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Download Speed</span>
                <span class="detail-value">{{ $plan->download_speed }} Mbps</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Upload Speed</span>
                <span class="detail-value">{{ $plan->upload_speed }} Mbps</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value cancelled">❌ Discontinued</span>
            </div>
        </div>

        <!-- Subscription Status -->
        <div class="details" style="margin-top: 16px;">
            <h3>Your Subscription Status</h3>
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value">
                    @if($isPending)
                        <span class="status-badge pending">Pending (Cancelled)</span>
                    @elseif($isActive)
                        <span class="status-badge active">Active</span>
                    @endif
                </span>
            </div>
            @if($isActive && $subscription)
            <div class="detail-row">
                <span class="detail-label">End Date</span>
                <span class="detail-value">{{ $endDate }}</span>
            </div>
            @endif
        </div>

        <!-- Action Required -->
        @if($isPending)
        <div class="notice-box">
            <p><strong>💡 What Happens Next</strong></p>
            <p>Your subscription request has been cancelled. You can browse other available plans and subscribe to a new one.</p>
        </div>
        @elseif($isActive)
        <div class="notice-box warning">
            <p><strong>⚠️ Action Required</strong></p>
            <p>Your service will continue until <strong>{{ $endDate }}</strong>.</p>
            <p>Please choose a new plan before your current subscription ends to avoid service interruption.</p>
        </div>
        @endif

        <!-- Action Buttons -->
        <div style="text-align: center;">
            <a href="http://localhost:5173/plans" class="btn">Browse Plans</a>
            <a href="http://localhost:5173/subscriptions" class="btn-secondary">My Subscriptions</a>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $companyName }}. All rights reserved.</p>
            <p>If you have any questions, contact our support team.</p>
        </div>
    </div>
</body>
</html>
