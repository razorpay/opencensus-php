<!doctype html>
<html>
  <head>
    <title>Invoice</title>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">
    <?php date_default_timezone_set('Asia/Kolkata') ?>
    <link href="https://fonts.googleapis.com/css?family=Lato:300,400,600" rel="stylesheet" type="text/css"></link>
    <link rel="icon" href="https://razorpay.com/favicon.png" type="image/x-icon" />
    <?php
      $error_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"><path d="M12 2c5.514 0 10 4.486 10 10s-4.486 10-10 10-10-4.486-10-10 4.486-10 10-10zm0-2c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm6 16.538l-4.592-4.548 4.546-4.587-1.416-1.403-4.545 4.589-4.588-4.543-1.405 1.405 4.593 4.552-4.547 4.592 1.405 1.405 4.555-4.596 4.591 4.55 1.403-1.416z"/></svg>';
    ?>

    @if (isset($data['environment']))
      @if ($data['environment'] !== 'production')
        <script>
          var Razorpay = {
            config: {
              api: '/'
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

      #desktop-container {
        width: 100%;
        min-width: 845px;
        display: none;
      }

      #desktop-container > div {
        //position: absolute;
        //transform: translate(-50%, -50%);
        left: 50%;
        top: 50%;
      }

      #mobile-container {
        position: relative;
        display: none;
        background: #eaeaea;
      }

      #payment-container {
        width: 100%;
        position: relative;
        max-width: 880px;
        margin: 40px auto 0;
      }

      .table-box {
        display: inline-block;
        vertical-align: middle;
      }

      .table-box > div {
        min-width: 350px;
      }

      #inv-info-par {
        max-width: 600px;
        width: 60%;
      }

      #chkout-par {
        width: 39%;
        max-width: 350px;
      }

      #chkout-box {
        width: 100%;
        margin: 0 auto;
        box-shadow: 0 0 20px rgba(0,0,0,0.08);
        min-height: 511px;
        background-color: #fff;
        overflow: hidden;
        position: relative;
        z-index: 0;
        margin-left: -15px;
      }

      .short#chkout-box {
        min-height: 460px;
      }

      #overlay {
        position: fixed;
        width: 100%;
        height: 100%;
        left: 0;
        top: 0;
        background-color: rgba(0, 0, 0, 0.05);
        opacity: 0;
        z-index: 0;
        pointer-events: none;
        transition: 0.5s all ease-in-out;
      }

      #overlay.overlay-hist {
        pointer-events: all;
        background-color: rgba(0, 0, 0, 0.4);
        transition: 0.24s all ease-in-out;
        z-index: 1;
      }

      #payment-container iframe.razorpay-checkout-frame {
        min-height: 511px !important;
      }

      #inv-info-box {
        max-width: 600px;
        width: 100%;
        margin: 0 auto;
        box-shadow: 0 0 20px rgba(0,0,0,0.08);
        border: 1px solid #dfdfdf;
        border-radius: 4px;
        min-height: 270px;
      }

      .inv-details {
        padding: 30px 40px;
        background-color: #fff;
        border-radius: 4px;
      }

      .inv-details .inv-for {
        font-size: 20px;
        font-weight: 600;
      }

      .inv-details {
        font-size: 14px;
      }

      .inv-details .info {
        color: #a5a5a5;
        line-height: 22px;
        font-size: 13px;
      }

      #desktop-container .inv-details .info {
        margin-top: 16px;
      }

      #mobile-container .inv-details .info {
        margin-top: 8px;
      }

      #mobile-container .inv-details .info {
        margin-bottom: 16px;
      }

      .inv-details .info .val {
        color: #414141;
        font-size: 14px;
        text-transform: capitalize;
      }

      .info .light {
          color: #969a9a;
      }

        #partial-payment-info {
            display: none;
        }

      .inv-details .info #display-pay-amt {
            font-weight: 600;
            font-size: 20px;
        }

      .inv-details .info #display-pay-amt > span {
        position: relative;
      }

      #paid-tag {
        padding: 0 4px;
        border-radius: 4px;
        font-size: 18px;
        border: 3px solid #ff5353;
        color: #ff5353;
        position: absolute;
        transform: rotate(-16deg) scaleY(1.15);
        right: -48px;
        top: -8px;
      }

      .line-strike {
        display: block;
        width: 18px;
        border-bottom: 2px solid #18bd5a;
        margin-top: 12px;
      }

      #inv-info-box .footer {
        background-color: #fafafa;
        padding: 20px 40px;
        border-bottom-right-radius: 4px;
        border-bottom-left-radius: 4px;
        color: #717171;
        font-size: 12px;
      }

      #cancelled-crack {
        width: 100%;
        margin-top: 114px;
        background-image: url(https://cdn.razorpay.com/static/cancelled_invoice.png);
        background-repeat: no-repeat;
        background-position: -189px -90px;
        height: 80px;
        display: none;
      }

      #mobile-container #cancelled-invoice {
        width: 100%;
        top: -12px;
        background-image: url(https://cdn.razorpay.com/static/cancelled_invoice.png);
        background-repeat: no-repeat;
        background-position: -19px -147px;
        font-size: 20px;
        padding: 40px;
        line-height: 20px;
        min-height: 225px;
        display: none;
      }

      #cancelled-invoice {
        text-align: center;
        line-height: 20px;
      }

      #mobile-container #cancelled-invoice {
        padding: 45px 24px;
      }

      #desktop-container #cancelled-invoice {
        padding: 30px;
      }

      #cancelled-invoice .title {
        font-weight: 600;
        margin-top: 16px;
      }

      #cancelled-invoice .desc {
        font-size: 14px;
        color: #777777;
        margin-top: 16px;
      }

      #inv-info-box .footer img {
        height: 15px;
        vertical-align: bottom;
      }

      #footer {
        margin: 40px auto 28px;
        max-width: 655px;
        width: 85%;
        padding: 15px 24px;
        background-color: #fcfcfc;
        border: 1px solid #dfdfdf;
        font-size: 12px;
        border-radius: 4px;
        box-shadow: 0 0 15px rgba(0,0,0,0.08);
        color: #787878;
        overflow: auto;
      }

      #footer a {
        color: #8a8a8a;
      }

      #footer img {
          height: 24px;
          margin-bottom: 4px;
      }

      .bg-svg {
          position: absolute;
          z-index: -100;
          top: -35px;
          width: 100%;
      }

      #payment-container--mob {
        width: 100%;
        max-width: 412px;
        padding-bottom: 80px;
        margin: 0 auto;
        border: 1px solid #dfdfdf;
        box-shadow: 0 0 10px rgba(0,0,0,0.08);
        min-height: 100vh;
      }

      #mobile-container .inv-details{
        background-color: #fff;
        border-radius: 4px;
        padding: 22px 24px;
      }

      #payment-container--mob #inv-info-container {
        box-shadow: 0 0 20px rgba(0,0,0,0.08);
        border-radius: 4px;
        margin: 12px;
        border: 1px solid #dfdfdf;
        background: #f5f5f5;
      }



        #chkout-header {
            padding: 24px;
            overflow: hidden;
            max-height: 128px;
            position: relative;
            color: #fff;
            background: #fff;
        }

        #mob-payment-btn {
            position: fixed;
            bottom: 0;
            width: 100%;
            max-width: 411px;
            background: #fff;
            z-index: 100;
            height: 55px;
            font-size: 16px;
            color: #fff;
            border: 0;
            background-image: linear-gradient(to bottom right,rgba(255,255,255,0.2),rgba(0,0,0,0.2));
            cursor: pointer;
            display: none;
        }

        #chkout-header:before {
            content: "";
            left: 0;
            right: 0;
            bottom: 0;
            top: 0;
            position: absolute;
            background-image: linear-gradient(to bottom right,rgba(255,255,255,0.2),rgba(0,0,0,0.2));
        }

        #desktop-container #chkout-header {
            position: absolute;
            top: 0;
            width: 100%;
        }

        #header-logo {
            text-align: center;
            position: relative;
            height: 80px;
            border-radius: 3px;
            line-height: 62px;
            float: left;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        #header-logo.visible {
            background: #fff;
            padding: 8px;
            width: 80px;
            margin-right: 24px;
        }

        #header-details {
            white-space: nowrap;
            position: relative;
        }

        #header-details #merchant {
            margin-top: 18px;
        }

        #header-details #merchant-name {
            text-overflow: ellipsis;
            overflow: hidden;
            font-size: 20px;
        }

        #header-details #merchant-desc {
            white-space: pre;
            text-overflow: ellipsis;
            overflow: hidden;
            opacity: .8;
            font-size: 14px;
        }

        #header-details #amount {
            font-size: 24px;
            margin-top: 10px;
        }

        #payment-container--mob #footer {
            width: auto;
            margin: 12px;
            padding: 12px 18px
        }

        #payment-container--mob #fin-logo{
            padding: 5px 0;
            margin-top: 10px;
            margin-bottom: 0;
        }

        #scs-box img {
            padding-left: 2px;
            width: 48px;
            margin: 4px auto;
        }


        #scs-box {
            line-height: 28px;
            background-color: #fff;
            padding: 36px 30px;
            text-align: center;
            font-size: 14px;
            top: 0;
            width: 100%;
            position: absolute;
            margin: 175px auto 0;
            display: none;
            background: #effff6;
        }

        #payment-for {
            position: relative;
        }
        .btn-link {
          color: #528ff0;
          background: linear-gradient(transparent, rgba(255,255,255,0.8));
          border: 0;
          cursor: pointer;
          padding: 0;
          font-size: 14px;
          outline: none;
        }

        .showmore {
            padding-left: 10px
            margin-left: -9px;
        }

        #desktop-container .showhist {
            margin-top: 16px;
        }

        #hist-modal {
            position: fixed;
            width: 92%;
            max-width: 460px;
            left: 50%;
            top: 48%;
            line-height: 24px;

            background: #fff;
            border-radius: 4px;
            box-shadow: 0 0 10px rgba(0,0,0,0.4);
            color: #909090;
            max-height: 70vh;
            overflow: scroll;
            transition: .1s all ease-in;
            transform: translate(-50%,-50%) scale(0.7);
            opacity: 0;
            z-index: 2;
            pointer-events: none;
        }

        #hist-modal.show {
          display: block;
          transform: translate(-50%,-50%) scale(1);
          opacity: 1;
          pointer-events: all;
        }

        #hist-close {
            position: absolute;
            right: 10px;
            top: 10px;
            padding: 10px;
            font-size: 18px;
            cursor: pointer;
            color: #57666e;
        }

        .modal-title {
            padding: 24px 20px;
            font-size: 18px;
            font-weight: 600;
            color: #2e3345;
        }
        .modal-desc {
            font-size: 14px;
            font-weight: 400;
            color: #909090;
        }

        .modal-col {
            padding: 24px 20px;
            border-top: 1px solid #e0e0e0;
        }

        .modal-col .row:nth-of-type(n+2) {
            font-size: 13px;
        }

    </style>
  </head>
  <body>

    <script>

      (function (globalScope) {

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

        globalScope.data               = data;
        globalScope.hasRedirect        = hasRedirect;
        globalScope.redirectToCallback = redirectToCallback;
      }(window.RZP_DATA = window.RZP_DATA || {}));
    </script>

    @if (isset($data['error']))
      <div id="failure" class="card">
        {!! $error_icon !!}
        <h2>Error</h2>
        <p>{{$data['error']['description']}}. Please contact the merchant for assistance.</p>
      </div>
    @else
      <div id="invoice-status-container" class={{$data['invoice']['status']}}>
          @if (isset($data['invoice']) && $data['invoice']['type'] !== 'invoice')
            <!-- Desktop Container -->
            <div id="desktop-container">
                <div>
                  <svg class="bg-svg" width="1665px" height="665px" viewBox="0 0 1665 665" preserveAspectRatio="none">
                      <polygon fill="#fafafa" points="40 50 1665 210 1665 346 220 545 -150 150"></polygon>
                      <polygon fill="#f5f5f5" transform="translate(0, -40)" points="-40 215 1865 0 1965 450 1550 730 0 680"></polygon>
                  </svg>
                  <div id="payment-container">

                      <div class="table-box" id="inv-info-par">
                          <div id="inv-info-box">
                              <div class="inv-details">
                                  <div class="inv-for">
                                    Payment Request from {{$data['merchant']['name']}}
                                  </div>
                                  <div id="inv-details-main">
                                      <div class="info" style="margin-top: 28px;">
                                          PAYMENT FOR
                                          <div id="payment-for" class="val" style="white-space: pre-wrap;word-wrap: break-word;"></div>
                                      </div>

                                      @if($data['invoice']['expire_by'] and $data['invoice']['status'] !== 'paid')
                                          <div class="info">
                                              {{$data['invoice']['status'] === 'expired' ? 'EXPIRED ON' : 'EXPIRES BY'}}
                                              <div class="val">
                                              {{format_epoch($data['invoice']['expire_by'])}}
                                              </div>
                                          </div>
                                      @endif

                                      <div class="info">
                                          <span id="pay-title">AMOUNT PAYABLE</span>
                                          <div class="val" id="display-pay-amt">
                                          ₹{{format_amount($data['invoice']['amount'])}}
                                          </div>

                                          <div class="info" id="partial-payment-info">
                                              <div class="val">
                                                  <b>₹{{format_amount($data['invoice']['amount_due'])}}</b>
                                                  <span class="light">Due</span>
                                              </div>
                                              <div class="val">
                                                  <span> ₹{{format_amount($data['invoice']['amount_paid'])}}</span>
                                                  <span class="light">Paid</span>
                                              </div>
                                          </div>
                                          <div class="line-strike"></div>

                                      </div>
                                      @if($data['invoice']['partial_payment'] && count($data['invoice']['payments']))
                                        <button class="btn-link showhist" onclick="showPayHist()"> Show Payment History </button>
                                        <div id="hist-modal">
                                          <div id="hist-close" onclick="closePayHist()"><b>✕</b></div>

                                          <div class="modal-title">
                                            Payment History
                                            <div class="modal-desc">
                                            {{count($data['invoice']['payments'])}} Payment{{(count($data['invoice']['payments']) > 1) ? 's' : ''}} made for this request
                                            </div>
                                          </div>


                                          @foreach ($data['invoice']['payments'] as $key => $item)
                                              <div class="modal-col">
                                                <div class="row"><b style="color: #2e3345">
                                                    ₹{{format_amount($item['amount'])}} Paid </b>on {{format_epoch($item['created_at'])}}
                                                </div>
                                                <div class="row">Paid using <span style="text-transform: capitalize">{{$item['method']}}</span></div>
                                                    <div class="row">Payment ID: {{$item['id']}}</div>
                                              </div>
                                          @endforeach
                                        </div>
                                      @endif
                                  </div>
                              </div>
                              <div class="footer">
                                  Powered by
                                  <img src="https://cdn.razorpay.com/logo.svg" />
                              </div>
                          </div>
                      </div>
                      <div class="table-box" id="chkout-par">
                        <div id="overlay"></div>

                        <div id="chkout-box" class={{(in_array($data['invoice']['status'], ['paid', 'expired', 'cancelled'], true) === true) ? 'short' : ''}}>
                            <div id="chkout-header">
                                <div id="header-logo" class={{isset($data['merchant']['image']) ? 'visible' : ''}}>
                                    @if (isset($data['merchant']['image']))
                                        <img src={{$data['merchant']['image']}} width="100%">
                                    @endif
                                </div>

                                <div id="header-details">
                                    @if (isset($data['merchant']))
                                        <div id="merchant">
                                            <div id="merchant-name">{{$data['merchant']['name']}}</div>
                                            <div id="merchant-desc">Invoice #{{$data['invoice']['id']}}</div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div id="scs-box">
                                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACUAAAAlCAMAAADyQNAxAAAASFBMVEUAAADD+tvD+tvD+tvD+tvD+dvD+trD+dvE+9vE/NvG+9zD/+HH/+H////C+doewGBj2JOW6rojwmOo8MeL5rF/4qh03qBm2ZW6Mr7TAAAADnRSTlMA6JrxzLKRiHlOOiIUAmMEAH8AAADOSURBVDjLlZRZDoMwDERtSAgEMizd7n/TSi0qSerE8P6QniwM46GY4J01DDbW+UAyY8M44GYUnKlDTjfl0tDin3ZIpR4yfSw1KNFkk7RpA2oM+3YtarTfTTvU6T4fExpjvF9tz8CQWR/Y4UC+JG3zih1PrigtvwdHVpdgyegSDLEugQkHt0w6iGa9trUgcfRey7ytogRDFokmSbDkkGhPQYIjj0SbBQk++4+LJHHIM3GXMnE6X3pWT+dev6EL96jftt4TZztH76/rXaj36ht1cjrNdgCxBgAAAABJRU5ErkJggg==" />
                                <div style="font-weight: 600; font-size: 18px">Payment Completed</div>
                                <div id="scs-msg"style="color:#9b9b9b"></div>
                            </div>
                            <div id="cancelled-crack"></div>
                              @if($data['invoice']['status'] === 'cancelled')
                                <div id="cancelled-invoice">
                                  <div class="title" style='color:#f54443; font-size: 18px;'>Payment Link Cancelled</div>
                                  <div class="desc">
                                    Oops! This payment link was cancelled. Please contact {{$data['merchant']['name']}} support in case you have any queries.
                                  </div>
                                </div>
                              @elseif($data['invoice']['status'] === 'expired')
                                <div id="cancelled-invoice">
                                    <div class="title" style='color:#f54443; font-size:18px'>Payment Link Expired</div>
                                    <div class="desc">
                                        Oops! This payment link expired on {{format_epoch($data['invoice']['expire_by'])}}. Please contact {{$data['merchant']['name']}} support in case you have any queries.
                                    </div>
                                </div>
                              @endif
                        </div>
                        </div>
                      </div>
                  <div id="footer">
                      <div>
                          <img style="padding:3px 0" src="data:image/jpeg;base64,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" />
                          <img id="rzp-logo" src="https://cdn.razorpay.com/logo.svg" style="float: right;"/>
                      </div>
                      <div>
                          Want to create payment links for your business? Visit
                          <a href="razorpay.com/payment-links" target="_blank">razorpay.com/payment-links</a>
                          and get started instantly
                      </div>
                  </div>
                </div>
              </div>

            <!-- Mobile Container -->
            <div id="mobile-container">
              <div id="overlay"></div>
              <div id="payment-container--mob">
                  <div id="chkout-header">
                    <div id="header-logo" class={{isset($data['merchant']['image']) ? 'visible' : ''}}>
                        @if (isset($data['merchant']['image']))
                            <img src={{$data['merchant']['image']}} width="100%">
                        @endif
                    </div>
                    <div id="header-details">
                        @if (isset($data['merchant']))
                            <div id="merchant">
                                <div id="merchant-name">{{$data['merchant']['name']}}</div>
                                <div id="merchant-desc">Invoice #{{$data['invoice']['id']}}</div>
                            </div>
                        @endif
                    </div>
                  </div>
                  <div id="inv-info-container">
                      <div class="inv-details">
                          <div id="inv-details-main">
                              <div class="info">
                                  PAYMENT FOR
                                  <div id="payment-for" class="val" style="white-space: pre-wrap;word-wrap: break-word;"></div>
                              </div>

                              <div class="info">
                                  <span id="pay-title">AMOUNT PAYABLE</span>
                                  <div class="val" id="display-pay-amt">
                                    ₹{{format_amount($data['invoice']['amount'])}}
                                  </div>
                                  <div class="info" id="partial-payment-info">
                                      <div class="val">
                                          <b>₹{{format_amount($data['invoice']['amount_due'])}}</b>
                                          <span class="light">Due</span>
                                      </div>
                                      <div class="val">
                                          <span>₹{{format_amount($data['invoice']['amount_paid'])}}</span>
                                          <span class="light">Paid</span>
                                      </div>
                                  </div>
                                  <div class="line-strike"></div>
                              </div>
                              @if($data['invoice']['status'] === 'paid' && !$data['invoice']['partial_payment'])
                                  <div class="info">
                                      PAYMENT ID
                                      <div class="val" style="text-transform:unset">{{$data['invoice']['payment_id']}}</div>
                                  </div>
                              @endif

                              @if($data['invoice']['expire_by'] and $data['invoice']['status'] !== 'paid')
                                <div class="info">
                                  {{$data['invoice']['status'] === 'expired' ? 'EXPIRED ON' : 'EXPIRES BY'}}
                                  <div class="val">{{format_epoch($data['invoice']['expire_by'])}} </div>
                                </div>
                              @endif
                              @if($data['invoice']['customer_details']['customer_name'] or $data['invoice']['customer_details']['customer_email'])
                                <div class="info">
                                  ISSUED TO
                                  @if($data['invoice']['customer_details']['customer_name'])
                                    <div class="val">{{$data['invoice']['customer_details']['customer_name']}}</div>
                                  @endif
                                  @if($data['invoice']['customer_details']['customer_email'])
                                    <div class="val">{{$data['invoice']['customer_details']['customer_email']}}</div>
                                  @endif
                                </div>
                              @endif
                            @if($data['invoice']['partial_payment'] && count($data['invoice']['payments']))
                              <button class="btn-link showhist" onclick="showPayHist()"> Show Payment History </button>
                              <div id="hist-modal">
                                <div id="hist-close" onclick="closePayHist()"><b>✕</b></div>

                                <div class="modal-title">
                                  Payment History
                                  <div class="modal-desc">{{$data['invoice']['partial_payment']}} Payment{{$data['invoice']['partial_payment'] > 1 ?: 's'}} made for this request</div>
                                </div>

                                  @foreach ($data['invoice']['payments'] as $key => $item)
                                      <div class="modal-col">
                                        <div class="row"><b style="color: #2e3345">
                                            ₹{{format_amount($item['amount'])}} Paid </b>on {{format_epoch($item['created_at'])}}
                                        </div>
                                        <div class="row">Paid using <span style="text-transform: capitalize">{{$item['method']}}</span></div>
                                        <div class="row">Payment ID: {{$item['id']}}</div>
                                      </div>
                                  @endforeach
                              </div>
                            @endif
                          </div>
                      </div>
                      @if($data['invoice']['status'] === 'cancelled')
                        <div id="cancelled-invoice">
                          <div class="title" style='color:#f54443; font-size:18px'>Payment Link Cancelled</div>
                          <div class="desc">
                            Oops! This payment link was cancelled. Please contact {{$data['merchant']['name']}} support in case you have any queries.
                          </div>
                        </div>
                      @elseif($data['invoice']['status'] === 'expired')
                        <div id="cancelled-invoice">
                          <div class="title" style='color:#f54443; font-size:18px'>Payment Link Expired</div>
                            <div class="desc">
                              Oops! This payment link expired on {{format_epoch($data['invoice']['expire_by'])}}. Please contact {{$data['merchant']['name']}} support in case you have any queries.
                          </div>
                        </div>
                      @endif
                  </div>

                  <div id="footer">
                      <img id="rzp-logo" src="https://cdn.razorpay.com/logo.svg" />
                      <div>
                          Want to create payment links for your business? Visit
                          <a href="razorpay.com/payment-links" target="_blank">razorpay.com/payment-links</a>
                          and get started instantly
                      </div>
                      <img id="fin-logo" src="data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDACgcHiMeGSgjISMtKygwPGRBPDc3PHtYXUlkkYCZlo+AjIqgtObDoKrarYqMyP/L2u71////m8H////6/+b9//j/2wBDASstLTw1PHZBQXb4pYyl+Pj4+Pj4+Pj4+Pj4+Pj4+Pj4+Pj4+Pj4+Pj4+Pj4+Pj4+Pj4+Pj4+Pj4+Pj4+Pj4+Pj/wAARCAAoAWoDASIAAhEBAxEB/8QAGgAAAgMBAQAAAAAAAAAAAAAAAAQBAwUCBv/EADMQAAICAQIFAgQEBwADAAAAAAECAAMRBCEFEjFBURMUImFxgTJTkaFCQ1JisdHhM3Lw/8QAGQEAAwEBAQAAAAAAAAAAAAAAAAECAwQF/8QAHxEAAgICAgMBAAAAAAAAAAAAAAECERIhAzETQVFh/9oADAMBAAIRAxEAPwDZhFNRoUsUmtnR+2GOJncM1Fi6xUZ2KtkEEylG0BuQiHFtQaqBWpIZz28RfSc9XDr9Q7sSwwuT9v8AMMdWBrwmLwpmN1ljuxVEzuZU1+p1+o5FblB6LnAEeGwN+EyKOHaqrUVsXHKGBblY9I9r9R7fSswOGOy/WKt6AZhPM132pYjl3OCDuTvPSg5GRCUaAmVai9dPUXbfwPJlsyOKuTqAnZVkSdI04oZyplN2sutO7lR4XYTiq66sj03YfLO0rllAy5PgTFv2ehjFKqNFdVY6gMQDjfHeQGYHIY5i6nDCXznk23ZzuKXQzTeSQr/rGJnR+tuatT8p0cM29M5+SNbRJ/CYt6j/ANRjLfhP0ivK3j951RMJENY4/iMpe+0dHMuZGPj9ZQ9TncAfqJoqMZ5eis6m78xo3oLXsL87E4xjMU9vYegHXH4hGeHoyPYGGDgQnVaDjyvY9IJkxbWsy1ZU4wwyZilbOhulZ3ZfXUcPYAfE6R1deZXyvmc10VpkhQxO5Y7kymsNzvpy2eQhlJ8eI6XoVu9jCuGzhth3MnI3+Lp3lPolfiBBIOcAbdT/ALnK0HCnKgg5wRt3/wBySmMZ/v8A3grqSQGBI64i502ebLD4pYqcljMMYbG2OkdIm2Xzh7ErGXYKD5M7lGtq9XTOB1G4iG+gOs04/mrOTr9MP5n7GYyqzHCqSfkJJqsAya2A+kqkZeRmseI6cfxMftOTxOjsHP2mTO1ptYZWtyPIEdIM5GieKV9q3McqsFtSuvRhMzh1i02OHRs+QucRvT6lX1NlYUqOoBGPrJaKjL6NwlbXIjBS3xHsBmKG3U2au2qqwKF3GREtlOSQ/CZ66y5tGzhR6itgkDt5k6TUPZcF9ZXUjcEYP2joMkPwmWdXdzsGtFbA7IV2/WaKMSik8uSOx2hQKSZ3PP3j23EyegDhh9Os9BMjjNLG2uxVJyMHAlQ7KF9Y51mv5U3GeRY5xXFGhrpXpkD7CVcI0x9VrnUjlGBkd4cY53vRVViFXsPMr2kIu4PUPaOzDZzj7RTU8Pu0zGyrLINww6iOMbtLwuoUqefYnAzjvFW4nqmUpyLk7ZCnMSu7QF/DeIPbYKbtyfwtF+LXerqhUp2Tb7ydHprKA2qtQjkB5VxuTKdNo7dXc3MSncsRHSuwLOI1V1pQK2VsLynB/wDvM1tFZ6mjqb+3B+20ytTwtqKTYtnPjsFjvCC3tmRgRyttkdopbiA/MvitRFi2gbEYP1mpObK1tQo4ypmTVo0454Ss87O6Ww/12jl3DLASamDDwdjOE4bex+LlUfXMycX0d/lg12Soywl0uGj5FAVsnvnvI9vZ4H6zCXHK+jnfJFlQGTgR9F5UA8CcVUivcnJls34uNx2zGcr6IY4UnwIkl6swAByem8cf/wAbfQzIR+Rw2M4M6oK0zl5ZNNDpfG4G3/tOSe+MY/u/5KPcAADk6fOR7gb5rU+PlLxIysv3zkrnf+rqf0+st0ikO5PTAHXPmJnUjBHJsTnrGtDYLHsPLg7d5Mk6NI1Y5K7AGDKRkEdJZMzXam2vUMiNgADtM0rZcnS2XIdSq+mqKANgzNnad11cj85YsxXBJ7xS1NQlRYu/wgE5YTlOZlrJY5c4GJpV+zPJr0O3V87IwUErnc/Tb95xjUDbmJ267dcf7i/KBklyANtxg5+kMN2c/h5osP0PK/hcRqNyCQTjO4Pb/c7rWz1iz77EdR5lBS1WwLW2Bzsc7fKUnV2oxAbIB7iLD4V5PqNmEISDQz9LV6PELV7cuR9MxoG/3RGB6OP3kXslDi9gcY5TgTM1Wqa20mt3CEdM4ldmbaiOpTTZr7GAB5AMjtmVavXW1XmusABfI6xTTahtPZzAZB2I8xw36PUuvqIQ52ydoUK7WtBw+w3am2xsZIGcSLVNessvPY4QeTj/ABO9M9NVr5X0zjGO0mpTqbzY34V6SHL4K7SS7LtLSVHqPu7ee0Wet01Nttd9ak7HPaPVV+mnLzM3fJkGitiSQdznqY1ovHVCS1JVpyiakLZnJYH9pKU5uW622v4c45B1xGvbU5zyCSdPUc5Xqc4yY7DERsrcqyNqK2Q75bdgIwmko5F+Jjt1yZcNNSOidsde0thY1H6EIQiKCEIQAIQhAAhCEACEIQAJVejuAEYr1zg47bfvCEAKWp1GMK5Pj4yMHA3/AMwNNxYkkkB+YfGem8IQAlKdRn47T1zsfkf+bSDVqMLhjt1+M9fP/IQgANRcQoLFuhOWOxzn77RuEIAcuMowHXEyva3/AJZhCVGTREoqXYe1v/LMPaX/AJZhCXmyfGiPaX/ln9Y5oKbKi/OpXOMQhJcm0UopMcmfxHSvYwtrGTjDAQhJTplSVoVt1l1lbVsgGcAkA52ldd71gBawMHJ2O8ITajG2QLTgr6Y5Scgb7GdrqXwAUGRgZx1xCEBE+4fGGQNkY3z5zOtJpXutDMpFYOST3hCKTpaHFW9mzCEJibld1YtpdD3Exq9HfYdqyPmdoQjTIlFNjdfC+9tn2WN16OirpWCfLbwhCxqKRF+lW5uYHlbvt1ltVYqrCDt38whJoeKTs7hCEYwhCEACEIQA/9k=" />
                  </div>
                  <button id="mob-payment-btn">
                      PROCEED TO PAY
                  </button>
            </div>
          </div>
          @endif

        @if ($data['invoice']['type'] !== 'invoice')
          <script src="https://cdn.razorpay.com/static/analytics/bundle.js" onload="initAnalytics()" async></script>
          <script>
            function checkIsDesktop() {
                var width = (window.innerWidth > 0) ? window.innerWidth : screen.width;
                return width > 853;
            }

            function cleanHTML() {
                // Show content according to width
                if (checkIsDesktop()) {
                    document.getElementById('desktop-container').style.display = 'block';
                    document.getElementById('invoice-status-container').removeChild(document.getElementById('mobile-container'));
                } else {
                    document.getElementById('mobile-container').style.display = 'block';
                    document.getElementById('invoice-status-container').removeChild(document.getElementById('desktop-container'));
                }
            }

            function toggleTrimDescription(toTrim) {
              var data = window.RZP_DATA.data;
              desc = data['invoice']['description'];
              var charLimit, pseudoChar, button = '';

              if (checkIsDesktop()) {
                  charLimit = 200;
                  pseudoChar = 45;
              } else {
                  charLimit = 125;
                  pseudoChar = 35;
              }

              if (desc && (desc.length > charLimit)) {
                  if (toTrim) {
                    var newLines = 0;
                    newLines = (desc.match(new RegExp("\n", "g")) || []).length;

                    if (newLines) {
                      for(let i = 0; i < newLines; i++) {
                        if ((charLimit - i * pseudoChar) < 0.6 * charLimit) {
                          desc = desc.substr(0, charLimit - i*pseudoChar);
                          break;
                        }
                      }
                    } else {
                      desc = desc.substr(0,charLimit);
                    }

                    desc =  desc.trim();
                    desc += '...';
                    button = '<button class="btn-link showmore" onclick="toggleTrimDescription(false)"> Show More </button'
                  }
                }

              document.getElementById('payment-for').innerHTML = desc + button;
            }
          </script>

          <script>
              cleanHTML();

              function initAnalytics() {
                analytics.init(['ga'], window.location.hostname.indexOf('razorpay.com') < 0);
                analytics.track('ga', 'pageview');
              }

              var data = window.RZP_DATA.data;
              var color = data.merchant.brand_color || '#168AFA';
              document.getElementById('chkout-header').style['background-color'] = color;


              toggleTrimDescription(true);

              function fullPaid() {
                  var amount = data['invoice']['amount'];
                  document.getElementById('pay-title').innerHTML = 'AMOUNT PAID';

                  if (checkIsDesktop()) {
                      document.getElementById('scs-box').style.display = 'block';
                      var successNote = "You have successfully paid ₹ " + (amount/100).toFixed(2);

                      if (!data['invoice']['partial_payment']) {
                        successNote += '<div> Payment ID: ' + data['invoice']['payment_id'] + ' </div>'
                      }

                      document.getElementById('scs-msg').innerHTML = successNote;

                      document.getElementById('display-pay-amt').innerHTML = '<span> ₹' + (amount/100).toFixed(2);
                  } else {
                    document.getElementById('display-pay-amt').innerHTML = '<span> ₹' + (amount/100).toFixed(2) + '<span id="paid-tag">PAID</span></span>';
                  }
              }

              if (data['invoice']['partial_payment'] && data['invoice']['status'] !== 'paid' && data['invoice']['amount_paid'] != 0) {
                document.getElementById('partial-payment-info').style.display = 'block';
              }

              // Invoice full paid
              if (data['invoice']['amount_due'] === 0 && data['invoice']['status'] === 'paid') {
                  fullPaid();
              }
              // Invoice cancelled/expired
              else if (data['invoice']['status'] === 'cancelled' || data['invoice']['status'] === 'expired') {
                document.getElementById('cancelled-invoice').style.display = 'block';

                if (checkIsDesktop()) {
                    document.getElementById('cancelled-crack').style.display = 'block';
                    document.getElementById('chkout-box').style.background = '#f5f5f5';
                } else {
                    document.getElementById('inv-details-main').style.display = 'none';
                }
              }
          </script>
          <script>
            if(data.invoice['partial_payment']) {
                function showOverlay(clsToAdd) {
                  var overlay = document.getElementById('overlay');
                  overlay.style.opacity = 1;

                  if (clsToAdd && overlay.className.indexOf(clsToAdd) === -1) {
                    overlay.className += " " + clsToAdd;
                  }
                }

                function hideOverlay(clsToRemove) {
                  var overlay = document.getElementById('overlay');
                  overlay.style.opacity = 0;


                  if (clsToRemove && overlay.className.indexOf(clsToRemove) > -1) {
                    overlay.className = overlay.className.replace(clsToRemove, '');
                  }
                }

                function showPayHist() {
                    document.getElementById('hist-modal').className = 'show';
                    showOverlay('overlay-hist');

                    ga('send', 'event', 'PL Hosted Page', 'Show Pay History', data.invoice.payments.length);
                }

                function closePayHist() {
                    document.getElementById('hist-modal').className = '';
                    hideOverlay('overlay-hist');

                    ga('send', 'event', 'PL Hosted Page', 'Close Pay History', data.invoice.payments.length);
                }
            }
          </script>
          @if ($data['invoice']['status'] !== 'paid' and ($data['invoice']['status'] !== 'expired' and $data['invoice']['status'] !== 'cancelled'))
            @if (isset($data['error']))
              <div id="failure" class="card">
                {!! $error_icon !!}
                <h2>Payment Failed</h2>
                <p>{{ $data['error']['description'] }}</p>
                <button id="button" onclick="razorpay.open()">Retry</button>
              </div>
            @endif
            <script>
              if (checkIsDesktop()) {
                document.getElementById('chkout-box').addEventListener('mouseover', showOverlay);
                document.getElementById('chkout-box').addEventListener('mouseout', hideOverlay);
              } else {
                var payBtn = document.getElementById('mob-payment-btn');
                payBtn.style['background-color'] = color;
                payBtn.style['display'] = 'block';
              }

              (function (globalScope) {
                var data = globalScope.data;

                var invoiceObj = data.invoice;
                var merchant = data.merchant;

                var options = {
                  key: data.key_id,
                  invoice_id: invoiceObj.id,
                  amount: invoiceObj.amount,
                  // parent: '#chkout-box',
                  description: 'Invoice #' + invoiceObj.id,
                  handler: function(response) {

                    if (globalScope.hasRedirect()) {

                      return globalScope.redirectToCallback(
                                                             data.invoice.callback_url,
                                                             data.invoice.callback_method,
                                                             response
                                                           );
                    }

                    return location.reload(); // To display the latest payment id
                  },
                  prefill: {
                    contact: invoiceObj.customer_details.customer_contact,
                    email: invoiceObj.customer_details.customer_email,
                  },
                  callback_url: location.href,
                  theme: {
                    close_button: false,
                  },
                  modal: {
                    confirm_close: true,
                    escape: false
                  }
                };

                if (merchant) {
                  if (merchant.name) {
                        options.name = merchant.name;
                  }

                  var color = merchant.brand_color || '#168AFA';
                  options.theme.color = color;

                  if (merchant.image) {
                    options.image = merchant.image;
                  }
                }

                var razorpay;
                if (!data.error) {
                    if (checkIsDesktop()) {
                      options.parent = '#chkout-box';
                      razorpay = window.razorpay = Razorpay(options);
                    } else {
                        document.getElementById('mob-payment-btn').addEventListener('click', function() {
                            razorpay = window.razorpay = Razorpay(options);
                            razorpay.open();
                        });
                    }
                }

              }(window.RZP_DATA = window.RZP_DATA || {}));
            </script>
          @endif
        @else
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

                  if (data.invoice.partial_payment) {
                    window.location.reload()
                  } else {
                    let invoice = data.invoice;
                    invoice.amount_due_formatted = '0.00';
                    invoice.amount_paid_formatted = invoice.amount_formatted;
                    invoice.status = 'paid';
                    invoice.is_paid = true;
                    this.rerender(data)
                  }
                  data.invoice.status = 'paid';
                  data.invoice.is_paid = true;
                  this.rerender(data)
                }
              });
            }(window.RZP_DATA = window.RZP_DATA || {}));
          </script>
        @endif
      </div>
    @endif

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
