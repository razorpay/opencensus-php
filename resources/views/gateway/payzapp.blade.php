<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Razorpay Payzapp</title>
<script>
{{-- WebSDK Configurations --}}
_wIapDefaults = {

{{-- optional, default value is false. By opting for manual trigger, you will require to trigger our api --}}
  wIapManualTrigger: true,

{{-- mandatory if wIapManualTrigger is false, default value is 'wIapBtn' --}}
  wIapButtonId: 'wIapBtn',

{{-- Provide wibmo environment domain. default value is for production that is 'www.wibmo.com' --}}
  wIapWibmoDomain: "{{ $request['url'] }}",

{{-- Default false. Pass true, If you want IAP response to be passed to your web page through javascript call --}}
  wIapInlineResponse: false,

{{-- Mandatory if wIapInlineResponse is true. --}}
  wIapInlineResponseHandler: 'handleWibmoIapResponse',

{{-- mandatory if wIapInlineResponse is false --}}
  wIapReturnUrl: "{{ $request['callback_url'] }}",
};

_wIapInitRequestJSON = JSON.parse(<?= "'".json_encode($request['content'])."'" ?>);

</script>
<script src="https://{{ $request['url'] }}/v1/wIAP.js"></script>
</head>
<body>
<script type="text/javascript">
wIAP.doIAPWPay(_wIapInitRequestJSON, _wIapDefaults.wIapReturnUrl);
</script>
</body>
</html>
