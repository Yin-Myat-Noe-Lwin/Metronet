<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Subscription Cancelled</title>
</head>
<body>

    <div>
        <h2>Subscription Cancelled</h2>

        <p>
            Dear {{ $customerName ?? 'Customer' }},
        </p>

        <p>
            We are writing to confirm that your subscription has been successfully cancelled.
        </p>

        <hr>

        <h3>Cancelled Subscription Details</h3>

        <p><strong>Subscription ID:</strong> {{ $subscription->id }}</p>
        <p><strong>Plan:</strong> {{ $plan->name ?? 'N/A' }}</p>
        <p><strong>Start Date:</strong> {{ date('F d, Y', strtotime($subscription->start_date)) }}</p>
        <p><strong>End Date:</strong> {{ date('F d, Y', strtotime($subscription->end_date)) }}</p>
        <p><strong>Status:</strong> Cancelled</p>

        <hr>

        <h3>What Happens Next?</h3>

        <p>
            Your service will remain active until the end of your current billing period.
            After that, your service will be discontinued.
        </p>

        <hr>

        <p>
            If you cancelled by mistake or wish to reactivate your subscription,
            please contact our support team immediately.
        </p>

        <p>
            Thank you for choosing <strong>Metronet ISP</strong>.
            We hope to serve you again in the future.
        </p>

        <p>
            If you have any questions, feel free to contact our support team.
        </p>

        <hr>

        <p>
            © {{ date('Y') }} Metronet ISP. All rights reserved.
        </p>
    </div>

</body>
</html>
