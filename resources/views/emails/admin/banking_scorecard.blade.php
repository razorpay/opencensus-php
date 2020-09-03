<!DOCTYPE html>
<html lang="en-US">
    <head>
        <meta charset="utf-8">
    </head>
    <body>
        <div>
            <p>Yesterday's TPV - {{ $yesterday_tpv }} Cr.</p>
            <p>Monthly TPV till EOD yesterday - {{ $month_tpv }} Cr.</p>

            <br>

            <p>Yesterday's Payouts Count - {{ $yesterday_tax_count }}</p>
            <p>Monthly Payouts Count - {{ $month_tax_count }}</p>

            <br>
            <p>Yesterday's Fees earned - {{ $yesterday_fees_collected }}</p>
            <p>Monthly Fees earned - {{ $month_fees_collected }}</p>

        </div>

        <div>
            <br />
            <p>
                <b>Yesterday's Merchants By Payouts count -</b>
                <br />
            </p>

            <table border="1">

                <tr>
                    <th> Merchant Id </th>
                    <th> Merchant Name </th>
                    <th> Merchant Website </th>
                    <th> Payouts Count </th>
                    <th> Payout Amount Cr. </th>
                </tr>

            @foreach ($merchant_data as $merchant)

                <tr>

                    <td>{{ $merchant['x_merchant_id'] }}</td>
                    <td>{{ $merchant['x_merchant_display_name'] }}</td>
                    <td>{{ $merchant['x_merchant_website'] }}</td>
                    <td>{{ $merchant['payout_count'] }}</td>
                    <td>{{ $merchant['payout_amount_cr'] }}</td>

                </tr>

            @endforeach

            </table>

            <br />

        </div>

    </body>
</html>
