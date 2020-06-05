<html>
<head>
    <style>
        body {
            margin: 40px 100px;
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
            float: right;
            width: 50%;
            text-align: right;
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

        tbody > tr:first-child {
            border-bottom: 1px solid grey;
        }

        .large-row {
            line-height: 50px;
        }

        tr.space-under > td {
            padding-top: 20px;
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
        <div>Partner Name: {{ $merchant['name'] }}</div>
        <div>{{ $address  }}</div>
        <div>PAN No: {{ $pan  }}</div>
        @isset($gstin)
        <div>GSTIN: {{ $gstin  }}</div>
        @endisset
    </div>

    <div class="invoice__details">
        <div>Invoice No: {{ $invoice['id']  }}</div>
        <div> Invoice Date: {{ $created_at  }}</div>
    </div>
</div>

<div class="invoice__billing-to">
    <div class="text-md">Bill To</div>
    <div>
        <strong>Razorpay Software Private Limited</strong>
    </div>
    <div>
        #22, 1st Floor, SJR Cyber, Laskar
        Hosur Road, Adugodi, Bangalore,
        Karnataka - 560 030.
    </div>
    <div>PAN No: AAGCR4375J</div>
    <div>GSTIN : 29AAGCR4375J1ZU</div>
</div>


<table>
    <thead>
    <tr class="highlighted large-row">
        <th>SAC Code</th>
        <th>Particulars</th>
        <th>&nbsp;</th>
        <th>Amount</th>
    </tr>
    </thead>
    <tbody>
    <tr class="large-row">
        <td>997158</td>
        <td>Partner Commission Charges for the period {{ $start_date }} to {{ $end_date  }}</td>
        <td>&nbsp;</td>
        <td><span>{{ $invoice['line_items'][0]['sub_total_spread'][0]  }}</span>&nbsp;<span>{{ $invoice['line_items'][0]['sub_total_spread'][1]  }}</span><span>.{{ $invoice['line_items'][0]['sub_total_spread'][2]  }}</span></td>
    </tr>
    <tr>
        <td colspan=2>&nbsp;</td>
        <td>Sub Total</td>
        <td><span>{{ $invoice['line_items'][0]['sub_total_spread'][0]  }}</span>&nbsp;<span>{{ $invoice['line_items'][0]['sub_total_spread'][1]  }}</span><span>.{{ $invoice['line_items'][0]['sub_total_spread'][2]  }}</span></td>
    </tr>
    @foreach($invoice['line_items'][0]['taxes'] as $tax)
    <tr class="space-under">
        <td colspan=2>&nbsp;</td>
        <td>{{ $tax['name'] }}</td>
        <td><span>{{ $tax['tax_amount_spread'][0]  }}</span>&nbsp;<span>{{ $tax['tax_amount_spread'][1]  }}</span><span>.{{ $tax['tax_amount_spread'][2]  }}</span></td>
    </tr>
    @endforeach
    <tr class="space-under">
        <td colspan=2>&nbsp;</td>
        <td>Rounding</td>
        <td>0</td>
    </tr>
    <tr class="highlighted large-row">
        <td colspan=2>&nbsp;</td>
        <td>Total</td>
        <td><span>{{ $invoice['line_items'][0]['gross_amount_spread'][0] }}</span>&nbsp;<span>{{ $invoice['line_items'][0]['gross_amount_spread'][1] }}</span><span>.{{ $invoice['line_items'][0]['gross_amount_spread'][2] }}</span></td>
    </tr>
    </tbody>
</table>

<div class="invoice__terms-of-payment text-md">Terms of payment:
    <div class="text-sm">Payment should be made within 30 working days</div>
    <div class="text-sm">For any clarifications on the Invoice, please revert within 15 days of receipt of the Invoice.</div>
</div>

<div class="text-sm">Commission will be settled after deducting the TDS percentage of {{ $tds_percentage }}%</div>

<div class="invoice__footnote text-sm">
    Note: This is an auto generated invoice, no signature required.
</div>
</body>
</html>
