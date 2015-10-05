<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Razorpay Test Bank Choice</title>

<script>
// WebSDK Configurations
_wIapDefaults = {
    wIapManualTrigger           : true,                                         // optional, default value is false. By opting for manual trigger, you will require to trigger our api
    wIapButtonId                : 'wIapBtn',                                    // mandatory if wIapManualTrigger is false, default value is 'wIapBtn'
    wIapWibmoDomain             : "<?= $data['request']['url'] ?>",             // Provide wibmo environment domain. default value is for production that is 'www.wibmo.com'
    wIapInlineResponse          : false,                                        // Default false. Pass true, If you want IAP response to be passed to your web page through javascript call
    wIapInlineResponseHandler   : 'handleWibmoIapResponse',                     // Mandatory if wIapInlineResponse is true.
    wIapReturnUrl               : "<?= $data['request']['callback_url'] ?>",    // mandatory if wIapInlineResponse is false
};

_wIapInitRequestJSON = JSON.parse(<?= "'".json_encode($data['request']['content'])."'" ?>);

</script>

<script src="https://<?= $data['request']['url']?>/v1/wIAP.js"></script>

</head>

<body>
<script type="text/javascript">
wIAP.doIAPWPay(_wIapInitRequestJSON, _wIapDefaults.wIapReturnUrl);
</script>

</body>
</html>
