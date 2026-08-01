<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Service Area Unavailable - {{ $companyName }}</title>

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
            background: #ffffff;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }

        .header h1 {
            color: #ff6b35;
            margin: 0;
            font-size: 28px;
        }

        .subtitle {
            color: #94a3b8;
            font-size: 14px;
        }

        .message {
            text-align: center;
        }

        .message h2 {
            color: #0f172a;
        }

        .message p {
            color: #64748b;
        }

        .alert-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
            text-align: center;
        }

        .alert-box .icon {
            font-size: 45px;
        }

        .alert-box h3 {
            color: #dc2626;
        }

        .alert-box p {
            color: #991b1b;
        }

        .info-box {
            background: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #94a3b8;
        }

        .detail-value {
            font-weight: 600;
            color: #0f172a;
        }

        .inactive {
            color: #dc2626;
            font-weight: bold;
        }

        .subscription-box {
            background: #ecfdf5;
            border: 1px solid #86efac;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .subscription-box h4 {
            color: #166534;
        }

        .message-box {
            background: white;
            border-left: 4px solid #ff6b35;
            padding: 15px;
            margin: 20px 0;
        }

        .support-box {
            background: #eef2ff;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
        }

        .support-box a {
            color: #ff6b35;
            font-weight: bold;
        }

        .footer {
            text-align: center;
            border-top: 2px solid #f1f5f9;
            margin-top: 25px;
            padding-top: 20px;
            color: #94a3b8;
            font-size: 12px;
        }

        .btn {
            display: inline-block;
            margin-top: 20px;
            background: #ff6b35;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        }
    </style>

</head>


<body>

    <div class="container">


        <!-- Header -->
        <div class="header">

            <h1>{{ $companyName }}</h1>

            <p class="subtitle">
                Internet Service Provider
            </p>

        </div>



        <!-- Message -->
        <div class="message">

            <h2>
                Service Area Unavailable
            </h2>

            <p>
                Dear {{ $customerName }},
            </p>

            <p>
                We would like to inform you that our internet service is no longer available in your area.
            </p>

        </div>



        <!-- Alert -->
        <div class="alert-box">

            <div class="icon">
                ⚠️
            </div>

            <h3>
                Service Deactivated
            </h3>

            <p>
                The following service area has been disabled:
            </p>

            <strong>
                {{ $areaName }}
            </strong>

        </div>



        <!-- Service Area Details -->
        <div class="info-box">

            <h4>
                📍 Service Area Details
            </h4>


            <div class="detail-row">
                <span class="detail-label">
                    Region
                </span>

                <span class="detail-value">
                    {{ $serviceArea->region }}
                </span>
            </div>


            <div class="detail-row">

                <span class="detail-label">
                    City
                </span>

                <span class="detail-value">
                    {{ $serviceArea->city }}
                </span>

            </div>


            <div class="detail-row">

                <span class="detail-label">
                    Township
                </span>

                <span class="detail-value">
                    {{ $serviceArea->township }}
                </span>

            </div>


            <div class="detail-row">

                <span class="detail-label">
                    Previous Status
                </span>

                <span class="detail-value">
                    {{ $oldStatusLabel }}
                </span>

            </div>


            <div class="detail-row">

                <span class="detail-label">
                    Current Status
                </span>

                <span class="inactive">
                    {{ $newStatusLabel }}
                </span>

            </div>


        </div>



        <!-- Subscription -->
        @if ($subscription)
            <div class="subscription-box">

                <h4>
                    📋 Subscription Details
                </h4>


                <div class="detail-row">

                    <span class="detail-label">
                        Subscription ID
                    </span>

                    <span class="detail-value">
                        {{ $subscriptionId }}
                    </span>

                </div>


                <div class="detail-row">

                    <span class="detail-label">
                        Plan
                    </span>

                    <span class="detail-value">
                        {{ $planName }}
                    </span>

                </div>


                <div class="detail-row">

                    <span class="detail-label">
                        Status
                    </span>

                    <span class="inactive">
                        Affected
                    </span>

                </div>


            </div>
        @endif



        <!-- Message -->
        <div class="message-box">

            <p>
                We apologize for the inconvenience caused by this service change.
            </p>

            <p>
                Please contact our support team for assistance with alternative plans or subscription support.
            </p>

        </div>



        <!-- Support -->
        <div class="support-box">

            <p>
                📧 Need Help?
            </p>

            <p>
                Email:
                <a href="mailto:{{ $supportEmail }}">
                    {{ $supportEmail }}
                </a>
            </p>

            <p>
                Our support team is ready to assist you.
            </p>

        </div>



        <!-- Button -->
        <div style="text-align:center">

            <a href="{{ url('/subscriptions') }}" class="btn">
                View My Subscriptions
            </a>

        </div>



        <!-- Footer -->
        <div class="footer">

            <p>
                © {{ date('Y') }} {{ $companyName }}. All rights reserved.
            </p>

            <p>
                This email was sent to {{ $customer->email }} regarding your subscription.
            </p>

        </div>


    </div>

</body>

</html>
