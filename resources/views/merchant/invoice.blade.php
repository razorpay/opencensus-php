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

  .invoice-box {
    max-width:800px;
    margin:auto;
    padding:30px;
    border:1px solid #eee;
    box-shadow:0 0 10px rgba(0, 0, 0, .15);
    line-height:24px;
    color:#555;
    position: relative;
  }

  .invoice-box div.page-title {

    position: absolute;
    top: 0;
    left: 0;
    padding: 2px;
    width: 100%;
    text-transform: uppercase;
    text-align: center;
  }

  .foot-note {

    font-size: 12px;
    text-align: center;
    margin-top: 10px;
    page-break-after: always;
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

  .invoice-box table tr.top table td.title img.logo{

    display: block;
    width:100%;
    max-width:200px;
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
    <div class="page-title">
      TAX INVOICE
    </div>
    <table cellpadding="0" cellspacing="0">
      <tr class="top">
        <td colspan="2">
          <table>
            <tr>
              <td class="title">
                <img class="logo" src="https://cdn.razorpay.com/logo-small.png">
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
