<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; padding: 40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); padding: 40px;">
                    <!-- Logo -->
                    <tr>
                        <td align="center" style="padding-bottom: 20px;">
                            <h1 style="color: #1a1a2e; font-size: 24px; margin: 0;">MetroNet</h1>
                        </td>
                    </tr>

                    <!-- Heading -->
                    <tr>
                        <td style="padding-bottom: 16px;">
                            <h2 style="color: #0f172a; font-size: 20px; margin: 0; text-align: center;">
                                Reset Your Password
                            </h2>
                        </td>
                    </tr>

                    <!-- Message -->
                    <tr>
                        <td style="padding-bottom: 10px;">
                            <p style="color: #475569; font-size: 15px; line-height: 1.6; margin: 0;">
                                We received a request to reset the password for your account.
                            </p>
                            <p style="color: #475569; font-size: 15px; line-height: 1.6; margin: 10px 0 0;">
                                Click the button below to reset your password:
                            </p>
                        </td>
                    </tr>

                    <!-- Button -->
                    <tr>
                        <td align="center" style="padding: 25px 0;">
                            <a href="{{ $actionUrl ?? $url ?? '#' }}"
                               style="display: inline-block; padding: 12px 35px; background: #ff6b35; color: #ffffff;
                                      text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 15px;
                                      transition: background 0.2s;">
                                Reset Password
                            </a>
                        </td>
                    </tr>

                    <!-- Note -->
                    <tr>
                        <td style="padding-bottom: 10px;">
                            <p style="color: #94a3b8; font-size: 13px; line-height: 1.5; margin: 0; text-align: center;">
                                If you did not request a reset, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="padding: 15px 0 5px;">
                            <hr style="border: none; border-top: 1px solid #e2e8f0;">
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center">
                            <p style="color: #94a3b8; font-size: 12px; margin: 0;">
                                © {{ date('Y') }} Metronet ISP. All rights reserved.
                            </p>
                            <p style="color: #94a3b8; font-size: 11px; margin: 5px 0 0;">
                                This is an automated message, please do not reply.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
