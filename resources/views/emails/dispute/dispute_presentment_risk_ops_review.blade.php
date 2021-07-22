<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width">
</head>
<body style="width: 100% !important; min-width: 100%; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">

<div style="padding:40px">

    <p style="color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px; margin-bottom: 10px;">
        Hello,</p>

    <p style="color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px; margin-bottom: 10px;">
        Merchant <b> {{ $dispute['merchant_id'] }} ) </b> has accepted the dispute raised against
        payment ( <a href="{{ $dashboard_hostname . 'admin/entity/payment/' . $dispute['payment_id'] }}" target="_blank"
                     style="color: #2ba6cb; text-decoration: none;">{{ $dispute['payment_id'] }}</a> ) via Dispute
        Presentment API. <br>
        As a result, the dispute has been marked as {{ $dispute['status'] }}. <br>
        <br>
        However, there was an error in recovering the amount due to and requires a risk_ops
        review.<br>
        Reason: <b>{{$reason_for_review}}</b> <br>
        Message: <b>{{$review_message}}</b><br>
        The details are given below : <br>
    </p>

    <p style="color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px; margin-bottom: 10px;">
        <strong> Dispute Details <br> </strong>

        Dispute ID : <a href="{{ $dashboard_hostname . 'admin/entity/dispute/' . $dispute['id'] }}" target="_blank"
                        style="color: #2ba6cb; text-decoration: none;">{{ $dispute['id'] }}</a> <br>
        Merchant ID : <a href="{{ $dashboard_hostname . 'admin/merchants/' . $dispute['merchant_id'] }}" target="_blank"
                         style="color: #2ba6cb; text-decoration: none;">{{ $dispute['merchant_id'] }}</a> <br>
        Type : {{ $dispute['phase'] }} <br>
        Status : {{ $dispute['status'] }} <br>
        Amount disputed : {{ $dispute['amount'] }} <br>
        Amount deducted : {{ $dispute['amount_deducted'] }} <br>
        Currency : {{ $dispute['currency'] }} <br>
        Reason code : {{ $dispute['reason_code'] }} <br>
        Gateway case ID : {{ $dispute['gateway_dispute_id'] }} <br>
    </p>

    <p style="color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px; margin-bottom: 10px;">
        <strong> Payment Details <br> </strong>

        Payment ID : <a href="{{ $dashboard_hostname . 'admin/entity/payment/' . $dispute['payment_id'] }}"
                        target="_blank" style="color: #2ba6cb; text-decoration: none;">{{ $dispute['payment_id'] }}</a>
        <br>
        Amount : {{ $payment['amount'] }} <br>
        Currency : {{ $payment['currency'] }} <br>
        Method: {{$payment['method']}} <br>
        Gateway: {{$payment['gateway']}} <br>
    </p>

</div>

</body>
</html>
