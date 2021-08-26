<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Razorpay - Tax Invoice</title>

    <style>
        @include('merchant/pg_invoice/components/styles')
    </style>
</head>

<body>

@if($Summary)

    @foreach($Summary as $pageName => $pageValue)
        <?php $rows = isset($pageValue['rows']) ? $pageValue['rows'] : [];
        $rowsSize = sizeOf($rows);
        $hasPage = $rowsSize > 0; ?>

        @if ($hasPage)
            <div class="invoice-box">
                <div class="page-title">
                    {{{ $pageName }}}
                </div>
                <table cellpadding="0" cellspacing="0">

                    @include('merchant/pg_invoice/components/pageheader')

                    <tr>
                        <td colspan="2" class="text-center">
                            This Invoice summary is for the billing period <b>{{{ $dates['startDate']}}}</b> - <b>{{{$dates['endDate']}}}</b>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" class="text-center">
                            This document includes the electronic invoice for the use of Razorpay's Service. The summary of this document is as follows and the details are provided below.
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <table>
                                <thead>
                                <tr class="heading">
                                    <th class="sno">
                                        #
                                    </th>
                                    <th class="doc-no">
                                        Document No.
                                    </th>
                                    <th class="doc-date">
                                        Document Date
                                    </th>
                                    <th class="description">
                                        Description
                                    </th>
                                    <th class="amount text-right">
                                        Amount
                                    </th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($rows as $rowIndex => $rowItem)

                                    <?php $isTotalRow = $rowItem['Description'] === "Total"; ?>
                                    @if (!$isTotalRow)
                                        <tr class="item <?php echo(($rowsSize === 1 || $rowsSize - 2 === $rowIndex) ? "last" : "")?>">
                                            <td class="sno">
                                                {{{ $rowIndex + 1 }}}.
                                            </td>
                                            <td class="doc-no">
                                                {{{ $rowItem['Document No.'] }}}
                                            </td>
                                            <td class="doc-date">
                                                @if(isset($einvoice_data['e_invoice_complete_generation_date']))
                                                    {{{$einvoice_data['e_invoice_complete_generation_date']}}}
                                                @else
                                                    {{{ $rowItem['Document Date'] }}}
                                                @endif
                                            </td>
                                    @else
                                        <tr class="total">
                                            <td class="empty" colspan="3"></td>
                                            @endif
                                            <td class="description <?php echo($isTotalRow ? "text-right" : "") ?>">
                                                {{{ $rowItem['Description'] }}}
                                            </td>
                                            <td class="amount text-right">
                                                @include('merchant/pg_invoice/components/currency',
                                                         ['value' => $rowItem['Amount']])
                                            </td>
                                        </tr>
                                        @endforeach
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
        @endif

    @endforeach

    <div class="foot-note text-left">
        <ol>
            <li>To get GST input, please make sure that you have updated your GSTIN in your Razorpay Dashboard.</li>
            <li>All the Invoice, Debit &amp; Credit note values are inclusive of GST.</li>
            @if (isset($is_postpaid) && $is_postpaid !== true)
            <li>The payment for this invoice has been electronically collected and the details of these amounts may be found enclosed.</li>
            @endif
            <li>Unless otherwise stated, tax on this invoice is not payable under reverse charge.</li>
        </ol>
        @if (isset($einvoice_data['callout_message']))
            <div class="text-left" style="border:1px solid black;padding:5px;margin-bottom:10px;font-weight:bold;">
                NOTE: {{{$einvoice_data['callout_message']}}}
            </div>
        @endif
    </div>

@endif


