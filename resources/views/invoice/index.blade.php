<!doctype html>
<html>
<head>
    <title>Invoice</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <meta name="robots" content="noindex">
    <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">

    <?php date_default_timezone_set('Asia/Kolkata') ?>
    <link href="https://fonts.googleapis.com/css?family=Lato:300,400,600" rel="stylesheet" type="text/css"></link>
    <link rel="icon" href="https://razorpay.com/favicon.png" type="image/x-icon" />

    @if (isset($data['environment']))
        @if ($data['environment'] !== 'production')
            <script>
                var Razorpay = {
                    config: {
                        api: "{{ config('app.url') }}/"
                    }
                }
            </script>
        @endif
    @endif
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            font-family: "Lato",ubuntu,helvetica,sans-serif;
            color: #414141;
            background: #fff;
        }


        #success path {
            fill: #6DCA00;
        }

        #failure path {
            fill: #e74c3c;
        }

        h3 {
            font-weight: normal;
        }

        .card {
            background: #fff;
            border-radius: 2px;
            box-shadow: 0 2px 9px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin: 30px auto;
            width: 80%;
            max-width: 300px;
            text-align: center;
        }

        #break {
            color: #777;
            font-size: 14px;
            margin: 30px -30px 0;
            padding: 30px 30px 0;
            border-top: 1px dashed #e3e4e6;
            text-align: left;
            line-height: 24px;
        }

        #break span {
            float: right;
        }

        #success {
            display: none;
        }

        .paid #success {
            display: block;
        }

        .issued #partial {
            display: none;
        }

        #button {
            background-color: #4994E6;
            color: #fff;
            border: 0;
            outline: none;
            cursor: pointer;
            font: inherit;
            margin-top: 10px;
            padding: 10px 20px;
            border-radius: 2px;
        }

        #button:active {
            box-shadow: 0 0 0 1px rgba(0,0,0,.15) inset, 0 0 6px rgba(0,0,0,.2) inset;
        }

        body div.redirect-message {

            display: none;
        }

        body.has-redirect div.redirect-message {

            display: block;
        }
    </style>

    <script src="https://cdn.razorpay.com/static/analytics/bundle.js"></script>
</head>
<body>

<script>

    (function (globalScope) {

        var keylessHeader = "{{$keyless_header}}";

        var data = {!!utf8_json_encode($data)!!};

        function forEach (dict, cb) {

            dict = dict || {};

            if (typeof dict !== "object" || typeof cb !== "function") {

                return dict;
            }

            var key, value;

            for (key in dict) {

                if (!dict.hasOwnProperty(key)) {

                    continue;
                }

                value = dict[key];
                cb.apply(value, [value, key, dict]);
            }

            return dict;
        }

        function parseQuery(qstr) {

            var query = {};

            var a = (qstr[0] === '?' ? qstr.substr(1) : qstr).split('&'), i, b;

            for (i = 0; i < a.length; i++) {

                b = a[i].split('=');
                query[decodeURIComponent(b[0])] = decodeURIComponent(b[1] || '');
            }

            return query;
        }

        function createHiddenInput (key, value) {

            var input = document.createElement("input");

            input.type  = "hidden";
            input.name  = key;
            input.value = value;

            return input;
        }

        function hasRedirect () {

            return data.invoice &&
                data.invoice.callback_url &&
                data.invoice.callback_method;
        }

        function redirectToCallback (callbackUrl,
                                     callbackMethod,
                                     requestParams) {

            document.body.className = ([document.body.className,
                "paid",
                "has-redirect"]).join(" ");

            var form   = document.createElement("form"),
                method = callbackMethod.toUpperCase(),
                input, key;

            form.method = method;
            form.action = callbackUrl;

            forEach(requestParams, function (value, key) {

                form.appendChild(createHiddenInput(key, value));
            });

            var urlParamRegex = /^[^#]+\?([^#]+)/,
                matches       = callbackUrl.match(urlParamRegex),
                queryParams;

            if (method === "GET" && matches) {

                queryParams = matches[1];

                if (queryParams.length > 0) {

                    queryParams = parseQuery(queryParams);

                    forEach(queryParams, function (value, key) {

                        form.appendChild(createHiddenInput(key, value));
                    });
                }
            }

            document.body.appendChild(form);

            form.submit();
        }

        data.keyless_header            = keylessHeader;
        globalScope.data               = data;
        globalScope.hasRedirect        = hasRedirect;
        globalScope.redirectToCallback = redirectToCallback;
    }(window.RZP_DATA = window.RZP_DATA || {}));
</script>

<div id="invoice-status-container" class={{$data['invoice']['status']}}>
    <script src="{{$data['invoicejs_url']}}"></script>
    <div id="invoice-container"></div>
    <script type="text/javascript">
        (function (globalScope) {

            var data = globalScope.data;

            RazorpayInvoice({
                parentElement: "#invoice-container",
                data: data,
                paymentResponseHandler: function(response) {

                    if (globalScope.hasRedirect()) {

                        return globalScope.redirectToCallback(
                            data.invoice.callback_url,
                            data.invoice.callback_method,
                            response
                        );
                    }

                    if (response.razorpay_invoice_status === 'paid') {
                        let invoice = data.invoice;
                        invoice.amount_due_formatted = '0.00';
                        invoice.amount_paid_formatted = invoice.amount_formatted;
                        invoice.status = 'paid';
                        invoice.is_paid = true;
                        this.rerender(data);
                    } else {
                        window.location.reload()
                    }
                }
            });
        }(window.RZP_DATA = window.RZP_DATA || {}));
    </script>
</div>

<script>

    (function (globalScope) {

        var data = globalScope.data;

        if (globalScope.hasRedirect() &&
            data.request_params.razorpay_payment_id) {

            return globalScope.redirectToCallback(
                data.invoice.callback_url,
                data.invoice.callback_method,
                data.request_params
            );
        }
    }(window.RZP_DATA = window.RZP_DATA || {}));
</script>
</body>
</html>
