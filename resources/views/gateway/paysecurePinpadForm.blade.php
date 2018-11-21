<!DOCTYPE html>
<html>
<head>
    <title>Payment in progress • Razorpay</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
</head>
<script language="javascript" src="{{$data['merchantJsScript']}}" type="text/javascript">
</script>
<script language="javascript" type="text/javascript"> //reads the response back from PaySecure
    function accu_FunctionResponse(strResponse){
        // alert("this is the response that was received " + strResponse);
        if (strResponse != 'ACCU999' && strResponse != "")
        {
            location.href = "{{$data['callbackUrl']}}" + "?AccuResponseCode=" + strResponse;
        }
    }
    //Actual code has been given in the below table for Accu_FunctionResponse
    //checks browser compatibility Acculynk.browserCheck();
    //preps the PIN Pad for opening
    Acculynk.createForm("{{$data['guid']}}", "{{$data['lastFourDigits']}}", "{{$data['modulus']}}", "{{$data['exponent']}}");
    //these argument values needs to be replaced with actual g,c,m,e
    //opens the authentication and PIN Pad for consumer
    Acculynk.PINPadLoad();
    //closes the PIN Pad
    Acculynk._modalHide();
</script>
<body>
@include('partials.loader')
<center>
    <div id="accu_screen" style="display: none;"></div>
    <div id="accu_keypad" style="display: none;"></div>
    <div id="accu_form" style="display: none;"></div>
    <div id="accu_loading" style="display: none;"></div>
    <div id="accu_issuer" style="display: none;"></div>
</center>
</body>
<script src='{{$data['merchantJsScript']}}'></script>

<script type="text/javascript">

</script>
