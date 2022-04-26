<!doctype html>
<html>

<head>

    <meta charset="utf-8">

    <title>RazorpayX - Tax Invoice</title>

    <style>
        @include('merchant/invoice/components/styles')
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
                                @if($pageName == 'INV')
                                    TAX INVOICE
                                @endif
                                @if($pageName == 'CRN')
                                     TAX CREDIT NOTE
                                @endif
                            </div>
                        </td>


                        <td class="text-right" style="padding-top: 60px;">
                            @if($pageName == 'INV')
                                <span class="text-black-o-40">Billing Period</span> {{$billing_period}} <br>
                            @endif
                            @if($pageName == 'CRN')
                                <span class="text-black-o-40">Credit Note Number</span> {{$invoice_number}} <br>
                            @endif
                            @if($pageName == 'INV')
                                    <span class="text-black-o-40">Invoice Issued on</span> {{$invoice_date}}<br>
                            @endif
                            @if($pageName == 'CRN')
                                <span class="text-black-o-40">Credit Note Issued on</span> {{$invoice_date}}<br>
                            @endif
                            @if($pageName == 'INV')
                                <span class="text-black-o-40">Invoice</span> {{$invoice_number}}
                            @endif
                            @if($pageName == 'CRN')
                                <span class="text-black-o-40">Invoice Reference</span>
                                    @if(isset($e_invoice_details['InvoiceNumber'])){{$e_invoice_details['InvoiceNumber']}}
                                    @else{{$invoice_number}}
                                    @endif
                            @endif
                            @if($pageName == 'CRN')
                                <span class="text-black-o-40">Reference Invoice Issue Date</span>
                                @if(isset($e_invoice_details['InvoiceNumberIssueDate'])){{$e_invoice_details['InvoiceNumberIssueDate']}}
                                @else{{$invoice_date}}
                                @endif
                            @endif
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
                            <div style=“position:absolute;bottom:350px;“>
                                @if (isset($e_invoice_details['Irn']))
                                    IRN - {{{$e_invoice_details['Irn']}}}<br/>
                                @endif
                            </div>
                        </td>

                        <td class="text-right">
                            <strong class="text-black-o-40">From:</strong><br />
                            @if($seller_entity === 'RSPL')
                                <span class="text-black-o-80 font-weight-600">Razorpay Software Pvt. Ltd. </span><br>
                                #22, 1st Floor, SJR Cyber,<br />
                                Laskar Hosur Road, Adugodi,<br />
                                Bangalore, Karnataka - 560 030.<br /><br />
                                <span class="font-weight-600 text-black-o-60">GSTIN</span> - 29AAGCR4375J1ZU<br />
                                <span class="font-weight-600 text-black-o-60">Pan No.</span> - AAGCR4375J<br />
                                <span class="font-weight-600 text-black-o-60">CIN No.</span> - U72200KA2013PTC097389<br/>
                            @else
                                <?php $billing_period_split = explode("/", explode("-", $billing_period)[0]); ?>

                                @if(((int)$billing_period_split[1] >= 4 and (int)$billing_period_split[2] == 2022) or ((int)$billing_period_split[2] > 2022))
                                    <span class="text-black-o-80 font-weight-600">RZPX PRIVATE LIMITED </span><br>
                                    #22, Ground Floor, SJR Cyber,<br />
                                    Laskar Hosur Road, Adugodi,<br />
                                    Bangalore, Karnataka - 560 030.<br /><br />
                                    <span class="font-weight-600 text-black-o-60">GSTIN</span> - 29AAKCR4702K1Z1<br />
                                    <span class="font-weight-600 text-black-o-60">Pan No.</span> - AAKCR4702K<br />
                                    <span class="font-weight-600 text-black-o-60">CIN No.</span> - U72900KA2020PTC139072<br/>
                                @else
                                    <span class="text-black-o-80 font-weight-600">RZPX PRIVATE LIMITED </span><br>
                                    #22, 1st Floor, SJR Cyber,<br />
                                    Laskar Hosur Road, Adugodi,<br />
                                    Bangalore, Karnataka - 560 030.<br /><br />
                                    <span class="font-weight-600 text-black-o-60">GSTIN</span> - 29AAKCR4702K1Z1<br />
                                    <span class="font-weight-600 text-black-o-60">Pan No.</span> - AAKCR4702K<br />
                                    <span class="font-weight-600 text-black-o-60">CIN No.</span> - U72900KA2020PTC139072<br/>
                                @endif
                            @endif
                            <div style=“position:absolute;bottom:350px;“>
                                @if (isset($e_invoice_details['QRCodeUrl']))
                                    <img style=“height:140px;width:140px;” src={{{$e_invoice_details['QRCodeUrl']}}}/>
                                    <br/>
                                @endif
                            </div>
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
                        <?php $isSellerEntityRow = $key === "seller_entity"; ?>

                        @if (!$isTotalRow and !$isSellerEntityRow)
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
                        @elseif(!$isSellerEntityRow)
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
        @if($seller_entity === 'RSPL')
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
        @else
            <div class="bank-details">
                <div>
                    <span class="text-black-o-40 font-bold">Bank Details</span>
                </div>
                <div>
                    <span class="font-weight-600">Account Name</span>
                    <span class="text-black-o-80 font-weight-600">RZPX PRIVATE LIMITED</span>
                </div>
                <div>
                    <span class="font-weight-600">Account No.</span>
                    <span>5020 0053 6761 16</span>

                </div>
                <div>
                    <span class="font-weight-600">Bank Name</span>
                    <span >HDFC Koramangala</span>
                </div>
                <div>
                    <span class="font-weight-600">IFSC</span>
                    <span>HDFC0000053</span>
                </div>
            </div>
        @endif
        <br>
        <span class="text-black-o-80 font-weight-600">Notes</span>
        <ol>

            <li>To get GST input, please make sure that you have updated your GSTIN in your Razorpay Dashboard.</li>

            <li>All the Invoice, Debit &amp; Credit note values are inclusive of GST.</li>

            <li>Invoicing is per IST timezone</li>

            @if($billing_period == '01/12/2020-30/12/2020')
            <li>If you are a registered entity, the invoice raised for the next billing cycle will be registered on the
                GST IRP (Invoice Registration Portal) as per GST guidelines.</li>

            <li>Please ensure that your GSTIN, Registered Address and PIN code is updated as per GST portal.
                You can click here to learn more:<a href="https://razorpay.com/docs/announcements/gst-changes/">
                    https://razorpay.com/docs/announcements/gst-changes/</a></li><br>
            @endif
            <?php $billing_period_split = explode("/", explode("-", $billing_period)[0]); ?>
            @if(((int)$billing_period_split[1] >= 8 and (int)$billing_period_split[2] == 2021) or ((int)$billing_period_split[2] > 2021))
                <li>Unless otherwise stated, tax on this invoice is not payable under reverse charge.</li>
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
