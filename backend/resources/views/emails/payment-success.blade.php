<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Payment Successful</title>
</head>
<body>

    <div>
        <h2>Payment Successful</h2>

        <p>
            Dear Customer,
        </p>

        <p>
            We are happy to inform you that your payment has been successfully received.
        </p>

        <hr>

        <h3>Payment Details</h3>

        <p><strong>Payment ID:</strong> {{ $payment->id }}</p>
        <p><strong>Transaction Ref:</strong> {{ $payment->transaction_ref }}</p>
        <p><strong>Amount:</strong> {{ $payment->amount }}</p>
        <p><strong>Paid At:</strong> {{ $payment->paid_at }}</p>

        <hr>

        <p>
            Your subscription is now active and you can continue using our service without interruption.
        </p>

        <p>
            Thank you for choosing Metronet ISP
        </p>

        <hr>

        <p>
            © {{ date('Y') }} Metronet ISP. All rights reserved.
        </p>
    </div>

</body>
</html>
