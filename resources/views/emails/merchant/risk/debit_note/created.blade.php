<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width">
</head>
<body class="body" style="width: 100% !important; min-width: 100%; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px; background: #EBECEE;">
<br/>
<img src="https://cdn.razorpay.com/logo.png" height=30 style="display: block; margin: auto;" />
<br/>
<div class="container" style="background-color:#3495ff; height:7px; width: 80% !important; min-width:
            80%; -webkit-text-size-adjust: 80%; -ms-text-size-adjust: 80%; margin: 0 auto;padding: 0px 20px;"></div>
<div class="container" style="border-spacing: 0; width: 80% !important; min-width: 80%;
            -webkit-text-size-adjust: 80%; -ms-text-size-adjust: 80%; margin: 0 auto; word-break: break-word;
            hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #7c839a; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; text-align: left; padding: 20px; font-size: 14px; line-height: 19px; background: #ffffff; background-color: #ffffff; padding: 20px; position: relative; border: 1px solid #e0e0e0; letter-spacing: 0.4px;">
	<br/>
	Dear Merchant,
	<br> <br>

	<br> <br>
	Please find below the details of the transaction(s), wherein there was no response from your end within the stipulated time frame. As a result, the chargeback request has been ruled in favour of the customer.

	<br><br>
	<b>Commercial Debit Note Id</b> : {{ $serial }}

	<br> <br><br> <br><br> <br><br>
	@php
		$brand_color = '#6A75ED';
        $brand_text_color = '#FFFFFF';

        $debitNoteTable = '';

        $headerColumnStyle = '<th class="content" style="word-break: break-word; -webkit-hyphens: auto;
        -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top;
        font-family: -apple-system, ' .
        "'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande' " .
        ',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding: 1%;
         background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; color: #000000;
         padding-bottom: 24px; padding-top: 0px;">
         <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; font-weight: bold;
         line-height: 20px; color: #212121;"> <br style="font-family: -apple-system, BlinkMacSystemFont, Arial,
         sans-serif; line-height: 20px; color: #212121;">';

        $rowColumnStyle = '<td class="content" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto;
        hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,' .
        "'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande' " .
        ',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 12px; line-height: 19px; padding: 1%;
        background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; color: #000000;
        padding-bottom: 24px; padding-top: 0px;">
        <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">';

        $headerRow = '
            ' . $headerColumnStyle . 'PAYMENT ID</th>
            ' . $headerColumnStyle . 'AMOUNT</th>';

        foreach ($debitNoteDataTable as $key => $data)
        {
            $paymentLink = 'https://dashboard.razorpay.com/#/app/payments/' . $data['payment_id'];

            $debitNoteTable .= '
                  <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; padding: 0; vertical-align: top; text-align: left;">

                    ' . $rowColumnStyle . '
                    <a href='. $paymentLink . ' target="_blank" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; text-decoration: none; color: #39ACE5;">
                    '. 'pay_' . $data['payment_id'] . ' </a>
                    </td>
                    ' . $rowColumnStyle . 'INR ' . $data['amount'] . '</td>
                  </tr>';
        }

	@endphp
	<table class="table" border="0" cellpadding="0" cellspacing="0" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; width: 100%; background-color: #fafafa; margin-top: -100px !important; height: 100%; max-width: 800px; margin: 0 auto; font-size: 12px;"><tbody style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
		<tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; padding: 0; vertical-align: top; text-align: left;">
			@php
				echo $headerRow;
			@endphp
		</tr>
		@php
			echo $debitNoteTable;
		@endphp
	</tbody></table>

	<br> <br>
	Therefore an amount of INR {{ $baseAmount }} ({{ $baseAmountWords }}) is due to be recovered by Razorpay from you.
	<br> <br>
	Since there are no funds available in your account for us to recover the Amount, we request you to transfer the Amount
	<br>
	corresponding to the following payment IDs to our Nodal account within the next 5 working days at the following details:

	<br><br>

	<br>
		<b>
			<p>
				Account Name - <b>Razorpay Software Private Limited</b> </br>
				Account No. - <b>917020041206002 </b> </br>
				IFSC Code - <b>UTIB0001506</b>  </br>
				Bank Name-<b> Axis Bank Limited</b>  </br>
			</p>
		</b>


		<br>
		Please confirm in this email thread with the transaction reference number after transferring the amount.<b>Kindly note that no refunds to be initiated against these transactions.</b>
		<br><br>
		Kindly note that Razorpay reserves the right to initiate legal action, as it deems fit, at the appropriate forum against you upon your failure to remit the amount in favour of Razorpay within the stipulated timeline.
		<br><br>
		<b><i>NOTE: Please do not initiate a refund to the user directly as the same amount is already processed as a refund to the account holder by his/her issuing bank and recovery is pending for Razorpay</i></b>

</div>
<table>
	<tr><td style="height: 7px"></td></tr>
</table>
</body>
</html>

@section('content')

@endsection
