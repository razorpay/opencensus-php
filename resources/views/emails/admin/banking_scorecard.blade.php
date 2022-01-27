<!DOCTYPE html>
<html lang="en-US">
    <head>
        <meta charset="utf-8">
    </head>
    <body>
        <div>
            <p>Yesterday's TPV - @if(is_null($yesterday_tpv) === false){{ $yesterday_tpv }} Cr.@else Not available @endif</p>
            <p>Monthly TPV till EOD yesterday - @if(is_null($month_tpv) === false){{ $month_tpv }} Cr.@else Not available @endif</p>

            <br>

            <p>Yesterday's Payouts Count - @if(is_null($yesterday_tax_count) === false){{ $yesterday_tax_count }}@else Not available @endif</p>
            <p>Monthly Payouts Count - @if(is_null($month_tax_count) === false){{ $month_tax_count }}@else Not available @endif</p>

            <br>
            <p>Yesterday's Fees earned - @if(is_null($yesterday_fees_collected) === false){{ $yesterday_fees_collected }}@else Not available @endif</p>
            <p>Monthly Fees earned - @if(is_null($month_fees_collected) === false){{ $month_fees_collected }}@else Not available @endif</p>

        </div>

        <div>
            <br />
            <p>
                <b>Yesterday's Merchants By Payouts count -</b>
                <br />
            </p>

            @if(is_null($merchant_data['sorted_by_payout_count']) === false)

                <table border="1">

                    <tr>
                        <th> Merchant Id </th>
                        <th> Merchant Name </th>
                        <th> Merchant Website </th>
                        <th> Payouts Count </th>
                        <th> Payout Amount Cr. </th>
                    </tr>

                @foreach ($merchant_data['sorted_by_payout_count'] as $merchant)

                    <tr>

                        <td>{{ $merchant['x_merchant_id'] }}</td>
                        <td>{{ $merchant['x_merchant_display_name'] }}</td>
                        <td>{{ $merchant['x_merchant_website'] }}</td>
                        <td>{{ $merchant['payout_count'] }}</td>
                        <td>{{ $merchant['payout_amount_cr'] }}</td>

                    </tr>

                @endforeach

                </table>

            @else
                <p>Not available</p>

            @endif

            <br />

        </div>

        <div>
            <p>
                <b>Yesterday's Merchants By Payouts amount -</b>
                <br />
            </p>

            @if(is_null($merchant_data['sorted_by_payout_amount']) === false)

                <table border="1">

                    <tr>
                        <th> Merchant Id </th>
                        <th> Merchant Name </th>
                        <th> Merchant Website </th>
                        <th> Payouts Count </th>
                        <th> Payout Amount Cr. </th>
                    </tr>

                    @foreach ($merchant_data['sorted_by_payout_amount'] as $merchant)

                        <tr>

                            <td>{{ $merchant['x_merchant_id'] }}</td>
                            <td>{{ $merchant['x_merchant_display_name'] }}</td>
                            <td>{{ $merchant['x_merchant_website'] }}</td>
                            <td>{{ $merchant['payout_count'] }}</td>
                            <td>{{ $merchant['payout_amount_cr'] }}</td>

                        </tr>

                    @endforeach

                </table>

            @else
                <p>Not available</p>

            @endif

            <br />

        </div>

    </body>
</html>
