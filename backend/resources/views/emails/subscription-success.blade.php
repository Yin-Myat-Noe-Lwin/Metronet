<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Subscription Successful</title>
</head>
<body>

    <div>
        <h2>Subscription Activated Successfully</h2>

        <p>
            Dear Customer,
        </p>

        <p>
            We are happy to inform you that your subscription has been successfully activated.
            You can now enjoy uninterrupted internet service with us.
        </p>

        <hr>

        <h3>Subscription Details</h3>

        <p><strong>Subscription ID:</strong> {{ $subscription->id }}</p>
        <p><strong>Plan:</strong> {{ $subscription->plan->name ?? 'N/A' }}</p>
        <p><strong>Start Date:</strong> {{ $subscription->start_date }}</p>
        <p><strong>End Date:</strong> {{ $subscription->end_date }}</p>
        <p><strong>Status:</strong> Active</p>

        <hr>

        <h3>Service Information</h3>

        <p>
            Your installation process has been completed
            Your CPE device has been assigned
            Your service is now active
        </p>

        <hr>

        <p>
            Thank you for choosing <strong>Metronet ISP</strong>.
            We are committed to providing you with a stable and fast internet experience.
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
