<!doctype html>
<html>

<head>

    <meta charset="utf-8">

    <title>Razorpay - Tax Invoice</title>

    <style>
        @include('payment/invoice/components/styles')
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
                            <img class="logo" src="https://cdn.razorpay.com/static/assets/international/r-dark-logo.png">

                            <div class="invoice-summary">
                                @if($documentType == 'INV')
                                      TAX INVOICE
                                @endif
                                @if($documentType == 'CRN')
                                      TAX CREDIT NOTE
                                @endif
                            </div>
                        </td>


                        <td class="text-right" style="padding-top: 60px;">
                            @if($documentType == 'INV')
                                <span class="text-black-o-40">Invoice Date</span> {{$invoice_date}}<br>
                                <span class="text-black-o-40">Invoice No.</span> {{$invoice_number}}
                            @endif
                            @if($documentType == 'CRN')
                                <span class="text-black-o-40">Credit Note No.</span> {{$invoice_number}} <br>
                                <span class="text-black-o-40">Credit Note Date</span> {{$invoice_date}}<br>
                                <span class="text-black-o-40">Invoice Reference</span>
                                @if(isset($e_invoice_details['InvoiceNumber'])){{$e_invoice_details['InvoiceNumber']}}
                                @else{{$invoice_number}}
                                @endif
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
                            <span class="text-black-o-80 font-weight-600">{{$issued_to['legal_name']}} </span><br>
                            {{$issued_to['address1']}}<br />
                            {{$issued_to['location']}} <br />
                            Outside India - 96 <br /><br />
                            <span class="font-weight-600 text-black-o-60">GSTIN</span> - {{$issued_to['gstin']}}<br /><br />
                            <strong class="text-black-o-40">Ship To:</strong><br />
                            <span class="text-black-o-80 font-weight-600">{{$issued_to['legal_name']}} </span><br>
                            {{$issued_to['address1']}}<br />
                            {{$issued_to['location']}} <br />
                            Outside India - 96 <br /><br />
                            <div style=“position:absolute;bottom:350px;“>
                                @if (isset($e_invoice_details['Irn']))
                                    IRN - {{{$e_invoice_details['Irn']}}}<br/>
                                @endif
                            </div>
                        </td>

                        <td class="text-right">
                            <strong class="text-black-o-40">From:</strong><br />
                            <span class="text-black-o-80 font-weight-600">Razorpay Software Pvt. Ltd. </span><br>
                            #22, 1st Floor, SJR Cyber,<br />
                            Laskar Hosur Road, Adugodi,<br />
                            Bangalore, Karnataka - 560 030<br />
                            State/UT Code: 29<br /><br />
                            <span class="font-weight-600 text-black-o-60">GSTIN</span> - 29AAGCR4375J1ZU<br />
                            <span class="font-weight-600 text-black-o-60">Pan No.</span> - AAGCR4375J<br />
                            <span class="font-weight-600 text-black-o-60">CIN No.</span> - U72200KA2013PTC097389<br/>
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

                        <tr>
                            <td class="sno">
                                {{$rowIndex + 1}}
                                <?php $rowIndex = $rowIndex + 1 ?>
                            </td>
                            <td class="doc-no">
                                <span class="text-black-o-80 font-weight-600">{{$rowItem['product_description']}}</span><br>
                            </td>
                            <td class="doc-date">
                                {{$rowItem['hsn_code']}}
                            </td>
                            <td class="amount text-right">
                                @include('payment/invoice/components/currency',['value' => $rowItem['total_item_value']])
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-right font-weight-600 text-black-o-80">
                                IGST 18%
                            </td>
                            <td class="amount text-right">
                                @include('payment/invoice/components/currency',['value' => 0])
                            </td>
                        </tr>
                        <tr class="invoice-total-row">
                            <td colspan="3" class="text-right font-weight-600 text-black-o-80">
                                Total Amount
                            </td>
                            <td class="amount text-right">
                                @include('payment/invoice/components/currency',['value' => $rowItem['total_item_value']])
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-right text-black-o-80">
                                <strong>Grand Total</strong>
                            </td>
                            <td class="amount text-right text-black-o-80">
                                <strong>@include('payment/invoice/components/currency',['value' => $rowItem['total_item_value']])</strong>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>

                </table>

            </td>

        </tr>

    </table>

    <div class="foot-note text-left">
        <div class="bank-details">
            <div>
                <p class="font-weight-600">Supply meant for export under LUT without payment of Integrated Tax</p><br /><br />
            </div>
            <div>
                <span class="text-black-o-40 font-bold">Bank Details</span>
            </div>
            <div>
                <span class="font-weight-600">Account Name</span>
                <span class="text-black-o-80 font-weight-600">Razorpay Software Private Limited</span>
            </div>
            <div>
                <span class="font-weight-600">Account No.</span>
                <span>50200001324291</span>

            </div>
            <div>
                <span class="font-weight-600">Bank Name</span>
                <span >HDFC Bank Limited, Koramangala</span>
            </div>
            <div>
                <span class="font-weight-600">IFSC</span>
                <span>HDFC0000053</span>
            </div>
        </div>
        <br>
        <span class="text-black-o-80 font-weight-600 notes">Notes</span>
        <ol>
            <li>Unless otherwise stated, tax on this invoice is not payable under reverse charge as per the India GST regulations.</li><br /><br /><br />
        </ol>
            <span class="text-black-o-80 font-weight-600">This is a computer generated invoice and requires no signature</span>

    </div>

</div>


<script>window.print();</script>

</body>

</html>
