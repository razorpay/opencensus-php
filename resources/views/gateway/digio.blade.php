<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Razorpay E-Mandate</title>
    <script>
// data //
var data = {!!utf8_json_encode($request)!!};
// data //

        window.RZP = {
            callback_url: "{{ $request['callback_url'] }}",
            content: {!! $request['content'] !!}
        };

        window.RZP.options = {
            environment: window.RZP.content.environment,
            redirection_url: window.RZP.callback_url,
            redirect_url: window.RZP.callback_url,

//            "callback": function (result) {
//                document.write(result.message);
//                window.location.href = window.RZP.callback_url + "?digio_doc_id=" + result.digio_doc_id;
//            },

            logo: "https://razorpay.com/assets/razorpay-logo-95e9447029.svg"
        };

    </script>
    <script src="https://app.digio.in/sdk/v1/digio.js" type="application/javascript"></script>
</head>
<body>
<script type="text/javascript">
    var digio = new Digio(window.RZP.options);

    digio.init();
    digio.esign(window.RZP.content.signer_id, window.RZP.content.identifier);
</script>
</body>
</html>