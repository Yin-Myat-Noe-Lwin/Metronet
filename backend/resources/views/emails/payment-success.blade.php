<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Payment Successful</title>
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
    </style>
</head>
<body>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>{{ $companyName }}</h1>
            <p class="subtitle">Internet Service Provider</p>
        </div>

        <!-- Your Original Content -->
        <div>
            <h2>Payment Successful</h2>

            <p>
                Dear {{ $customerName }},
            </p>

            <p>
                We are happy to inform you that your payment has been successfully received.
            </p>

            <hr>

            <h3>Payment Details</h3>

            <p><strong>Payment ID:</strong> {{ $payment->id }}</p>
            <p><strong>Transaction Ref:</strong> {{ $transactionRef }}</p>
            <p><strong>Amount:</strong> {{ $amount }} MMK</p>
            <p><strong>Paid At:</strong> {{ $paidAt }}</p>

            <hr>

            <p>
                Your subscription is now active and you can continue using our service without interruption.
            </p>

            <p>
                Thank you for choosing {{ $companyName }}
            </p>

            <hr>

            <p>
                © {{ date('Y') }} {{ $companyName }}. All rights reserved.
            </p>
        </div>
    </div>

</body>
</html>
