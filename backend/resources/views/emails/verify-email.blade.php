<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Email Verification</title>
</head>
<body>

    <div>
        <h2>Verify Your Email Address</h2>
        <p>
            Thank you for registering with us. Please verify your email address to activate your account.
        </p>
        <p>
            Click the button below to verify your email:
        </p>
        <div>
            <a href="{{ $verificationUrl }}">
                Verify Email
            </a>
        </div>
        <p>
            If you did not create this account, you can safely ignore this email.
        </p>
        <hr>
        <p>
            © {{ date('Y') }} Metronet ISP. All rights reserved.
        </p>
    </div>
</body>
</html>
