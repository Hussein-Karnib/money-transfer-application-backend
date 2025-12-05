<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Account Approved</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f7f7f9; padding: 24px;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
        <tr>
            <td style="padding: 24px 24px 8px 24px;">
                <h2 style="margin: 0 0 8px 0; color: #111827;">Hi {{ $user->name }},</h2>
                <p style="margin: 0; color: #374151;">Your account has been approved. You can now log in and start sending transfers.</p>
            </td>
        </tr>
        <tr>
            <td style="padding: 16px 24px;">
                <p style="margin: 0; color: #111827; font-weight: 600;">Details</p>
                <ul style="padding-left: 18px; color: #374151; margin: 8px 0 0 0;">
                    <li>Approved at: {{ $approvedAt->format('Y-m-d H:i') }}</li>
                    <li>Email: {{ $user->email }}</li>
                </ul>
            </td>
        </tr>
        <tr>
            <td style="padding: 16px 24px 24px 24px; color: #6b7280; font-size: 14px;">
                If you did not request this, please contact support immediately.
            </td>
        </tr>
    </table>
</body>
</html>
