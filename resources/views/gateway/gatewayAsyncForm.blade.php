<!DOCTYPE html>
<html>
<head>
    <title></title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>

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

        html {
            background: #FBFBFB;
        }

        .card {
            padding: 24px;
            background: #fff;
            margin: 24px 0;
        }

        .center {
            text-align: center;
        }

        .red {
            color: red;
        }

        .green {
            color: #11b700;
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

        #spinner {
            padding: 15px 0 10px;
        }

        .more-pad {
            padding-bottom: 40px;
        }

        #content {
            max-width: 480px;
            margin: 0 auto;
            padding: 24px;
            box-sizing: border-box;
            position: relative;
            /*border: 1px solid #adadad;*/
        }

        .loadingcard {
            background: white;
            padding: 40px 0 0;
            border-radius: 2px;
            box-shadow: 0px 4px 20px rgba(0,0,0,0.10);
        }

        #message-txt {
            font-size: 20px;
            padding: 0 25px 25px;
        }

        #message-txt div {
            font-size: 16px;
            margin-top: 12px;
            opacity: 0.8;
        }

        #banner {
            padding: 24px;
        }

        .buttons div {
            padding: 15px;
        }

        #retry_btn {
            display: block;
            background: #3395ff;
            color: #fff;
            border: 0;
            border-bottom-left-radius: 2px;
            border-bottom-right-radius: 2px;
            cursor: pointer;
        }

        #cancel_btn {
            color: #3395ff;
            margin-top: 40px;
            border-top: 1px solid #ececec;
            cursor: pointer;
        }

        .hide {
            display: none !important;
        }

    </style>
</head>
<body>
    <div id='content'>
        <div id="banner" class='center'>
            <img src="https://cdn.razorpay.com/logo.svg" id="logo" height="28px" style="height: 28px; margin: 20px auto;display: block;">
        </div>

        <div class="loadingcard">
            @if ($data['type'] === 'intent')
                <div id='message-txt' class="center">
                    <b>Select your UPI app</b>
                    <div>Payment will be made to <b>razorpay@icici</b></div>
                </div>
            @else
                <div id='message-txt' class="center">
                    <div>Please accept collect request from <b>razorpay@icici</b> in your UPI app</div>
                </div>
            @endif

            <div id="error_msg" class="center red hide">No UPI apps found on this device</div>

            <div id="spinner" class={{ $data['type'] === ' intent' ? 'hide more-pad' : ''}}>
                <div class="spin">
                    <div></div>
                </div>
                <div class="spin spin2">
                    <div></div>
                </div>
            </div>

            <div class="center buttons">
                <div id="cancel_btn"><b>Cancel Payment</b></div>
                <div class="hide" id="retry_btn" onclick="initUpiActivity()"><b>Retry Payment</b></div>
            </div>
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

        var key_id = '{{ App::getFacadeRoot()['basicauth']->getPublicKey() }}';
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



        var pollStatus = function (){};
        var initUpiActivity = function(){};
        if (data.type === 'intent') {
            if (CheckoutBridge) {
                var poll_url = data.request.url, intent_url = data.data.intent_url;

                initUpiActivity = function() {
                    gel('retry_btn').classList.add('hide');
                    gel('message-txt').classList.remove('red');
                    CheckoutBridge.callNativeIntent && CheckoutBridge.callNativeIntent(intent_url);
                }

                initUpiActivity();

                pollStatus = function(resp) {
                    if (!Object.keys(resp).length || resp.Status === 'Failed') {
                       gel('retry_btn').classList.remove('hide');
                    } else {
                        gel('spinner').classList.remove('hide');
                        gel('cancel_btn').classList.add('hide');
                        gel('message-txt').innerHTML = "<b>Confirming your payment...</b>";

                        recurseAjax(
                            poll_url,
                            function(response) {
                                if(response.razorpay_payment_id) {
                                    gel('message-txt').innerHTML = "<b>Payment is Successful!</b>";
                                    gel('message-txt').classList.add('green');
                                    gel('cancel_btn').classList.add('hide');
                                    gel('spinner').classList.add('hide');
                                    setTimeout(function(){CheckoutBridge.oncomplete(JSON.stringify(resp))}, 1000);
                                }
                                else {
                                    gel('message-txt').innerHTML = "<b>Payment Failed!</b>";
                                    gel('message-txt').classList.add('red');
                                    gel('retry_btn').classList.remove('hide');
                                    gel('spinner').classList.add('hide');
                                }
                            },
                            function(response) {
                                if (response) {
                                    return false;
                                    return response.status != 'success' || response.status != 'failed';
                                }
                            }
                        )
                    }
                }
            } else {
                console.log('checking...');
                gel('error_msg').classList.remove('hide');
                gel('retry_btn').classList.add('hide');
            }
        } else {
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
        }

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
