<!doctype html>
<html style="height:100%;width:100%;">
<head>
    <title>Processing, Please Wait...</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>
        try{
            var payment_id = "{{$data['payment_id']}}";
            if (typeof(CheckoutBridge) !== 'undefined' && typeof(CheckoutBridge.setPaymentID) === 'function') {
                CheckoutBridge.setPaymentID(payment_id);
            } else if(window.opener && typeof window.opener.setPaymentID === 'function'){
                opener.setPaymentID(payment_id);
            }
        } catch(e){}
    </script>
    <style>
        body {
            font-family: sans;
            display: flex;
            height: 100%;
            margin: 0;
            flex-direction: column;
            text-align: center;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #414141;
            padding-bottom: 40px;
            box-sizing: border-box;
        }
    </style>
    @if (isset($data['production']) and $data['production'] === true)
        <script>
            var events = {
                page: 'gateway_postform',
                props: {
                    @if (isset($data['payment_id']))
                    payment_id: '{{$data['payment_id']}}',
                    @endif
                    @if (isset($data['merchant_id']))
                    merchant_id: '{{$data['merchant_id']}}',
                    @endif
                },
                load: true,
                unload: true
            }
        </script>
        @include('partials.track')
    @endif
</head>
<body onload="window.location.href = '{{$data['request']['url']}}'">
<img src="https://cdn.razorpay.com/app/phonepe.svg" width="120">
<p>Loading...</p>
<form id="form1" name="form1" method="get" action="{{$data['request']['url']}}" onsubmit="return true;">
</form>
<form id="form2" name="form2">
    <input type="hidden" name="type" value="{{$data['type']}}">
    <input type="hidden" name="gateway" value="{{$data['gateway']}}">
</form>
</body>
</html>
