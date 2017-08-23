<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Razorpay - Tax Invoice</title>

  <style>

  html, body {

    margin: 0;
    padding: 0;
    width: 100%;
  }

  body {

    font-family:'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
    font-size:14px;
  }

  body * {

    box-sizing: border-box;
    -webkit-box-sizing: border-box;
    -moz-box-sizing: border-box;
    -o-box-sizing: border-box;
  }

  .invoice-box{
    max-width:800px;
    margin:auto;
    padding:30px;
    border:1px solid #eee;
    box-shadow:0 0 10px rgba(0, 0, 0, .15);
    line-height:24px;
    color:#555;
  }

  .foot-note {

    font-size: 12px;
    text-align: center;
    margin-top: 10px;
  }

  .invoice-box table{
    width:100%;
    line-height:inherit;
    text-align:left;
    border-collapse: collapse;
  }

  .invoice-box table th {

    background-color: #eee;
    border-bottom: 1px solid #ddd;
  }

  .invoice-box table td, .invoice-box table th{
    padding:5px 8px;
    vertical-align:top;
  }

  .invoice-box table td.sno {

    padding: 5px 12px;
  }

  .invoice-box table td.tax {

    white-space: nowrap;
  }

  .invoice-box table tr.top table td{
    padding-bottom:20px;
  }

  .invoice-box table tr.top table td.title{
    font-size:45px;
    line-height:45px;
    color:#333;
  }

  .invoice-box table tr.information table td{
    padding-bottom:40px;
  }

  .invoice-box table th.heading td{
    background:#eee;
    border-bottom:1px solid #ddd;
    font-weight:bold;
  }

  .invoice-box table tr.details td{
    padding-bottom:20px;
  }

  .invoice-box table tr.item td{
    border-bottom:1px solid #eee;
  }

  .invoice-box table tr.item.last td{
    border-bottom:none;
  }

  .invoice-box table tr.total td {
    border-top:2px solid #eee;
    font-weight:bold;
  }

  .invoice-box table tr.total td.empty {

    border-top: none;
  }

  .text-right {

    text-align: right;
  }

  .font-bold {

    font-weight: bold;
  }

  /*
  .code {

    font-family: "Courier New", Courier, monospace;
  }*/

  div.bank-details {

    width: 100%;
  }

  div.bank-details table {

    margin: 10px auto;
  }

  div.bank-details table thead th {

    background-color: transparent;
    font-weight: bold;
  }

  div.bank-details table td.lesser-width {

    width: 1%;
    white-space: nowrap;
  }

  div.bank-details table td {

    vertical-align: middle;
  }

  @media only print {

    body {

      font-size: 9pt;
      line-height: 12pt;
    }

    .invoice-box table td {

      padding: 0 2px;
    }

    .invoice-box table th {

      padding-left: 0;
      padding-right: 0;
    }

    .invoice-box table tr.top table td{

      padding-bottom:10px;
    }

    .invoice-box table tr.information table td{

      padding-bottom:20px;
    }

    .invoice-box table td.sno, .invoice-box table th.sno {

      padding: 0 12px;
    }

    div.bank-details table td.lesser-width.seperator {

      padding: 2px;
    }
  }

  @media only screen and (max-width: 600px) {
    .invoice-box table tr.top table td{
      width:100%;
      display:block;
      text-align:center;
    }

    .invoice-box table tr.information table td{
      width:100%;
      display:block;
      text-align:center;
    }
  }
  </style>
</head>

<body>
  <div class="invoice-box">
    <table cellpadding="0" cellspacing="0">
      <tr class="top">
        <td colspan="2">
          <table>
            <tr>
              <td class="title">
                <img src="data:image/png;base64,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" style="width:100%; max-width:200px;">
              </td>

              <td class="text-right">
                Invoice #: {{{$invoice_id}}}<br>
                Created: {{{$dates['billingDate']}}}<br>
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
                <b>From:</b><br/>
                Razorpay Software Pvt. Ltd.<br/>
                #22, 1st Floor, SJR Cyder,<br/>
                Laskar Hosur Road, Adugodi,<br/>
                Bangalore, Karnataka - 560 030.<br/>
                <span class="code">GSTIN - {{{ $rzp_gstin }}}<br/>
                Pan No. - {{{ $rzp_pan_no }}}<br/>
                CIN No. - {{{ $rzp_cin_no }}}</span>
              </td>

              <td class="text-right">
                <b>Issued To:</b><br/>
                {{{$merchant['name']}}} [{{{$merchant['id']}}}]<br>
                @if ($merchant_details['business_registered_address'])
                {{{$merchant_details['business_registered_address']}}}<br/>
                @endif
                @if ($merchant_details['business_registered_city'])
                {{{$merchant_details['business_registered_city']}}}
                @if ($merchant_details['business_registered_pin'])
                -
                @else
                <br/>
                @endif
                @endif
                @if ($merchant_details['business_registered_pin'])
                {{{$merchant_details['business_registered_pin']}}}<br/>
                @endif
                @if ($merchant_details['business_registered_state'])
                {{{$merchant_details['business_registered_state']}}}<br/>
                @endif
                @if (!empty($gst))
                <span class="code">GSTIN - {{{$gst}}}</span>
                @endif
              </td>
            </tr>
          </table>
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
              <?php $rowsSize = sizeOf($rows); ?>

              @foreach($rows as $rowIndex => $rowItem)

              <?php $isTotalRow = $rowItem['Description'] === "Total"; ?>

              @if (!$isTotalRow)
              <tr class="item <?php echo(($rowsSize === 1 || $rowsSize - 2 === $rowIndex) ? "last" : "")?>">
                <td class="sno">
                  {{{$rowItem['Sl. No.']}}}.
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
                  <b>₹{{{$rowItem['Amount']}}}</b>
                </td>
                <td class="tax text-right">
                  @if (array_key_exists('SGST @ 9%', $rowItem))
                  SGST @ 9% - ₹{{{ $rowItem['SGST @ 9%'] }}}<br/>
                  @endif

                  @if (array_key_exists('CGST @ 9%', $rowItem))
                  CGST @ 9% - ₹{{{ $rowItem['CGST @ 9%'] }}}<br/>
                  @endif

                  @if (array_key_exists('IGST @ 18%', $rowItem))
                  IGST @ 18% - ₹{{{ $rowItem['IGST @ 18%'] }}}<br/>
                  @endif

                  @if (array_key_exists('Tax Total', $rowItem))
                  <b>Tax Total - ₹{{{ $rowItem['Tax Total'] }}}</b>
                  @endif
                </td>
                <td class="grand-total text-right">
                  <b>₹{{{$rowItem['Grand Total']}}}</b>
                </td>
              </tr>
              @endforeach

              <tr>
                <td colspan="4"></td>
                <td class="text-right">Paid</td>
                <td class="text-right font-bold">₹{{{ $total_amount_paid }}}</td>
              </tr>
              <tr>
                <td colspan="4"></td>
                <td class="text-right">Due</td>
                <td class="text-right font-bold">₹{{{ $total_amount_due }}}</td>
              </tr>
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
  <script>window.print();</script>
</body>
</html>
