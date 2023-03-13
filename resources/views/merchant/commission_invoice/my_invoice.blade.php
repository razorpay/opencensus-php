<html>
<head>
    <style>
        body {
            margin: 40px 80px;
            font-family: Arial;
        }

        .invoice__title {
            float: right;
            margin-bottom: 15px;
        }

        .invoice__title > div {
            font-size: 50px;
            font-weight: bold;
        }

        .invoice__partner-details {
            width: 30%;
            display: inline-block;
        }

        .invoice__details {
            min-width: 260px;
            float: right;
            text-align: left;
        }

        table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
        }

        th, td {
            text-align: left;
            padding: 5px 0;
        }

        tr th:first-child {
            padding-left: 10px;
        }

        tr td:first-child {
            padding-left: 10px;
        }

        tr th:last-child {
            text-align: right;
            padding-right: 10px;
        }

        tr td:last-child {
            text-align: right;
            padding-right: 10px;
        }

        .large-row {
            line-height: 40px;
        }

        tr.space-under > td {
            padding-top: 15px;
            padding-bottom: 15px;
        }

        .highlighted {
            background-color: #d3d3d3;
        }

        .text-sm {
            font-size: 15px;
            line-height: 1;
        }

        .text-md {
            font-size: 20px;
            line-height: 1.5;
        }

        .invoice__billing-to {
            width: 40%;
            margin: 30px 0;
        }

        .invoice__terms-of-payment {
            margin: 30px 0;
        }

        .invoice__footnote {
            text-align: center;
            margin: 100px 0 30px 0;
        }

        .clear {
            clear: both;
        }
    </style>
</head>
<body>
<div class="invoice__title">
    <div>Tax</div>
    <div>Invoice</div>
</div>

<div class="clear"></div>
<div>
    <div class="invoice__partner-details">
        <strong>Partner Name: {{ $merchant['name'] }}</strong>
    </div>

    <div class="invoice__details">
        <div><strong>Invoice No: {{ $invoice['id']  }}</strong></div>
        <div>Invoice Date: {{ $created_at  }}</div>
    </div>
</div>

<div class="invoice__billing-to">
    <div class="text-md">Bill To</div>
    <div>
        <strong>Curlec Sdn Bhd</strong>
    </div>
    <div>
        No. 57, Level 8, Komune Co-working, Vertical Corporate Tower - B, The Vertical,
        Bangsar South, No. 8 Jalan Kerinchi, Kuala Lumpur, Malaysia - 59200
    </div>
</div>


<table>
    <thead>
    <tr class="highlighted large-row">
        <th>Particulars (for the period {{ $start_date }} to {{ $end_date  }})</th>
        <th>&nbsp;</th>
        <th>Amount</th>
    </tr>
    </thead>
    <tbody>
    @foreach($invoice['line_items'] as $lineItem)
        <tr class="large-row">
            <td>Partner Commission Charges For Payment Gateway

                @if(count($invoice['line_items']) > 1)
                    -
                    @if($lineItem['tax_rate'] > 0)
                        (Taxable)
                    @else
                        (Non-taxable)
                    @endif
                @endif
            </td>
            <td>&nbsp;</td>
            <td><span>{{ $lineItem['sub_total_spread'][0]  }}</span>&nbsp;<span>{{ $lineItem['sub_total_spread'][1]  }}</span><span>.{{ $lineItem['sub_total_spread'][2]  }}</span></td>
        </tr>
        @foreach($lineItem['taxes'] as $tax)
            <tr class="space-under">
                <td colspan=2>&nbsp;</td>
                <td>{{ $tax['name'] }}</td>
                <td><span>{{ $tax['tax_amount_spread'][0]  }}</span>&nbsp;<span>{{ $tax['tax_amount_spread'][1]  }}</span><span>.{{ $tax['tax_amount_spread'][2]  }}</span></td>
            </tr>
        @endforeach
        <tr style="border-bottom:1px solid grey">
            <td colspan="100%"></td>
        </tr>
    @endforeach
    <tr class="highlighted large-row">
        <td>Total</td>
        <td><span>{{ $invoice['gross_amount_spread'][0] }}</span>&nbsp;<span>{{ $invoice['gross_amount_spread'][1] }}</span><span>.{{ $invoice['gross_amount_spread'][2] }}</span></td>
    </tr>
    </tbody>
</table>

<div class="invoice__terms-of-payment text-md">Terms of payment:
    <div class="text-sm">Payment should be made within 14 working days</div>
    <div class="text-sm">For any clarifications on the invoice, please revert within 7 days of the receipt of the invoice.</div>
</div>

<div class="invoice__footnote text-sm">
    <div>Note: This invoice is issued on behalf of {{ $merchant['name'] }}. This is an auto generated invoice, no signature required.</div>
</div>
</body>
</html>
