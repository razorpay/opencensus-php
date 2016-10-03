<!DOCTYPE html>
<html>
<head>
    <title></title>
    <style>
        @keyframes pulsate {
            0% {
              transform: scale(.1);
              opacity: 0.0;
            }
            50% {
              opacity: 1;
            }
            100% {
              transform: scale(1.2);
              opacity: 0;
            }
        }

        * {
            margin: 0;
            padding: 0;
        }

        @font-face {
            font-family:'lato';
            src: url("https://cdn.razorpay.com/lato3.woff2") format('woff');
            font-weight:normal;
            font-style:normal
        }

        @keyframes spin {
          0% {
            transform: scale(0.5);
            opacity: 0;
            border-width: 8px;
          }

          20% {
            transform: scale(0.6);
            opacity: 0.8;
            border-width: 4px;
          }

          90% {
            transform: scale(1);
            opacity: 0;
          }
        }

        html, body {
            height: 100%;
            font-family: 'lato';
        }

        .right {
            float: right;
        }

        .card {
            padding: 24px;
            background: #fff;
            margin: 24px 0;
        }

        .center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        .pad {
            padding-top: 15px;
            text-align: center;
        }

        .red {
            color: red;
        }

        .spin {
            width: 60px;
            height: 60px;
            margin: 0 auto;
        }

        .spin div {
            width: 100%;
            height: 100%;
            vertical-align: middle;
            display: inline-block;
            border-radius: 50%;
            border: 4px solid #29b7d6;
            animation: spin 1.3s linear infinite;
            box-sizing: border-box;
            opacity: 0;
        }

        .spin2 {
            margin: -60px auto 20px;
        }

        .spin2 div {
            animation-delay: 0.65s;
        }

        #resend-text {
            margin-top: 10px;
            text-align: center;
        }

        #spinner {
            display: none;
            margin: 40px 0
        }

        #spinner.shown {
            display: block;
        }

        #content {
            max-width: 480px;
            margin: 0 auto;
            background: #FBFBFB;
            padding: 24px;
            box-sizing: border-box;
            position: relative;
            /*border: 1px solid #adadad;*/
        }

        .loadingcard {
            background: white;
            padding: 40px 0 30px;
        }

        #overlay.shown {
            display: block;
        }

        #message-text {
            font-size: 20px;
            padding: 0 25px;
        }

        #banner {
            padding: 24px;
        }

        #paymentdetails {
            font-size: 22px;
            color: #616161;
        }

        #amount:after {
            content: "";
            clear: both
        }

        #contact {
            display: inline-block;
        }

        #prompt {
            line-height: 36px;
            font-size: 18px;
            min-height: 76px;
        }

        #otpform {
            min-height: 280px;
        }

        #otp {
            font-size: 28px;
            width: 120px;
            margin: 10px auto;
            border: 0;
            border-bottom: 2px solid #ddd;
            letter-spacing: 1px;
            text-align: center;
            outline: none;
            padding: 10px;
            display: block;
        }

        button {
            display: block;
            padding: 12px 40px;
            background: #1aace5;
            color: #fff;
            font-size: 22px;
            border: 0;
            margin: 40px auto 20px;
        }

        .link {
            border-bottom: 1px solid #777;
            padding: 5px;
            cursor: pointer;
            display: inline-block;
        }

        #resend, #addfunds {
            font-size: 16px;
            cursor: pointer;
            border-bottom: 1px solid #00BE70;
            padding-bottom: 4px;
            line-height: 30px;
        }

        #addfunds {
            display: none;
        }

        #addfunds.shown {
            display: inline-block;
        }

    </style>
