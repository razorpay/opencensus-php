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

        .pad {
            padding-top: 20px;
            text-align: center;
        }

        #spinner {
            display: none;
            border: 5px solid #888;
            border-radius: 30px;
            height: 15px;
            margin: 0 0 -5px 15px;
            opacity: 0;
            width: 15px;
            animation: pulsate 1s ease-out;
            animation-iteration-count: infinite;
        }

        #spinner.shown {
            display: inline-block;
        }

        #content {
            max-width: 480px;
            margin: 0 auto;
            background: #FBFBFB;
            padding: 24px;
            box-sizing: border-box;
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
            padding: 12px;
            background: #00BE70;
            color: #fff;
            font-size: 28px;
            border: 0;
            width: 100%;
            margin: 40px auto 20px;
            letter-spacing: 1.5px;
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
        <div id="banner">
            <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAI0AAAAgCAMAAAAYAgunAAAAMFBMVEXT46mRuSr7/PeGsxakxk/I3JXz9+fr8ti30nTj7sqyzmqcwD+sy17d6b2+1oL///+7L4YrAAAAEHRSTlP///////////////////8A4CNdGQAAA+xJREFUeNrNV4tuIyEMNAbMw0D+/29vbEi2Fylt79RInSgrwMaeHWykpdv/gXMY5fbT+F82smL6PWyYtM42C/8KNkDTCH1+C5v5W9iwwdiMn2fDhm9QuIYyMxGt3rP8LBuW1or9yqeMuMBN9lBKTgG62I6fBZWujjTyZw3SBlyyO3BOqY/wlg7vMQYHXna+9ssRSC5OGVFXTm9hE6Jmw4L4/bU462LTjM16F5t+CqNHbSwFEGEs4GHw0ZM2oa6kXY7VN1wDB5/ZY+phr4iwupH54yLY1DOhEGZbfaAqcmMmPLal5Mx/s4lBFxU386xjjDVZch+jnk3csi0/nBqMsJLstHPdfTlj68m/8gc2DWxIQ0oaopL0MMo21KjyxCaGvGNIDdEQ1ogOJRgka9wYlotzONPeMPM9AX/NImon4mGR9YlNI5pz0kBR5BDOe2ocH7Vpnvf0l9VdXVWNUF81Wav5ahh72V6pJRh9GhY7N105d/hO7jGQhyJw/cAmgw0zu/oYTo10J51vL7RhhCQRbLC8IhN56SZIUZuIEPJDnIwkhWF1rmWY9MxlBUSBbcnpE7IqFkOhD13SNM6SYpVDeprvxebSRpDdF7FGNs/gaVqk5mRXgCPXGPbUG6XpCTQVGYxAMRsiNFe6A8NlBOM2J1YDSQ3J3VZUeaUN2KgPKmK5wIhyg0Kn5vzNuTtlAKEmm0I0gawgUhDBSl1AxKgdBF2F0VMJADPaJ4cECM23F3XzTTbhsKmHTdCEn0awuRdOwcTrzbWpawoShaB3NlOR8pTND2sTNdgvaBZzybBSRJpTN/uiYtRVna3Z+RHLgGmXzWGjnnDqxabc2fR/0kYzZTIUturpgo0W4OopwPIXT2L9BG6W3svG/IAOqtQxtM55rU17NIRVsXD9WxuNQ3hj50qNecDnmc1poxYCMWyWM8V+jgcweX3Qy2s2cMZToLU1/PSeurSBnqYsrCJWvlztIBoU4ovNvQOpiLRqnWjbOk+c57nmLsCNX7PBc99+y28/D1se2tgtAOtaq6Yxd+SUu1cnhv1iY7dVGkNBUZx1GHruCiF9JgOIHjYDItzZ4D323Y9HmmxsQrm04dJNZWCfd0mYRa9OSsbpojNcAmv2x/Eob9us6sag1n4bslJ1M+kQdxr+viUPS5ZqYzO60x6UyxrSKr4nYbarsyHyBS6TcqYmvGe0Lu1YmhtncetZK7JplXK2+MK+Re+e7nQGvHch1MMMXxztwJiYn78GBP/HjGKkZyN//7viU+uVxnvv6y+Yoqbgu+H1Kl+wYVoJ0vCbuRTqGtHZX7CRgVLLb5eGUvSL4EttKri8HS3pmOzDP+UnwTXbEK2hAAAAAElFTkSuQmCC" alt="" height='28px'>

            <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAQAAAAA5CAMAAADurgWFAAAAYFBMVEVSUlJvb2/+/v7FxcUpt9YHg7RqzeO4uLjy8vIjIyOg3+2YmJipqakoKCjs7Ozb29s9PT2EhIQes9TR0dHj4+P4+PjC6/Pi9foVk79Av9vw+vwiqcwfHx/7/PwtLS3///81hRISAAAAIHRSTlP/////////////////////////////////////////AFxcG+0AAAg6SURBVHgB5Vltc6wsDEXcVqmi+GLbbUH//798qoR4bOzM3W/P7h6nM/cKCDl5Sciq5R+h38LyiPhXAsLr2/LEBOj3S//xxAR8vfWX1+U5CSD1kwE8JQFfr/3l0r/qJyVAv/eXH/RvyxMSQOpfcQ1PSYB+6/sLGcDTEUDqJ3w9GwHk/YT+dXk+Aj529V/6zQCG7Be6rK6HMTwkAeT9aABaeWt+P8b7uWsej4Bw7S8MKoJaP5/D+OHhCHjrL4CrXt9ldv4DVukHI+DrivL37+u7xsx/wj0aAe8HB4hFUEUEWEPwxiYLcOGxCNDRAL6/oQiakrB52WxPOdYuveseKwaQAXy+RAIum3prI909J6N4tCCoX/vrKv8nGEBwNsqaw8TcEylhXRWmchzbdmiHYWjbZtqZ0ufg8VDGde0wlsAvzNPNOqrhlQ5lXNOW5H/iDCOPiC3h3QkBH/2P+b+8kAH0HySr9PYMSWnNbDxgdhXVB41TJ3BtHC3rzFmfYFVatYwuzmuWqXbrp9Wiaemg88yl3YzLSpKpMxbPYJzKY/yiE1SgPEWvTgh4vXx/vryQAVyoCKKUX8O8kTzAhdMsaQ2ZS2fsCWLmGDNjzIxIq5bOr9NMpovZbAPtMvi4tFbe4kbzFA/kRYL2ah3K4gn8TkAz2xVGBXViAKv4RwMY0qcnmEh5wddyc8wOpZ1P4IuV18obucpsNlBS0Ckyb1P0YZLhk3yEVc7zfbSy6d+EEKdaV57EgLdNfDYAvWkjfsEwhSCYLTc2vDXWm+3h45l1qDDzGTc/VAZFQ8Z4yKo+B3qdsvyWSbbW4woyzMZYPoOZIUNpR9PahVD7+GKUQVCT/GQAVATRzqaBiRmRsuXAyWVdVeX58IMim+edmzAbY038c7uW811+o6piW2bnneZptoKxNMGrKl/jXK0sElCvZ6i3M+SVgo+FZAHp+IPfmVa/74CfBwO4ogFgvteVAS0vEGBB6UqvIZ4xFTZJM64hi+Qv0to6vaCom0KJjVbepB3ztGBySTJxhqDS5DWFHR14QsdRv+6A3y9IQP+Oxm4KkqRpC2f4KxKtZ/YRebLYLTIVnk94TKt+gDN7V7flNE2l5phT7FKKu4g00HLP4S7gkOn0bwK+rv0lEUAGkIogwho41z+OXX79ikBl2cXQZjytqbbY6IRVVYYVNSR+i3Uu6s1m+44Dm4xACUYbcCWJQ9ULEhC2K+DRAF4TzwRLD8H6SshPlikUMymWKUrrwXpRQpNR3AbzADX4AbTMjAnwbEgoGZlnSgBHAj62FsD3MQR+gUcLGK9aUPCOwssKuXGWKByP+sQzc6wehQ+xHQOrDc8SR5gcOEdJ82rUTjq6OnaAPoUBRG0IeD9ng047j3WmHD8zV8hgrKRSVR6lHVBCPnNmMeuIyE3oDMya8krtZzhU7g17CoVeDCQqVT9RZDKA/qQTZDHDq6Ld7W5U3ls7789vz9S1TyEj/KnPfJewMTLrEK1uQjdnIwr1bAycgIZcAAJ8y6GXQjcQQB3QT1kEaZX2GduxSsJ1mPQiLwJ81KnzRGGtpbQsoU35nuP9KBNLDS7jeVap/CzAs8fdUkYP4RAI+FrlFxHgAyt+W2JbyAVxJzQ+PrB5RKMM6WMQ+gxSQhnvsRYlDzrG9klZPAMr5NcdIVB2JedEAt6SAURwJwgcrQPLpQyHX7eqbiMUJcx01GFOBV+DDo0k4WVqkvFexjuIzT6HM+bthtocZuc++QNNZBYTAYF6gCdVcAkSwzF9cdSlVcngS+wSCfeX0koJg4L4gJUFJk2e5TTHxwJmk80jARn9g0RBAt6PBoCdoOrYCUri2AwdAO8ZR/8NXZK/1mf6lBH9NN6XImmC1NpR/aB5tsXZFPhM1VgbVyAU9QAxBF6hFeiOXa+Wg8CEerCZrNew+rGW1gtphYQi3qO5t1AEcczMzS+vrGE2WECX2ZPaXbEBQBUMRZA/5vSJ76wj6QH+J/L7yOmIRRVFkLCbk3gfHJ9CxkytsO4UlS+aLdgJEqBfY93/yQ4gO0Hs8ngDpXHcHPwXbnS/Nm2FjTfQb5DxnnVcyQgQlpG/JqMjKoVjlSDg4+8cyCJMYL2gvsYcdalp2Bdw+bGqKRkNR3mQp4VboowPxDq+K7M9UXQQdNDsXMAUmmygXCQBqwHIKljjnbLGrE+AK+3sqrULMdTKsP9OGfPuZsPw7a4RmxX52rzIDDjKeRHEC9Z9imymSVVyDx7Luxn6X0gAxGqE+qMIescKgnM6lEK+hXRGDWEDbZja//kzIpukxVXGNefxHvqt1uA2PgvJYnjMG9A1rkdSEIqLoH/rBAXHlR5ZCCJpY6JIJOAHyoICPitFvEfOJXwXMC/tgU6mWPmOob42hV/2TlAPv4fGe4UfUR1054i3Nn/oTtexY2C6eEGQD8XxmpRIsNZ4l2viN87Tsk9Soai8ovQW32YdnbkRFgQJAKDeLtcV358R1xWpE2TdirnDhYV1hHJZpmz20bW9cVUYaKxZdDa7E1iywbFTxtNK452qW53ifZyXQ9JMZhg6XmNdN6QYNziTvmSzISg6s+iORfIFVAh6ewL9bU88TiAciNPh8Hoah+IH8RcqzSN6ChL4qdC0Q7FiOP6KBvNEWaNpM/wFLf5ulhd1UbTjpP/8AN8AZAz4n2Ny3GG+GaJWu0cCZOfgNox8Z7hPArTCKvh2lDNXDPdIgOgc3O5AlhLAvRKAnYPbwQlgWu6UANkfv01+E4ujZrlXAjrs7dyMijPovRLQQOf7dhScAO6WgBqvhrdiiItttdwtAdjbuRnNjJ2iP/EfO6X1cFyFdBYAAAAASUVORK5CYII=" alt="Razorpay" height='28px' class='right'>
        </div>
        <div id='paymentdetails' class="card">
            <div id="contact">{{$data['contact']}}</div>
            <div id="amount" class="right">Rs. {{$data['amount']}}</div>
        </div>
        <form class="card" id="otpform" name="otpform" action="{{$data['request']['url']}}" method="post" onsubmit="return false;">
            <div id="prompt" class="center">We have sent an OTP to your registered mobile Number ({{$data['contact']}})</div>
            <div>
                <input id='otp' type="text" name="otp" maxlength="6" />
            </div>
            <div class="pad">
                <span id="resend">Resend OTP</span><span id="spinner"></span>
                <span id="addfunds">Add Funds</span>
            </div>
            <input type="hidden" name="type" value="otp">
            <div>
                <button type="submit" id='submitotp'>CONFIRM</button>
            </div>
        </form>
        <form id='mirror' name='mirror'>
        </form>
        <form id="form2" name="form2">
            <input type="hidden" name="type" value="{{$data['type']}}">
            <input type="hidden" name="gateway" value="{{$data['gateway']}}">
        </form>
    </div>

    <script type="text/javascript">
        var request_url = '{{$data['request']['url']}}';

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

        function resendOTP () {

            var xhr;
            if (window.XMLHttpRequest) {
                xhr = new XMLHttpRequest();
            } else {
                xhr = new ActiveXObject("Microsoft.XMLHTTP");
            }

            var url = '/v1/payments/{{$data["payment_id"]}}/otp_resend?key_id=' + key_id;

            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    var res = JSON.parse(xhr.responseText);
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

            if(!gel('otp').value){
                alert('please enter the OTP.');
            }

            if (window.XMLHttpRequest) {
                xhr = new XMLHttpRequest();
            } else {
                xhr = new ActiveXObject("Microsoft.XMLHTTP");
            }

            // var url = '/v1/payments/{{$data["payment_id"]}}/otp_resend?key_id=' + key_id;
            var url = request_url;
            gel('spinner').className = "shown";

            xhr.onreadystatechange = function() {
                try{
                    var res = JSON.parse(xhr.responseText);
                } catch (e){
                }

                if (xhr.readyState == 4) {

                    if(xhr.status === 400) {
                        if (res.error.action==='RETRY') {
                            gel('prompt').innerHTML = 'Entered OTP was incorrect. Re-enter to proceed.'
                            return;
                        } else if (res.error.action === 'TOPUP') {
                            gel('prompt').innerHTML = 'Insufficient balance';
                            gel('addfunds').className = 'shown';
                            gel('resend').remove();
                            gel('submitotp').remove();
                            gel('otp').remove();
                            return;
                        }
                    }

                    gel('mirror').setAttribute('action', '/v1/payments/{{$data["payment_id"]}}/redirect?key_id=' + key_id);
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