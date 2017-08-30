<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Razorpay - Tax Invoice</title>

  <style>
    @include('merchant/invoice/components/styles')
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

      @include('merchant/invoice/components/pageheader')
      <tr>
        <td colspan="2" class="text-center">
          This Invoice summary is for billing period <b>{{{ $dates['startDate']}}}</b> - <b>{{{$dates['billingDate']}}}</b>
        </td>
      </tr>
      <tr>
        <td colspan="2" class="text-center">
          Greetings from Razorpay, we're writing to provide you with an electronic invoice for use of Payment Gatweay Service. Additional information regarding your bill are available below.
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
                  {{{ $rowItem['Document Date'] }}}
                </td>
              @else
              <tr class="total">
                <td class="empty" colspan="3"></td>
              @endif
                <td class="description <?php echo($isTotalRow ? "text-right" : "") ?>">
                  {{{ $rowItem['Description'] }}}
                </td>
                <td class="amount text-right">
                  @include('components/currency',
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
      <li>To get the GST input you are requested to update your GST no. in your dashboard, ignore if already GST upated</li>
      <li>All the invoice, Debit &amp; Credit note values are inclusive of GST.</li>
      <li>In case of GST no. is not updated then we will generate SGST &amp; CGST invoice.</li>
    </ol>
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
    <table cellpadding="0" cellspacing="0">

      @include('merchant/invoice/components/pageheader')

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
                  <b>@include('components/currency', ['value' => $rowItem['Amount']])</b>
                </td>
                <td class="tax text-right">
                  @if (array_key_exists('SGST @ 9%', $rowItem))
                  <div>SGST @ 9% : @include('components/currency', ['value' => $rowItem['SGST @ 9%']])</div>
                  @endif

                  @if (array_key_exists('CGST @ 9%', $rowItem))
                  <div>CGST @ 9% : @include('components/currency', ['value' => $rowItem['CGST @ 9%']])</div>
                  @endif

                  @if (array_key_exists('IGST @ 18%', $rowItem))
                  <div>IGST @ 18% : @include('components/currency', ['value' => $rowItem['IGST @ 18%']])</div>
                  @endif

                  @if (array_key_exists('Tax Total', $rowItem))
                  <b>Tax Total : @include('components/currency',
                                          ['value' => $rowItem['Tax Total']])</b>
                  @endif
                </td>
                <td class="grand-total text-right">
                  <b>@include('components/currency',
                              ['value' => $rowItem['Grand Total']])</b>
                </td>
              </tr>
              @endforeach

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