</head>
<body>
    <div id='content'>
        <div id="banner" class='center'>
            <img src="data:image/png;base64,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" alt="Razorpay" height='28px'>
        </div>
        <div class="loadingcard">
            <div id='message-text' class="center">
                Please accept collect request from <span class="bold">razorpay@icici</span> on your UPI app
            </div>
            <div id="spinner" class="shown">
                <div class="spin">
                    <div></div>
                </div>
                <div class="spin spin2">
                    <div></div>
                </div>
            </div>
            <div class="center"><span class="link">Cancel Payment<span></div>
        </div>
    </div>

    <script type="text/javascript">
        var request_url = '{{$data['request']['url']}}';

        // var payment_id = '{{$data["payment_id"]}}';

        var cancel_url = '/v1/payments/{{$data["payment_id"]}}/cancel?key_id=' + key_id;

        var reg = new RegExp('[?&]key_id=([^&#]*)', 'i');
        var key_id = reg.exec(request_url);
        key_id = key_id ? key_id[1] : null;

        var gel =  document.getElementById.bind(document);
        function enterOTP(e){
            if(!e) { return '' }

            var which = e.which;
            if(typeof which !== 'number'){
               which = e.keyCode;
            }

            if(e.metaKey || e.ctrlKey || e.altKey || which <= 18) {
                return false
            }
            var character = String.fromCharCode(which);
            if(/[0-9]/.test(character)){
              return character;
            }
            e.preventDefault();
            return false;
        }

        function showMessage (message) {
            gel('overlay').className = 'shown';
            gel('message-text').innerHTML = message.text;
            gel('spinner').className = message.loader ? "shown" : '';
        }

        function hideMessage (prompt) {
            if (prompt) {
                gel('prompt').innerHTML = prompt;
            }

            gel('overlay').className = '';
        }

        function resendOTP () {
            var xhr;
            if (window.XMLHttpRequest) {
                xhr = new XMLHttpRequest();
            } else {
                xhr = new ActiveXObject("Microsoft.XMLHTTP");
            }

            showMessage({
                text: 'Resending OTP',
                loader: true
            })

            var url = '/v1/payments/{{$data["payment_id"]}}/otp_resend?key_id=' + key_id;

            xhr.onreadystatechange = function() {
                gel('otp').value = '';
                if (xhr.readyState == 4 && xhr.status == 200) {
                    var res = JSON.parse(xhr.responseText);
                    // hideMessage("An OTP has been sent to");
                }
            }

            xhr.open('POST', url);
            xhr.send();
        }

        function addFunds() {
            gel('mirror').setAttribute('action', '/v1/payments/{{$data["payment_id"]}}/topup?key_id=' + key_id);
            gel('mirror').setAttribute('method', 'POST');
            gel('mirror').submit();
        }

        function onSubmit(e){
            var xhr;
            gel('submitotp').disable = true;

            if (window.XMLHttpRequest) {
                xhr = new XMLHttpRequest();
            } else {
                xhr = new ActiveXObject("Microsoft.XMLHTTP");
            }

            var url = request_url;

            showMessage({
                text: 'Verifying OTP',
                loader: true
            })

            xhr.onreadystatechange = function() {
                try{
                    var res = JSON.parse(xhr.responseText);
                } catch (e){
                }

                if (xhr.readyState == 4) {
                    gel('otp').value = '';
                    if(xhr.status === 400) {
                        hideMessage();
                        if (res.error.action==='RETRY') {
                            gel('prompt').innerHTML = '<span class="red">Entered OTP was incorrect. Re-enter to proceed. <span>'
                            return
                        } else if (res.error.action === 'TOPUP') {
                            gel('prompt').innerHTML = 'Insufficient balance';
                            gel('addfunds').className = 'shown';
                            gel('resend').remove();
                            gel('submitotp').remove();
                            gel('otp').remove();
                            return;
                        }
                    }

                    gel('mirror').setAttribute('action', '/v1/payments/{{$data["payment_id"]}}/redirect_callback?key_id=' + key_id);
                    gel('mirror').setAttribute('method', 'POST');
                    gel('mirror').submit();
                    gel('submitotp').disable = false;
                }
            }

            xhr.open('POST', url);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.send('otp='+gel('otp').value);
            return e.preventDefault();
        }

        gel('otp').addEventListener('keydown', enterOTP);
        gel('resend').addEventListener('click', resendOTP);
        gel('otpform').addEventListener('submit', onSubmit);
        gel('addfunds').addEventListener('click', addFunds);
    </script>

</body>
</html>