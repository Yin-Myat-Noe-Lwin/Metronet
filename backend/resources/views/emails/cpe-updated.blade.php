<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Device Updated - {{ $companyName }}</title>
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
        .device-info {
            background: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            margin: 24px 0;
            border: 1px solid #e2e8f0;
        }
        .device-info h3 {
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
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
        }
        .status-badge.available { background: #e9ecef; color: #6c757d; }
        .status-badge.assigned { background: #d4edda; color: #155724; }
        .status-badge.faulty { background: #f8d7da; color: #721c24; }
        .status-badge.maintenance { background: #fff3cd; color: #856404; }
        .status-badge.retired { background: #e2e3e5; color: #383d41; }
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
        .notice-box.info {
            background: #ecfdf5;
            border-color: #86efac;
        }
        .notice-box.info p {
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
        .assignment-info {
            background: #f8fafc;
            border-radius: 8px;
            padding: 16px;
            margin: 16px 0;
            border-left: 4px solid #ff6b35;
        }
        .assignment-info p {
            margin: 4px 0;
            font-size: 14px;
        }
        .assignment-info .label {
            color: #94a3b8;
            font-weight: 500;
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
            <h2>📡 Device Information Updated</h2>
            <p>Dear {{ $customerName }},</p>
            <p>We want to inform you about important changes to your device information.</p>
        </div>

        <!-- Device Information -->
        <div class="device-info">
            <h3>🔧 Device Details</h3>

            <!-- Device ID -->
            <div class="detail-row">
                <span class="detail-label">Device ID</span>
                <span class="detail-value">#{{ str_pad($cpe->id, 4, '0', STR_PAD_LEFT) }}</span>
            </div>

            <!-- Serial Number -->
            @if($serialChanged)
            <div class="detail-row">
                <span class="detail-label">Serial Number</span>
                <span class="detail-value">
                    <span class="old">{{ $oldSerialNumber }}</span>
                    → <span class="detail-value changed">{{ $newSerialNumber }}</span>
                </span>
            </div>
            @else
            <div class="detail-row">
                <span class="detail-label">Serial Number</span>
                <span class="detail-value">{{ $cpe->serial_number }}</span>
            </div>
            @endif

            <!-- MAC Address -->
            @if($macChanged)
            <div class="detail-row">
                <span class="detail-label">MAC Address</span>
                <span class="detail-value">
                    <span class="old">{{ $oldMacAddress }}</span>
                    → <span class="detail-value changed">{{ $newMacAddress }}</span>
                </span>
            </div>
            @else
            <div class="detail-row">
                <span class="detail-label">MAC Address</span>
                <span class="detail-value">{{ $cpe->mac_address }}</span>
            </div>
            @endif

            <!-- Status -->
            @if($statusChanged)
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value">
                    <span class="old">{{ $oldStatusLabel }}</span>
                    →
                    <span class="status-badge {{ strtolower($newStatusLabel ?? 'unknown') }}">
                        {{ $newStatusLabel }}
                    </span>
                </span>
            </div>
            @else
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value">
                    <span class="status-badge {{ strtolower($newStatusLabel ?? 'unknown') }}">
                        {{ $newStatusLabel ?? 'Unknown' }}
                    </span>
                </span>
            </div>
            @endif
        </div>

        <!-- Assignment Information -->
        @if($isAssigned && $assignment)
        <div class="assignment-info">
            <p><strong>Device Assignment Details</strong></p>
            <p><span class="label">Assigned At:</span> {{ $assignedAt ?? 'N/A' }}</p>
            <p><span class="label">Subscription End Date:</span> {{ $subscriptionEndDate ?? 'N/A' }}</p>
            <p><span class="label">Status:</span>
                <span class="status-badge active">Active</span>
            </p>
        </div>
        @endif

        <!-- Important Notice: Status Changed to Assigned -->
        @if($statusChanged && $newStatus == 1)
        <div class="notice-box">
            <p><strong>Device Successfully Assigned</strong></p>
            <p>
                Your device has been assigned to your subscription.
                The device is now <strong class="highlight">active</strong> and ready for use.
            </p>
            <p style="font-size: 13px; color: #92400e; margin-top: 8px;">
                If you experience any issues, please contact our support team.
            </p>
        </div>
        @endif

        <!-- Important Notice: Status Changed to Faulty -->
        @if($statusChanged && $newStatus == 2)
        <div class="notice-box">
            <p><strong>Device Marked as Faulty</strong></p>
            <p>
                Your device has been marked as <strong class="highlight">faulty</strong>.
                Our technical team will contact you shortly to arrange a replacement.
            </p>
            <p style="font-size: 13px; color: #92400e; margin-top: 8px;">
                Please contact support if you haven't heard from us within 24 hours.
            </p>
        </div>
        @endif

        <!-- Important Notice: Status Changed to Maintenance -->
        @if($statusChanged && $newStatus == 3)
        <div class="notice-box" style="background: #ecfdf5; border-color: #86efac;">
            <p><strong>Device Under Maintenance</strong></p>
            <p>
                Your device is currently under <strong>maintenance</strong>.
                We'll notify you once maintenance is complete.
            </p>
            <p style="font-size: 13px; color: #166534; margin-top: 4px;">
                Estimated completion time: 24-48 hours.
            </p>
        </div>
        @endif

        <!-- Important Notice: Status Changed to Retired -->
        @if($statusChanged && $newStatus == 4)
        <div class="notice-box" style="background: #fef2f2; border-color: #fca5a5;">
            <p><strong>Device Retired</strong></p>
            <p>
                Your device has been <strong class="highlight">retired</strong> from our system.
                A new device will be assigned to your subscription.
            </p>
            <p style="font-size: 13px; color: #991b1b; margin-top: 8px;">
                Please contact support for device replacement.
            </p>
        </div>
        @endif

        <!-- Notice: Serial Number Changed -->
        @if($serialChanged)
        <div class="notice-box info">
            <p><strong>Serial Number Updated</strong></p>
            <p>
                Your device serial number has been updated from
                <strong>{{ $oldSerialNumber }}</strong> to <strong>{{ $newSerialNumber }}</strong>.
            </p>
            <p style="font-size: 13px; color: #166534; margin-top: 4px;">
                Please update your records with the new serial number.
            </p>
        </div>
        @endif

        <!-- Notice: MAC Address Changed -->
        @if($macChanged)
        <div class="notice-box info">
            <p><strong>MAC Address Updated</strong></p>
            <p>
                Your device MAC address has been updated from
                <strong>{{ $oldMacAddress }}</strong> to <strong>{{ $newMacAddress }}</strong>.
            </p>
            <p style="font-size: 13px; color: #166534; margin-top: 4px;">
                Please update your records with the new MAC address.
            </p>
        </div>
        @endif

        <!-- Summary of All Changes -->
        @if($hasChanges)
        <div style="background: #f8fafc; border-radius: 8px; padding: 16px; margin: 16px 0; border: 1px solid #e2e8f0;">
            <p style="margin: 0 0 8px; font-weight: 600; color: #0f172a;">📝 Summary of Changes:</p>
            <ul style="margin: 0; padding-left: 20px; color: #475569;">
                @if($serialChanged)
                <li>Serial Number: <span class="old">{{ $oldSerialNumber }}</span> → <span style="color: #ff6b35; font-weight: 600;">{{ $newSerialNumber }}</span></li>
                @endif
                @if($macChanged)
                <li>MAC Address: <span class="old">{{ $oldMacAddress }}</span> → <span style="color: #ff6b35; font-weight: 600;">{{ $newMacAddress }}</span></li>
                @endif
                @if($statusChanged)
                <li>Status: <span class="old">{{ $oldStatusLabel }}</span> → <span style="color: #ff6b35; font-weight: 600;">{{ $newStatusLabel }}</span></li>
                @endif
            </ul>
        </div>
        @endif

        <!-- Action Button -->
        <div style="text-align: center;">
            <a href="{{ url('/profile') }}" class="btn">View My Devices</a>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $companyName }}. All rights reserved.</p>
            <p>If you have any questions, contact our support team.</p>
        </div>
    </div>
</body>
</html>
