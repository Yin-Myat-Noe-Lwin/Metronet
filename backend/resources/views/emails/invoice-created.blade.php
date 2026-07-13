<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Invoice Created</title>
</head>

<body>

    <h2>MetroNet ISP</h2>

    <p>Dear {{ $customer->name ?? 'Customer' }},</p>

    <p>Your invoice has been generated successfully.</p>

    <table border="1" cellpadding="8">
        <tr>
            <td><strong>Invoice Number</strong></td>
            <td>{{ $invoice_number }}</td>
        </tr>

        <tr>
            <td><strong>Amount</strong></td>
            <td>MMK {{ $amount }}</td>
        </tr>

        <tr>
            <td><strong>Due Date</strong></td>
            <td>{{ $due_date }}</td>
        </tr>

        <tr>
            <td><strong>Status</strong></td>
            <td>{{ $status }}</td>
        </tr>
    </table>

    <br>

    <p>Please make your payment before the due date.</p>

    <p>Thank you for choosing MetroNet ISP.</p>

</body>

</html>