@if($pages)

    @foreach($pages as $pageName => $pageValue)
        <?php $rows = isset($pageValue['rows']) ? $pageValue['rows'] : [];
        $rowsSize = sizeOf($rows);
        $hasPage = $rowsSize > 0; ?>

        @if ($hasPage)
            <div class="invoice-box">
                <div class="page-title">
                    {{{ $pageName }}}
                </div>
                <div>
                    @include('merchant/pg_invoice/components/pageheader')
                </div>
                <div style=“position:absolute;bottom:350px;“>
                    @if (isset($einvoice_data[$pageName]['Irn']))
                        IRN - {{{$einvoice_data[$pageName]['Irn']}}}<br/>
                    @endif
                    @if (isset($einvoice_data[$pageName]['QRCodeUrl']))
                        <img style=“height:140px;width:140px;” src={{{$einvoice_data[$pageName]['QRCodeUrl']}}}/>
                        <br/>
                    @endif
                </div>
                <table style=“position:relative;">
                    <tr>
                        <td colspan="2">
                            <table>
                                <thead>
                                <tr class="heading">
                                    <th class="sno">
                                        #
                                    </th>
                                    <th class="gst-code">
                                        GST. SAC Code
                                    </th>
                                    <th class="description">
                                        Description
                                    </th>
                                    <th class="amount text-right">
                                        Amount
                                    </th>
                                    <th class="tax text-right">
                                        Tax
                                    </th>
                                    <th class="grand-total text-right">
                                        Grand Total
                                    </th>
                                </tr>
                                </thead>

                                <tbody>
                                @foreach($rows as $rowIndex => $rowItem)

                                    <?php $isTotalRow = $rowItem['Description'] === "Total"; ?>

                                    @if (!$isTotalRow)
                                        <tr class="item <?php echo(($rowsSize === 1 || $rowsSize - 2 === $rowIndex) ? "last" : "")?>">
                                            <td class="sno">
                                                {{{ $rowIndex + 1 }}}.
                                            </td>
                                            <td class="gst-code">
                                                {{{$rowItem['GST.SAC Code']}}}
                                            </td>
                                    @else
                                        <tr class="total">
                                            <td class="empty" colspan="2"></td>
                                            @endif

                                            <td class="description <?php echo($isTotalRow ? "text-right" : "") ?>">
                                                {{{$rowItem['Description']}}}
                                            </td>
                                            <td class="amount text-right">
                                                <b>@include('merchant/pg_invoice/components/currency', ['value' => $rowItem['Amount']])</b>
                                            </td>
                                            <td class="tax text-right">
                                                @if (array_key_exists('SGST @ 9%', $rowItem))
                                                    <div>SGST @ 9% : @include('merchant/pg_invoice/components/currency', ['value' => $rowItem['SGST @ 9%']])</div>
                                                @endif

                                                @if (array_key_exists('CGST @ 9%', $rowItem))
                                                    <div>CGST @ 9% : @include('merchant/pg_invoice/components/currency', ['value' => $rowItem['CGST @ 9%']])</div>
                                                @endif

                                                @if (array_key_exists('IGST @ 18%', $rowItem))
                                                    <div>IGST @ 18% : @include('merchant/pg_invoice/components/currency', ['value' => $rowItem['IGST @ 18%']])</div>
                                                @endif

                                                @if (array_key_exists('Tax Total', $rowItem))
                                                    <b>Tax Total : @include('merchant/pg_invoice/components/currency',
                                          ['value' => $rowItem['Tax Total']])</b>
                                                @endif
                                            </td>
                                            <td class="grand-total text-right">
                                                <b>@include('merchant/pg_invoice/components/currency',
                              ['value' => $rowItem['Grand Total']])</b>
                                            </td>
                                        </tr>
                                @endforeach

                                {{--
                                @if (isset($pageValue['total_amount_paid']))
                                <tr>
                                  <td colspan="4"></td>
                                  <td class="text-right">Paid</td>
                                  <td class="text-right font-bold">
                                    @include('components/currency',
                                             ['value' => $pageValue['total_amount_paid']])
                                  </td>
                                </tr>
                                @endif

                                @if(isset($pageValue['total_amount_due']))
                                <tr>
                                  <td colspan="4"></td>
                                  <td class="text-right">Due</td>
                                  <td class="text-right font-bold">
                                    @include('components/currency',
                                             ['value' => $pageValue['total_amount_due']])
                                </tr>
                                @endif
                                --}}

                                <tbody>
                            </table>
                        </td>
                    </tr>
                </table>

                <div class="bank-details">
                    <table>
                        <thead>
                        <tr>
                            <th colspan="2">Bank Details</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>
                                <table>
                                    <tbody>
                                    <tr>
                                        <td class="lesser-width">Account Name</td>
                                        <td class="lesser-width seperator">:</td>
                                        <td>Razorpay Software Pvt. Ltd.</td>
                                    </tr>

                                    <tr>
                                        <td class="lesser-width">Account No.</td>
                                        <td class="lesser-width seperator">:</td>
                                        <td>50200001324291</td>
                                    </tr>

                                    <tr>
                                        <td class="lesser-width">Account Type</td>
                                        <td class="lesser-width seperator">:</td>
                                        <td>Current Account</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                            <td>
                                <table>
                                    <tbody>
                                    <tr>
                                        <td class="lesser-width">Bank Name</td>
                                        <td class="lesser-width seperator">:</td>
                                        <td>HDFC Bank Limited</td>
                                    </tr>
                                    <tr>
                                        <td class="lesser-width">IFSC Code</td>
                                        <td class="lesser-width seperator">:</td>
                                        <td>HDFC0000053</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="foot-note">
                Note: This is an auto generated invoice, no signature required.
            </div>
        @endif

    @endforeach

    <script>window.print();</script>

@endif
</body>
</html>
