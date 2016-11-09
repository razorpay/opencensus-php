<!DOCTYPE html>
<html>
<head>
    <title></title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
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
            src: -apple-system, BlinkMacSystemFont,  "Segoe UI", "Roboto", "Oxygen", "Ubuntu", "Cantarell",  "Fira Sans", "Droid Sans", "Helvetica Neue", sans-serif;
            font-weight:normal;
            font-style:normal
        }

        @-webkit-keyframes spin {
          0% {
            -webkit-transform: scale(0.5);
                    transform: scale(0.5);
            opacity: 0;
            border-width: 8px;
          }

          20% {
            -webkit-transform: scale(0.6);
                    transform: scale(0.6);
            opacity: 0.8;
            border-width: 4px;
          }

          90% {
            -webkit-transform: scale(1);
                    transform: scale(1);
            opacity: 0;
          }
        }

        @-moz-keyframes spin {
          0% {
            -moz-transform: scale(0.5);
                 transform: scale(0.5);
            opacity: 0;
            border-width: 8px;
          }

          20% {
            -moz-transform: scale(0.6);
                 transform: scale(0.6);
            opacity: 0.8;
            border-width: 4px;
          }

          90% {
            -moz-transform: scale(1);
                 transform: scale(1);
            opacity: 0;
          }
        }

        @-o-keyframes spin {
          0% {
            -o-transform: scale(0.5);
               transform: scale(0.5);
            opacity: 0;
            border-width: 8px;
          }

          20% {
            -o-transform: scale(0.6);
               transform: scale(0.6);
            opacity: 0.8;
            border-width: 4px;
          }

          90% {
            -o-transform: scale(1);
               transform: scale(1);
            opacity: 0;
          }
        }

        @keyframes spin {
          0% {
            -webkit-transform: scale(0.5);
               -moz-transform: scale(0.5);
                 -o-transform: scale(0.5);
                    transform: scale(0.5);
            opacity: 0;
            border-width: 8px;
          }

          20% {
            -webkit-transform: scale(0.6);
                    transform: scale(0.6);
            opacity: 0.8;
            border-width: 4px;
          }

          90% {
            -webkit-transform: scale(1);
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
            -webkit-animation: spin 1.3s linear infinite;
               -moz-animation: spin 1.3s linear infinite;
                -ms-animation: spin 1.3s linear infinite;
                 -o-animation: spin 1.3s linear infinite;
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
            <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAQAAAAA5CAMAAADurgWFAAAAYFBMVEVSUlJvb2/+/v7FxcUpt9YHg7RqzeO4uLjy8vIjIyOg3+2YmJipqakoKCjs7Ozb29s9PT2EhIQes9TR0dHj4+P4+PjC6/Pi9foVk79Av9vw+vwiqcwfHx/7/PwtLS3///81hRISAAAAIHRSTlP/////////////////////////////////////////AFxcG+0AAAg6SURBVHgB5Vltc6wsDEXcVqmi+GLbbUH//798qoR4bOzM3W/P7h6nM/cKCDl5Sciq5R+h38LyiPhXAsLr2/LEBOj3S//xxAR8vfWX1+U5CSD1kwE8JQFfr/3l0r/qJyVAv/eXH/RvyxMSQOpfcQ1PSYB+6/sLGcDTEUDqJ3w9GwHk/YT+dXk+Aj529V/6zQCG7Be6rK6HMTwkAeT9aABaeWt+P8b7uWsej4Bw7S8MKoJaP5/D+OHhCHjrL4CrXt9ldv4DVukHI+DrivL37+u7xsx/wj0aAe8HB4hFUEUEWEPwxiYLcOGxCNDRAL6/oQiakrB52WxPOdYuveseKwaQAXy+RAIum3prI909J6N4tCCoX/vrKv8nGEBwNsqaw8TcEylhXRWmchzbdmiHYWjbZtqZ0ufg8VDGde0wlsAvzNPNOqrhlQ5lXNOW5H/iDCOPiC3h3QkBH/2P+b+8kAH0HySr9PYMSWnNbDxgdhXVB41TJ3BtHC3rzFmfYFVatYwuzmuWqXbrp9Wiaemg88yl3YzLSpKpMxbPYJzKY/yiE1SgPEWvTgh4vXx/vryQAVyoCKKUX8O8kTzAhdMsaQ2ZS2fsCWLmGDNjzIxIq5bOr9NMpovZbAPtMvi4tFbe4kbzFA/kRYL2ah3K4gn8TkAz2xVGBXViAKv4RwMY0qcnmEh5wddyc8wOpZ1P4IuV18obucpsNlBS0Ckyb1P0YZLhk3yEVc7zfbSy6d+EEKdaV57EgLdNfDYAvWkjfsEwhSCYLTc2vDXWm+3h45l1qDDzGTc/VAZFQ8Z4yKo+B3qdsvyWSbbW4woyzMZYPoOZIUNpR9PahVD7+GKUQVCT/GQAVATRzqaBiRmRsuXAyWVdVeX58IMim+edmzAbY038c7uW811+o6piW2bnneZptoKxNMGrKl/jXK0sElCvZ6i3M+SVgo+FZAHp+IPfmVa/74CfBwO4ogFgvteVAS0vEGBB6UqvIZ4xFTZJM64hi+Qv0to6vaCom0KJjVbepB3ztGBySTJxhqDS5DWFHR14QsdRv+6A3y9IQP+Oxm4KkqRpC2f4KxKtZ/YRebLYLTIVnk94TKt+gDN7V7flNE2l5phT7FKKu4g00HLP4S7gkOn0bwK+rv0lEUAGkIogwho41z+OXX79ikBl2cXQZjytqbbY6IRVVYYVNSR+i3Uu6s1m+44Dm4xACUYbcCWJQ9ULEhC2K+DRAF4TzwRLD8H6SshPlikUMymWKUrrwXpRQpNR3AbzADX4AbTMjAnwbEgoGZlnSgBHAj62FsD3MQR+gUcLGK9aUPCOwssKuXGWKByP+sQzc6wehQ+xHQOrDc8SR5gcOEdJ82rUTjq6OnaAPoUBRG0IeD9ng047j3WmHD8zV8hgrKRSVR6lHVBCPnNmMeuIyE3oDMya8krtZzhU7g17CoVeDCQqVT9RZDKA/qQTZDHDq6Ld7W5U3ls7789vz9S1TyEj/KnPfJewMTLrEK1uQjdnIwr1bAycgIZcAAJ8y6GXQjcQQB3QT1kEaZX2GduxSsJ1mPQiLwJ81KnzRGGtpbQsoU35nuP9KBNLDS7jeVap/CzAs8fdUkYP4RAI+FrlFxHgAyt+W2JbyAVxJzQ+PrB5RKMM6WMQ+gxSQhnvsRYlDzrG9klZPAMr5NcdIVB2JedEAt6SAURwJwgcrQPLpQyHX7eqbiMUJcx01GFOBV+DDo0k4WVqkvFexjuIzT6HM+bthtocZuc++QNNZBYTAYF6gCdVcAkSwzF9cdSlVcngS+wSCfeX0koJg4L4gJUFJk2e5TTHxwJmk80jARn9g0RBAt6PBoCdoOrYCUri2AwdAO8ZR/8NXZK/1mf6lBH9NN6XImmC1NpR/aB5tsXZFPhM1VgbVyAU9QAxBF6hFeiOXa+Wg8CEerCZrNew+rGW1gtphYQi3qO5t1AEcczMzS+vrGE2WECX2ZPaXbEBQBUMRZA/5vSJ76wj6QH+J/L7yOmIRRVFkLCbk3gfHJ9CxkytsO4UlS+aLdgJEqBfY93/yQ4gO0Hs8ngDpXHcHPwXbnS/Nm2FjTfQb5DxnnVcyQgQlpG/JqMjKoVjlSDg4+8cyCJMYL2gvsYcdalp2Bdw+bGqKRkNR3mQp4VboowPxDq+K7M9UXQQdNDsXMAUmmygXCQBqwHIKljjnbLGrE+AK+3sqrULMdTKsP9OGfPuZsPw7a4RmxX52rzIDDjKeRHEC9Z9imymSVVyDx7Luxn6X0gAxGqE+qMIescKgnM6lEK+hXRGDWEDbZja//kzIpukxVXGNefxHvqt1uA2PgvJYnjMG9A1rkdSEIqLoH/rBAXHlR5ZCCJpY6JIJOAHyoICPitFvEfOJXwXMC/tgU6mWPmOob42hV/2TlAPv4fGe4UfUR1054i3Nn/oTtexY2C6eEGQD8XxmpRIsNZ4l2viN87Tsk9Soai8ovQW32YdnbkRFgQJAKDeLtcV358R1xWpE2TdirnDhYV1hHJZpmz20bW9cVUYaKxZdDa7E1iywbFTxtNK452qW53ifZyXQ9JMZhg6XmNdN6QYNziTvmSzISg6s+iORfIFVAh6ewL9bU88TiAciNPh8Hoah+IH8RcqzSN6ChL4qdC0Q7FiOP6KBvNEWaNpM/wFLf5ulhd1UbTjpP/8AN8AZAz4n2Ny3GG+GaJWu0cCZOfgNox8Z7hPArTCKvh2lDNXDPdIgOgc3O5AlhLAvRKAnYPbwQlgWu6UANkfv01+E4ujZrlXAjrs7dyMijPovRLQQOf7dhScAO6WgBqvhrdiiItttdwtAdjbuRnNjJ2iP/EfO6X1cFyFdBYAAAAASUVORK5CYII=" alt="Razorpay" height='28px'>
        </div>
        <div class="loadingcard">
            <div id='message-text' class="center">
                Please accept collect request from <span class="bold">razorpay@icici</span> in your UPI app
            </div>
            <div id="spinner" class="shown">
                <div class="spin">
                    <div></div>
                </div>
                <div class="spin spin2">
                    <div></div>
                </div>
            </div>
            <div class="center"><span class="link" id='cancel_btn'>Cancel Payment<span></div>
        </div>
        <form id='form' method="POST">
        </form>
        <form id="form2" name="form2">
            <input type="hidden" name="type" value="{{$data['type']}}">
            <input type="hidden" name="gateway" value="{{$data['gateway']}}">
        </form>
    </div>

    <script type="text/javascript">
        // Async Payment data //
        var data = {!!utf8_json_encode($data)!!};
        // Async Payment data //
        var request_url = '{{$data['request']['url']}}';

        var key_id = '{{ BasicAuth::getPublicKey() }}';
        var cancel_url = '/v1/payments/{{$data["payment_id"]}}/cancel?key_id='+key_id;
        var callback_url = '/v1/payments/{{$data["payment_id"]}}/redirect_callback?key_id='+key_id;
        var gel =  document.getElementById.bind(document);

        var start_delay = 5000;
        var end_delay = 1000;
        var normalize_time = 60000;


        function each(iteratee, eachFunc, thisArg) {
          var i;
          if (arguments.length < 3) {
            thisArg = this;
          }
          if (iteratee) {
            if (iteratee.length) { // not using instanceof Array, to iterate over array-like objects
              for (i = 0; i < iteratee.length; i++) {
                eachFunc.call(thisArg, i, iteratee[i]);
              }
            } else {
              for (i in iteratee) {
                if (iteratee.hasOwnProperty(i)) {
                  eachFunc.call(thisArg, i, iteratee[i]);
                }
              }
            }
          }
        }

        function ajax (opts) {
          var xhr = new XMLHttpRequest();
          if (!opts.method) {
            opts.method = 'get';
          }
          xhr.open(opts.method, opts.url, true);

          each(
            opts.headers,
            function(header, value){
              xhr.setRequestHeader(header, value);
            }
          )

          if(opts.callback) {
            xhr.onreadystatechange = function() {
              if(xhr.readyState === 4 && xhr.status) {
                var json;
                try {
                  json = JSON.parse(xhr.responseText);
                } catch(e) {
                  json = {
                    xhr: {
                      status: xhr.status,
                      text: xhr.responseText
                    },
                    error: {
                      description: 'Parsing error'
                    }
                  };
                }
                opts.callback(json);
              }
            }
            xhr.onerror = function(){
              opts.callback({error: {description: 'Network error'}});
            }
          }
          xhr.send(opts.data || null);
          return xhr;
        }

        function defer (func, timeout) {
          if (arguments.length === 1) {
            timeout = 0;
          }
          if (arguments.length < 3) {
            setTimeout(func, timeout);
          } else {
            var args = arguments;
            setTimeout(function(){
              func.apply(null, Array.prototype.slice.call(args, 2));
            }, timeout);
          }
        }

        var delay = start_delay;
        var delta = 400;

        function recurseAjax(url, callback, continueTill, mature) {
          defer(function() {
            var xhr = ajax({
              url: url,
              callback: function(response) {
                if (delay <= end_delay){
                    delay = end_delay;
                } else {
                    delay -= delta;
                }

                if (continueTill.call(xhr, response)) {
                  recurseAjax(url, callback, continueTill, true);
                } else {
                  callback(response);
                }
              }
            })
            if (!mature) {
              continueTill.call(xhr);
            }
          }, delay)
        }

        recurseAjax(request_url, function(response){
            /*
             * Redirecting to callback_url regardless of whether payment is
             * succesful or not
             */
            gel('form').setAttribute('action', callback_url);
            gel('form').submit();
        }, function(response){
            return response && response.status;
        })

        gel('cancel_btn').onclick = function () {
            ajax({
                url: cancel_url,
                callback: function(){
                    gel('form').setAttribute('action', callback_url);
                    gel('form').submit();
                }
            })
        }

    </script>

</body>
</html>