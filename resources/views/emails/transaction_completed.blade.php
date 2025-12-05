<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Transfer Completed</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f7f7f9; padding: 24px;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
        <tr>
            <td style="padding: 24px 24px 8px 24px;">
                <h2 style="margin: 0 0 8px 0; color: #111827;">Your transfer is completed</h2>
                <p style="margin: 0; color: #374151;">Reference: <strong>{{ $transfer->reference }}</strong></p>
            </td>
        </tr>
        <tr>
            <td style="padding: 16px 24px;">
                <p style="margin: 0; color: #111827; font-weight: 600;">Summary</p>
                <ul style="padding-left: 18px; color: #374151; margin: 8px 0 0 0;">
                    <li>Amount sent: {{ number_format($transfer->amount, 2) }} {{ $transfer->currency_from }}</li>
                    <li>Amount received: {{ number_format($transfer->amount_received, 2) }} {{ $transfer->currency_to }}</li>
                    @if($beneficiary)
                        <li>Beneficiary: {{ $beneficiary->full_name }}</li>
                    @endif
                    @if($sender = $transfer->sender)
                        <li>Sender: {{ $sender->name }}</li>
                    @endif
                    <li>Completed at: {{ optional($transfer->completed_at ?? $transfer->updated_at)->format('Y-m-d H:i') }}</li>
                </ul>
            </td>
        </tr>
        <tr>
            <td style="padding: 16px 24px 24px 24px; color: #6b7280; font-size: 14px;">
                Thank you for using our service. If you have any questions about this transfer, please reply to this email.
            </td>
        </tr>
    </table>
</body>
</html>
