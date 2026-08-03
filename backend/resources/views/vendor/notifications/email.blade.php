<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reset Your Password</title>
</head>
<body>
    <div>
        <h2>Reset Your Password</h2>
        <p>We received a request to reset the password for your account.</p>
        <p>Click the button below to reset your password:</p>
        <div>
            <a href="{{ $resetUrl }}" style="display:inline-block; padding:10px 20px; background:#ff6b35; color:#fff; text-decoration:none; border-radius:4px;">
                Reset Password
            </a>
        </div>
        <p>If you did not request a reset, you can safely ignore this email.</p>
        <hr>
        <p>© {{ date('Y') }} Metronet ISP. All rights reserved.</p>
    </div>
</body>
</html>
