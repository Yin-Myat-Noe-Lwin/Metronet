<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plan Updated - {{ $companyName }}</title>
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
        .detail-value.changed {
            color: #ff6b35;
        }
        .detail-value.old {
            text-decoration: line-through;
            color: #94a3b8;
        }
        .notice-box {
            background: #fef3c7;
            border: 1px solid #fcd34d;
            border-radius: 8px;
            padding: 16px;
            margin: 16px 0;
        }
        .notice-box p {
            margin: 4px 0;
            color: #92400e;
            font-size: 14px;
        }
        .notice-box .highlight {
            font-weight: 700;
            color: #dc2626;
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
            <h2>📋 Plan Updated</h2>
            <p>Dear {{ $customerName }},</p>
            <p>We want to inform you about important changes to your internet plan.</p>
        </div>

        <!-- Plan Details -->
        <div class="details">
            <h3>Plan Details</h3>

            <!-- Plan Name -->
            <div class="detail-row">
                <span class="detail-label">Plan Name</span>
                <span class="detail-value">{{ $plan->name }}</span>
            </div>

            <!-- Price Change -->
            @if($oldPrice && $newPrice && $oldPrice != $newPrice)
            <div class="detail-row">
                <span class="detail-label">Price</span>
                <span class="detail-value">
                    <span class="old">{{ number_format($oldPrice, 2) }} MMK</span>
                    → <span class="detail-value changed">{{ number_format($newPrice, 2) }} MMK</span>
                </span>
            </div>
            @endif

            <!-- Download Speed Change -->
            @if($oldDownloadSpeed && $newDownloadSpeed && $oldDownloadSpeed != $newDownloadSpeed)
            <div class="detail-row">
                <span class="detail-label">Download Speed</span>
                <span class="detail-value">
                    <span class="old">{{ $oldDownloadSpeed }} Mbps</span>
                    → <span class="detail-value changed">{{ $newDownloadSpeed }} Mbps</span>
                </span>
            </div>
            @endif

            <!-- Upload Speed Change -->
            @if($oldUploadSpeed && $newUploadSpeed && $oldUploadSpeed != $newUploadSpeed)
            <div class="detail-row">
                <span class="detail-label">Upload Speed</span>
                <span class="detail-value">
                    <span class="old">{{ $oldUploadSpeed }} Mbps</span>
                    → <span class="detail-value changed">{{ $newUploadSpeed }} Mbps</span>
                </span>
            </div>
            @endif

            <!-- Name Change -->
            @if($oldName && $newName && $oldName != $newName)
            <div class="detail-row">
                <span class="detail-label">Old Name</span>
                <span class="detail-value old">{{ $oldName }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">New Name</span>
                <span class="detail-value changed">{{ $newName }}</span>
            </div>
            @endif
        </div>

        <!-- ⚠️ Important Notice -->
        @if($oldPrice && $newPrice && $oldPrice != $newPrice)
        <div class="notice-box">
            <p><strong>⚠️ Important Information About Your Subscription</strong></p>
            <p>
                Your current subscription will continue at <strong>{{ number_format($oldPrice, 2) }} MMK</strong>
                until your current billing cycle ends.
            </p>
            <p>
                The new price of <strong class="highlight">{{ number_format($newPrice, 2) }} MMK</strong>
                will only apply to <strong>new subscriptions</strong> or when you <strong>renew</strong> after
                your current period ends.
            </p>
            <p style="font-size: 13px; color: #92400e; margin-top: 8px;">
                📌 Your current subscription is <strong>NOT affected</strong> by this price change.
            </p>
        </div>
        @endif

        <!-- Speed Upgrade Notice -->
        @if(($oldDownloadSpeed && $newDownloadSpeed && $oldDownloadSpeed != $newDownloadSpeed) ||
            ($oldUploadSpeed && $newUploadSpeed && $oldUploadSpeed != $newUploadSpeed))
        <div class="notice-box" style="background: #ecfdf5; border-color: #86efac;">
            <p><strong>🚀 Speed Update!</strong></p>
            <p>
                @if($newDownloadSpeed > $oldDownloadSpeed)
                    Download speed has been upgraded from <strong>{{ $oldDownloadSpeed }} Mbps</strong> to <strong>{{ $newDownloadSpeed }} Mbps</strong>!
                @endif
                @if($newUploadSpeed > $oldUploadSpeed)
                    Upload speed has been upgraded from <strong>{{ $oldUploadSpeed }} Mbps</strong> to <strong>{{ $newUploadSpeed }} Mbps</strong>!
                @endif
            </p>
            <p style="font-size: 13px; color: #166534; margin-top: 4px;">
                ✅ These speed changes will apply to your current subscription immediately.
            </p>
        </div>
        @endif

        <!-- Action Button -->
        <div style="text-align: center;">
            <a href="http://localhost:5173/subscriptions" class="btn">View My Subscriptions</a>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $companyName }}. All rights reserved.</p>
            <p>If you have any questions, contact our support team.</p>
        </div>
    </div>
</body>
</html>
