<!doctype html>
<html>

<head>

    <meta charset="utf-8">

    <title>RazorpayX - Tax Invoice</title>

    <link href="https://fonts.googleapis.com/css?family=Muli:400,600,700&display=swap" rel="stylesheet">
    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
        }

        body {
            font-family: 'Muli', sans-serif;
            font-size: 17px;
            color: rgba(0, 0, 0, 0.6);
            min-height: 980px;
            -webkit-print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        body * {
            box-sizing: border-box;
            -webkit-box-sizing: border-box;
            -moz-box-sizing: border-box;
            -o-box-sizing: border-box;
        }

        .foot-note {
            font-size: 13px;
            line-height: 20px;
            margin-top: 150px;
            bottom: 30px;
            left: 30px;
            right: 30px;
        }

        .foot-note ol {
            padding-left: 15px;
            font-size: 12px;
            color: rgba(0, 0, 0, 0.6);
            margin: 5px 0 0 0;
        }

        .bank-details {
            padding: 0 5px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            padding-bottom: 16px;
        }

        .bank-details span:first-child {
            width: 110px;
            display: inline-block;
        }

        .invoice-box {
            padding: 30px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, .15);
            line-height: 24px;
            position: relative;
            height: 100%;
        }

        .invoice-box table {
            width: 100%;
            line-height: inherit;
            text-align: left;
            border-collapse: collapse;
        }

        .invoice-box table th {
            background-color: #f2f2f2 !important;
        }

        .invoice-box table td,
        .invoice-box table th {
            padding: 6px 10px;
            vertical-align: top;
        }

        .invoice-box table th {

            vertical-align: middle;
        }

        .invoice-box table td.sno {
            font-weight: 600;
            padding: 5px 12px;
        }

        .invoice-box table td.tax,
        .invoice-box table td.amount,
        .invoice-box table td.grand-total {

            white-space: nowrap;
        }

        .invoice-box table td.amount,
        .invoice-box table th.amount {
            padding-right: 15px;
        }

        .invoice-box table tr.top table td {
            padding-bottom: 20px;
        }

        .invoice-box table tr.top table td.title {
            font-size: 46px;
            line-height: 45px;
            color: #333;
        }

        .invoice-box table tr.top table td.title img.logo {

            display: block;
            width: 100%;
            max-width: 200px;
        }

        .invoice-box table tr.information table td {
            padding-bottom: 40px;
        }

        .invoice-box table th.heading td {
            background: #eee;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
        }

        .invoice-box table tr.details td {
            padding-bottom: 20px;
        }

        .invoice-box table tr.item td {
            border-bottom: 1px solid #eee;
        }

        .invoice-box table tr.item.last td {
            border-bottom: none;
        }

        .invoice-box table tr.total td {
            border-top: 2px solid #eee;
            font-weight: bold;
        }

        .invoice-box table tr.total td.empty {

            border-top: none;
        }

        .text-right {

            text-align: right;
        }

        .text-center {

            text-align: center;
        }

        .text-left {

            text-align: left !important;
        }

        .font-bold {

            font-weight: bold;
        }


        @media only print {

            body {

                font-size: 13px;
                line-height: 15px;
            }

            .invoice-box {

                padding: 15px;
            }

            .invoice-box table td {

                padding: 0 2px;
            }

            .invoice-box table th {

                padding-left: 0;
                padding-right: 0;
            }

            .invoice-box table tr.top table td {

                padding-bottom: 10px;
            }

            .invoice-box table tr.information table td {

                padding-bottom: 20px;
            }

            .invoice-box table td.sno,
            .invoice-box table th.sno {

                padding: 0 12px;
            }

            .foot-note {

                margin-bottom: 0;
                page-break-after: always;
            }
        }

        @media only screen and (max-width: 600px) {
            .invoice-box table tr.top table td {
                width: 100%;
                display: block;
                text-align: center;
            }

            .invoice-box table tr.information table td {
                width: 100%;
                display: block;
                text-align: center;
            }
        }

        .invoice-summary {
            font-weight: 600;
            font-size: 17px;
            line-height: 24px;
            color: rgba(0, 0, 0, 0.6);
        }

        .invoice-box table tr.information {
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }

        .invoice-box table tr.information td {
            padding: 6px 0;
        }

        .text-black-o-40 {
            color: rgba(0, 0, 0, 0.4);
        }

        .text-black-o-60 {
            color: rgba(0, 0, 0, 0.6);
        }

        .text-black-o-80 {
            color: rgba(0, 0, 0, 0.8);
        }

        .font-weight-600 {
            font-weight: 600;
        }

        .invoices-table tr:last-child {
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }

        .invoices-table .invoice-total-row{
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }

        .invoice-box .invoice-table-cnt {
            padding: 0;
        }

        .invoice-box .invoice-header,
        .invoice-box .invoice-header table td {
            padding: 0;
        }

        .invoices-table tr td:last-child {
            font-weight: 600;
        }
    </style>

