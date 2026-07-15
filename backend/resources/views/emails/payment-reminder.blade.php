<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Payment Reminder - {{ $companyName }}</title>
</head>
<body>
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background: #fff; border-radius: 12px; padding: 40px; border: 1px solid #e0e0e0;">
            <div style="text-align: center; padding-bottom: 20px; border-bottom: 2px solid #f0f0f0;">
                <h1 style="color: #ff6b35;">{{ $companyName }}</h1>
                <p style="color: #888;">Internet Service Provider</p>
            </div>

            <div style="text-align: center; margin: 20px 0;">
                @if($daysLeft <= 1)
                    <span style="font-size: 48px;">🔴</span>
                    <h2 style="color: #dc2626;">URGENT: Payment Due Tomorrow!</h2>
                @else
                    <span style="font-size: 48px;">⚠️</span>
                    <h2 style="color: #d97706;">Payment Reminder</h2>
                @endif
            </div>

            <p>Dear {{ $customerName }},</p>

            @if($daysLeft <= 1)
                <p style="color: #dc2626; font-weight: bold;">
                    ⚠️ Your payment is due <strong>tomorrow</strong>!
                    Please make payment immediately to avoid service cancellation.
                </p>
            @else
                <p>This is a reminder that your invoice is due in <strong>{{ $daysLeft }} days</strong>.</p>
            @endif

            <div style="background: #f8fafc; border-radius: 8px; padding: 20px; margin: 20px 0;">
                <h3 style="margin-top: 0;">📋 Invoice Details</h3>
                <p><strong>Invoice #:</strong> {{ $invoice->invoice_number }}</p>
                <p><strong>Plan:</strong> {{ $subscription->plan->name ?? 'N/A' }}</p>
                <p><strong>Amount:</strong> {{ number_format($invoice->amount, 2) }} MMK</p>
                <p><strong>Due Date:</strong> {{ $invoice->due_date ? date('F d, Y', strtotime($invoice->due_date)) : 'N/A' }}</p>
                <p><strong>Days Left:</strong> {{ $daysLeft }} day(s)</p>
            </div>

            @if($daysLeft <= 1)
                <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 16px; margin: 20px 0; text-align: center;">
                    <p style="color: #991b1b; margin: 0;">
                        <strong>⚠️ If not paid by tomorrow, your service will be suspended.</strong>
                    </p>
                </div>
            @else
                <div style="background: #fef3c7; border: 1px solid #fcd34d; border-radius: 8px; padding: 16px; margin: 20px 0; text-align: center;">
                    <p style="color: #92400e; margin: 0;">
                        <strong>💡 Please make payment within {{ $daysLeft }} days to avoid service interruption.</strong>
                    </p>
                </div>
            @endif

            <div style="text-align: center; margin-top: 20px;">
                <a href="{{ url('/invoices') }}" style="display: inline-block; padding: 12px 24px; background: #ff6b35; color: #fff; text-decoration: none; border-radius: 6px;">
                    Pay Now
                </a>
            </div>

            <div style="text-align: center; padding-top: 20px; border-top: 1px solid #e0e0e0; margin-top: 20px; color: #888; font-size: 12px;">
                <p>&copy; {{ date('Y') }} {{ $companyName }}. All rights reserved.</p>            </div>
        </div>
    </div>
</body>
</html>
