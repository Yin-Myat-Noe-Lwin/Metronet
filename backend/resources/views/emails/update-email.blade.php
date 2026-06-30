<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Email Change Request</title>
</head>
<body>

    <h2>Confirm Your New Email Address</h2>

    <p>We received a request to change your email address for your Metronet ISP account.</p>

    <p>Please confirm your new email by clicking the link below:</p>

    <p>
        <a href="{{ $verificationUrl }}">
            Confirm Email Change
        </a>
    </p>

    <p>
        ⚠️ If you did not request this change, please ignore this email.
    </p>

    <hr>

    <p>© {{ date('Y') }} Metronet ISP. All rights reserved.</p>

</body>
</html>