</head>


<body>



<div class="invoice-box" style="z-index:2;margin-top: -20px;background: white;">

    <table cellpadding="0" cellspacing="0">


        <tr class="top">

            <td colspan="2" class="invoice-header">

                <table>

                    <tr style="z-index:2;margin-top: -20px;background: white;">

                        <td class="title">
                            <span style="color: #FFFFFF">.</span>
                            <img class="logo" src="https://cdn.razorpay.com/static/assets/razorpayx/logos/rx-dark-logo.png">

                            <div class="invoice-summary">
                                TAX INVOICE
                            </div>
                        </td>


                        <td class="text-right" style="padding-top: 60px;">
                            <span class="text-black-o-40">Billing Period</span> {{$billing_period}} <br>
                            <span class="text-black-o-40">Invoice Issued on</span> {{$invoice_date}}<br>
                            <span class="text-black-o-40">Invoice</span> {{$invoice_number}}
                        </td>

                    </tr>

                </table>

            </td>

        </tr>


        <tr class="information">

            <td colspan="2">

                <table>

                    <tr>

                        <td>
                            <strong class="text-black-o-40">Bill To:</strong><br />
                            <span class="text-black-o-80 font-weight-600">{{$issued_to['name']}} {{$issued_to['merchant_id']}} </span><br>
                            {{$issued_to['address']}}<br />
                            {{$issued_to['business_registered_city']}}
                            -
                            {{$issued_to['business_registered_pin']}}<br />
                            {{$issued_to['business_registered_state']}}<br /><br />
                            <span class="font-weight-600 text-black-o-60">GSTIN</span> - {{$gstin}}<br /><br />
                            <strong class="text-black-o-40">Ship To:</strong><br />
                            <span class="text-black-o-80 font-weight-600">{{$issued_to['name']}} {{$issued_to['merchant_id']}} </span><br>
                            {{$issued_to['address']}}<br />
                            {{$issued_to['business_registered_city']}}
                            -
                            {{$issued_to['business_registered_pin']}}<br />
                            {{$issued_to['business_registered_state']}}<br /> <br />
                        </td>

                        <td class="text-right">
                            <strong class="text-black-o-40">From:</strong><br />
                            <span class="text-black-o-80 font-weight-600">Razorpay Software Pvt. Ltd. </span><br>
                            #22, 1st Floor, SJR Cyber,<br />
                            Laskar Hosur Road, Adugodi,<br />
                            Bangalore, Karnataka - 560 030.<br /><br />
                            <span class="font-weight-600 text-black-o-60">GSTIN</span> - 29AAGCR4375J1ZU<br />
                            <span class="font-weight-600 text-black-o-60">Pan No.</span> - AAGCR4375J<br />
                            <span class="font-weight-600 text-black-o-60">CIN No.</span> - U72200KA2013PTC097389
                        </td>

                    </tr>

                </table>

            </td>

        </tr>

        <tr>

            <td colspan="2" class="invoice-table-cnt">

                <table>

                    <thead>

                    <tr class="heading">

                        <th class="sno">
                            #
                        </th>

                        <th class="doc-no">
                            DESCRIPTION
                        </th>

                        <th class="doc-date">
                            GST.SAC CODE
                        </th>

                        <th class="amount text-right">
                            AMOUNT
                        </th>

                    </tr>

                    </thead>

                    <tbody class="invoices-table">
                    <?php $rowsSize = sizeOf($rows);
                        $rowIndex = 0;
                    ?>
                    @foreach($rows as $key => $rowItem)

                        <?php $isTotalRow = $key === "combined"; ?>

                        @if (!$isTotalRow)
                            <tr>
                                <td class="sno">
                                    {{$rowIndex + 1}}
                                    <?php $rowIndex = $rowIndex + 1 ?>
                                </td>
                                <td class="doc-no">
                                    @if($rowItem['account_type'] === 'shared')
                                        <span class="text-black-o-80 font-weight-600">RazorpayX Virtual Account Transactions</span><br>
                                    @elseif($rowItem['account_type'] === 'direct')
                                        <span class="text-black-o-80 font-weight-600">{{strtoupper($rowItem['channel'])}} Current Account Transactions</span><br>
                                    @endif
                                    <span class="text-black-o-60">A/C No. {{$key}}</span>
                                </td>
                                <td class="doc-date">
                                    {{$rowItem['GST.SAC Code']}}
                                </td>
                                <td class="amount text-right">
                                    @include('merchant/invoice/components/currency',['value' => $rowItem['amount']])
                                </td>
                            </tr>
                        @else
                            <tr class="invoice-total-row">
                                <td colspan="3" class="text-right font-weight-600 text-black-o-80">
                                    Total Amount
                                </td>
                                <td class="amount text-right">
                                    @include('merchant/invoice/components/currency',['value' => $rowItem['amount']])
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-right font-weight-600 text-black-o-80">
                                    @if (array_key_exists('CGST_9%', $rowItem) and empty($rowItem['CGST_9%'])===false)
                                       CGST 9%
                                    @endif
                                </td>
                                <td class="amount text-right">
                                    @if (array_key_exists('CGST_9%', $rowItem) and empty($rowItem['CGST_9%'])===false)
                                        @include('merchant/invoice/components/currency',['value' => $rowItem['CGST_9%']])
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-right font-weight-600 text-black-o-80">
                                    @if (array_key_exists('SGST_9%', $rowItem) and empty($rowItem['SGST_9%'])===false)
                                        SGST 9%
                                    @endif
                                </td>
                                <td class="amount text-right">
                                    @if (array_key_exists('SGST_9%', $rowItem) and empty($rowItem['SGST_9%'])===false)
                                        @include('merchant/invoice/components/currency',['value' => $rowItem['SGST_9%']])
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-right font-weight-600 text-black-o-80">
                                    @if (array_key_exists('IGST_18%', $rowItem) and empty($rowItem['IGST_18%'])===false)
                                        IGST 18%
                                    @endif
                                </td>
                                <td class="amount text-right">
                                    @if (array_key_exists('IGST_18%', $rowItem) and empty($rowItem['IGST_18%'])===false)
                                        @include('merchant/invoice/components/currency',['value' => $rowItem['IGST_18%']])
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-right text-black-o-80">
                                    <strong>Grand Total</strong>
                                </td>
                                <td class="amount text-right text-black-o-80">
                                    <strong>@include('merchant/invoice/components/currency',['value' => $rowItem['grand_total']])</strong>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                    </tbody>

                </table>

            </td>

        </tr>

    </table>

    <div class="foot-note text-left">
        <div class="bank-details">
            <div>
                <span class="text-black-o-40 font-bold">Bank Details</span>
            </div>
            <div>
                <span class="font-weight-600">Account Name</span>
                <span class="text-black-o-80 font-weight-600">Razorpay Pvt. Ltd</span>
            </div>
            <div>
                <span class="font-weight-600">Account No.</span>
                <span>1234 1234 1234 1245</span>

            </div>
            <div>
                <span class="font-weight-600">Bank Name</span>
                <span >HDFC Koramangala</span>
            </div>
            <div>
                <span class="font-weight-600">IFSC</span>
                <span>HDFC12341245</span>
            </div>
        </div>
        <br>
        <span class="text-black-o-80 font-weight-600">Notes</span>
        <ol>

            <li>To get GST input, please make sure that you have updated your GSTIN in your Razorpay Dashboard.</li>

            <li>All the Invoice, Debit &amp; Credit note values are inclusive of GST.</li>

            <li>In case your GSTIN is not updated then we will generate an SGST &amp; CGST invoice.</li>

            <li>Invoicing is per IST timezone</li>

            @if($billing_period == '01/12/2020-30/12/2020')
            <li>If you are a registered entity, the invoice raised for the next billing cycle will be registered on the
                GST IRP (Invoice Registration Portal) as per GST guidelines.</li>

            <li>Please ensure that your GSTIN, Registered Address and PIN code is updated as per GST portal.
                You can click here to learn more:<a href="https://razorpay.com/docs/announcements/gst-changes/">
                    https://razorpay.com/docs/announcements/gst-changes/</a></li><br>
            @endif

        </ol>
        @if($billing_period == '01/12/2020-30/12/2020')
            <div class="text-left" style="border:1px solid black;padding:5px;margin-bottom:10px;font-weight:bold;">
                This invoice is for the billing cycle starting on Dec 01, 2020 to Dec 30, 2020. The charges for December 31, 2020 will be added to the next billing cycle.
            </div>
        @endif

    </div>

</div>


<script>window.print();</script>

</body>

</html>
