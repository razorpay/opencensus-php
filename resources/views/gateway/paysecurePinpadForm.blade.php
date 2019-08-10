<!DOCTYPE html>
<html>
<head>
    <title>Payment in progress • Razorpay</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
</head>
<script language="javascript" src="{{$data['merchantJsScript']}}" type="text/javascript">
</script>
<script language="javascript" type="text/javascript">

    redirected = false;
    //reads the response back from PaySecure
    function accu_FunctionResponse(strResponse) {

        // We receive only the response code in this case, which gets forwarded to the callback URL in the input
        // If the response code is ACCU999, it simply means that the PINPad lock was opened by the user, hence we
        // can ignore that trigger.
        if (strResponse != 'ACCU999' && strResponse != 'ISSUER999' && strResponse != "") {

            // Sometimes, this function gets invoked from their library with the value "ACCU000" after getting invoked with
            // the value "ISSUER000". Because of this, the callback request which was initiated with the value "ISSUER000"
            // gets cancelled and another callback request with value "ACCU000" is initiated. This is handled on the api side.
            // i.e, if we receive either ISSUER000 or ACCU000, the api marks the payment as success. However, to clearly identify
            // that the callback is from the iFrame flow, we're blocking the callback redirection from Js after we received
            // the first status response.

            // We do not make a check to see if the code is ACCU000, because in case in the future if they send ACCU000 only
            // then the callback requests would get blocked.
            if (redirected == false) {
                location.href = "{{$data['callbackUrl']}}" + "?AccuResponseCode=" + strResponse;
            }

            redirected = true;
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
