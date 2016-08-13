<!DOCTYPE html>
<html lang="en-US">
    <head>
        <meta charset="utf-8">
    </head>
    <body>
        <h2>Razorpay - New Payment</h2>

        <table>
            <tr>
                <td>Id</td>
                <td>{{{$input['id']}}}</td>
            </tr>
            <tr>
                <td>Amount</td>
                <td>{{{$input['amount']}}}</td>
            </tr>
            <tr>
                <td>Method</td>
                <td>{{{$input['method']}}}</td>
            </tr>
            <tr>
                <td>Created At</td>
                <td>{{{ Carbon\Carbon::createFromTimeStamp($input['created_at'], 'Asia/Kolkata')->toRfc850String()}}}</td>
            </tr>
        </table>

        Please check Razorpay dashboard for more details of the payment.
    </body>
</html>
