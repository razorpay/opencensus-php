<!DOCTYPE html>
<html>
<head>
    <title>Payment in progress • Razorpay</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
</head>
<script language="javascript" src="{{$data['merchantJsScript']}}" type="text/javascript">
</script>
<script language="javascript" type="text/javascript">

    //reads the response back from PaySecure
    function accu_FunctionResponse(strResponse) {
        // We receive only the response code in this case, which gets forwarded to the callback URL in the input
        // If the response code is ACCU999, it simply means that the PINPad lock was opened by the user, hence we
        // can ignore that trigger.
        if (strResponse != 'ACCU999' && strResponse != 'ISSUER999' && strResponse != "") {
            location.href = "{{$data['callbackUrl']}}" + "?AccuResponseCode=" + strResponse;
        }
    }

    window.onload = function(e) {
        // Checks browser
        Acculynk.browserCheck();

        // Create the PINPad form passing the data from input
        Acculynk.createForm("{{$data['guid']}}", "{{$data['lastFourDigits']}}", "{{$data['modulus']}}", "{{$data['exponent']}}");

        // Opens the PIN Pad
        Acculynk.PINPadLoad();

        // Closes the PIN Pad
        Acculynk._modalHide();
    }

</script>
<body>
@include('partials.loader')

{{--These are being used by the library--}}
<center>
    <div id="accu_screen" style="display: none;"></div>
    <div id="accu_keypad" style="display: none;"></div>
    <div id="accu_form" style="display: none;"></div>
    <div id="accu_loading" style="display: none;"></div>
    <div id="accu_issuer" style="display: none;"></div>
</center>
</body>
