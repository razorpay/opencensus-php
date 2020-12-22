<!doctype html>
<html>
  <head>
    <title>Emandate Processing...</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width">
    <meta charset="utf-8">
    <style>
      body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Ubuntu', 'Cantarell', 'Droid Sans', 'Helvetica Neue', sans-serif;
        background: #f4f4f4;
        color: #414141;
        font-size: 14px;
        line-height: 1.6;
      }
      form {
        width: 92%;
        max-width: 330px;
        margin: 20px auto 0px;
        background: #fff;
        border-radius: 4px;
        box-shadow: 0 2px 7px rgba(0,0,0,0.09);
      }
      header {
        border-bottom: 1px solid #eee;
        padding: 10px 20px;
        overflow: auto;
      }
      header img {
        padding: 4px 10px 4px 0;
        vertical-align: middle;
      }
      header div {
        font-size: 12px;
        display: inline-block;
        line-height: 16px;
        max-width: 150px;
        vertical-align: middle;
      }

      header span {
        font-size: 22px;
        float: right;
      }

      .heading {
        margin: 16px 0;
        color: #606060;
        font-weight: 600;
      }

      main:nth-of-type(2) {
        border-top: 1px dashed #ccc;
        margin-top: 16px;
      }

      .common input, select {
        margin: 5px 0 12px;
      }
      input, select {
        border: 1px solid #bbb;
        height: 36px;
        outline: none;
        width: 100%;
        box-sizing: border-box;
        -webkit-box-sizing: border-box;
        padding: 0 12px;
        font-size: 14px;
        font-family: inherit;
        color: #111;
      }
      .action {
        padding: 30px 12px 12px;
      }
      button {
        width: 100%;
        height: 42px;
        font-size: 14px;
        border: 1px solid #3395FF;
        background: #3395FF;
        color: #fff;
        cursor: pointer;
      }

      .accordion-container {
        border: 1px solid #ccc;
        border-radius: 0 2px 2px 0;
      }

      .accordion-container > div:nth-child(n+2) {
        border-top: 1px solid #ccc;
      }

      .accordion-heading {
        padding: 10px;
        display: block;
        cursor: pointer;
      }

      div:not(.disabled) > .accordion-heading:hover {
          background: #fcfcfc;
      }

      main {
        padding: 0 12px;
      }

      .content {
        max-height: 0;
        opacity: 0;
        transform: scale(0.8);
        transition: all 0.2s cubic-bezier(.4,1,1,1);
        pointer-events: none;
        font-size: 14px;
        line-height: 18px;
      }

      .content > div {
        padding: 0px 12px;
      }

      #section1, #section2, #section3 {
        position: relative;
      }

      .separate {
        border-top: 1px solid rgba(0,0,0,0.2);
      }

      .accordion-heading svg {
        display: inline-block;
        margin-right: 10px;
        vertical-align: middle;
      }

      .title {
        display: inline-block;
        font-size: 16px;
        color: rgba(0,0,0,0.7);
        line-height: 20px;
        vertical-align: middle;
      }

      .sub-title {
        font-size: 12px;
        font-weight: 600;
        color: rgba(0,0,0,0.4);
      }

      /* input[name="auth_type"][id="content2"]:checked + .arrow + .content {
        display: block;
        opacity: 1;
        transform: scale(1);
        max-height: 300px;
        padding-bottom: 16px;
        pointer-events: auto;
      } */

      .arrow {
        width: 20px;
        height: 20px;
        position: absolute;
        right: 10px;
        top: 20px;
        background: #fff;
        border: 1px solid #ccc;
        pointer-events: none;
        border-radius: 50%;
      }

      .arrow:before {
        content: '✓';
        position: absolute;
        font-size: 14px;
        font-weight: bold;
        top: -1px;
        left: 4px;
        color: #ccc;
        transition: all 0.3s;
      }

    input[name="auth_type"]:checked + .arrow {
      background: #3395FF;
      border-color: #3395FF;
    }

    input[name="auth_type"]:checked + .arrow:before {
      color: #fff;
    }

    #help-container {
      position: relative;
    }
    #help {
       position: absolute;
       border-radius: 4px;
       width: 80%;
       padding: 0 10px;
       top: 90%;
       right: 0;
       font-size: 12px;
       opacity: 0;
       background: #555;
       color: #fff;
       z-index: 10;
       transition: all 0.2s;
       pointer-events: none;
    }

    #help::after {
      content: "";
      position: absolute;
      width: 0;
      height: 0;
      border-width: 5px;
      border-style: solid;
      border-color: transparent transparent #555;
      bottom: 100%;
      right: 16px;
      margin: 0 0 -1px -10px;
    }
    #icon {
      color: blue;
      position: absolute;
      right: 8px;
      top: 16px;
      font-weight: 600;
      font-size: 11px;
      cursor: pointer;
      opacity: 0;
      transition: all 0.2s;
    }

    .error {
        color: red !important;
    }

    #icon.show {
      opacity: 1;
    }

    #icon.show:hover + #help {
      opacity: 1 !important;
    }

    #help pre {
      white-space: pre-wrap;
      font-family: inherit
    }

    #help .key {
        font-weight: 600;
    }

    .disabled {
      background: #e8e8e8;
      opacity: 0.6;
    }

    .input-radio {
        position: relative;
    }

    .input-radio input[type="radio"] {
        position: absolute;
        opacity: 0;
        display: unset;
    }

    .input-radio input[type="radio"]:checked+ label {
        background: none !important;
    }

    .input-radio input[type="radio"]:checked+ label .radio-display::after {
        border-color: #fff;
    }

    .input-radio label {
        display: block;
        position: relative;
        cursor: pointer;
    }

    .input-radio label .radio-display {
        content: '';
        display: inline-block;
        vertical-align: middle;
        position: absolute;
        width: 16px;
        height: 16px;
        cursor: pointer;
        border-radius: 50%;
        background: #fff;
        border: 1px solid #ccc;
        transition: 0.2s;
        z-index: -2;
    }

    .input-radio label .radio-display::after {
        content: "";
        position: absolute;
        width: 4px;
        height: 9px;
        top: 2px;
        left: 6px;
        border: 1px solid #ccc;
        border-top: none;
        border-left: none;
        transition: 0.2s;
        transform: rotate(40deg);
    }

    .input-radio label .label-content {
        padding: 4px 0px 4px 24px;
        line-height: 16px;
    }

    .input-radio.centered label .radio-display {
        top: 50%;
        transform: translateY(-50%);
    }

    .input-radio:not(.centered) label .radio-display {
        top: 3px;
    }

    .input-radio input[type=radio]:focus:not(:checked) + label .radio-display {
        border-color: #3395FF;
    }

    .input-radio input[type=radio]:checked + label .radio-display {
        background-color: #3395FF;
        border-color: #3395FF;
    }

    .has-tooltip {
        position: relative;
        display: inline-block;
        text-decoration: underline;
        cursor: pointer;
    }

    .has-tooltip .tooltip {
        z-index: -1;
        display: block;
        position: absolute;
        background: rgba(0,0,0,0.8);
        border-radius: 3px;
        color: #fff;
        padding: 12px;
        transition: 0.3s opacity;
        opacity: 0;
        pointer-events: none;

        margin-left: 8px;
        margin-top: 8px;
        width: 200px;
    }

    .has-tooltip .tooltip::before {
        content: '';
        display: block;
        position: absolute;
        width: 0;
        height: 0;

        border-left: 5px solid transparent;
        border-right: 5px solid transparent;
        border-bottom: 5px solid #000;
        top: -5px;
    }

    .has-tooltip a {
        color: inherit;
    }

    .has-tooltip:hover .tooltip,
    .has-tooltip:active .tooltip {
        opacity: 1;
        pointer-events: all;
        z-index: 4;
    }

    .emandate-education-text {
        color: rgba(81,89,120,0.7);
        margin-bottom: 8px;
        border-top: 1px solid #e6e7e8;
        padding-top: 16px !important;
    }

    .emandate-education-text p {
        margin: 0;
        margin-bottom: 4px;
    }

    .hidden {
        display: none;
    }

    #auth-btn {
        z-index: 1;
    }

    .steps {
        color: rgba(81,89,120,0.7);
    }

    .steps h6 {
        font-size: 14px;
        margin-top: 16px;
        margin-bottom: 8px;
    }

    .steps div:not(:last-child) {
        margin-bottom: 8px;
    }

    #emandate-aadhaar-radios .input-radio {
        display: inline-block;
    }

    #emandate-aadhaar-radios .input-radio:first-child {
        margin-right: 16px;
    }

    .steps {
        margin-top: 16px;
        border-top: 1px solid #e6e7e8;
    }

    .step {
        position: relative;
    }

    .step div {
        padding-left: 28px;
    }

    .step-icon {
        position: absolute;
        top: 0;
        left: 0;
        transform: scale(0.8);
    }

    .hidden {
        display: none;
    }
    </style>
  </head>
  <body>
    <img src={{ $data['org_logo'] }} id="logo" height="35px" style="height: 35px; margin: 20px auto;display: block;">
    <form action="{{ $data['request']['url'] }}" method="{{ $data['request']['method'] }}">
      @foreach ($data['request']['content']['input'] as $key => $value)
        @if (is_array($value))
          @foreach ($value as $key2=>$value2)
            <input type='hidden' name='{{$key}}[{{$key2}}]' value='{{$value2}}'>
          @endforeach
        @else
          <input type='hidden' name='{{$key}}' value='{{$value}}'>
        @endif
      @endforeach


      <header>
        <img src="https://cdn.razorpay.com/bank/{{ $data['request']['content']['input']['bank'] }}.gif" height= "30px">
        <div><b>{{ $data['request']['content']['bank_details']['name'] }}</b></div>
        @if (empty($data['request']['content']['input']['amount']) === false)
          <span>₹ {{ $data['request']['content']['input']['amount']/100 }}</span>
        @endif
      </header>
      <main>
        <div class="heading">Please fill Bank accounts details:</div>
        <div class="common">
          @include('emandate.commonFields')
        </div>
      </main>
      <main>
        <div class="heading">Please select Authentication method:</div>
        <div class="accordion-container">
        @if (in_array('debitcard', $data['request']['content']['bank_details']['auth_types']))
          <div id="section3">
            <label class="accordion-heading pickable" for="content3">
              <svg viewBox="0 0 27 22" width="24px" height="17px" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                <path d="M2 7v13h18v-5H7V7H2zm0-2h5v10h15v5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z" fill="#B70611"></path>
                <path d="M10.004 13.003a1 1 0 0 1 0-2h2a1 1 0 0 1 0 2h-2zM7 0h18a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2zm0 2v13h18V2H7zm-.282 5.005a1 1 0 1 1 0-2h19a1 1 0 0 1 0 2h-19z" fill="#B70611"></path>
              </svg>
              <span class="title">
                Debit Card
                <div class="sub-title">Via Debit Card details</div>
              </span>
            </label>
            <input type="radio" id="content3" name="auth_type" value="debitcard" hidden>
            <span class="arrow"></span>
          </div>
        @endif
        @if (in_array('netbanking', $data['request']['content']['bank_details']['auth_types']))
          <div id="section1">
            <label class="accordion-heading pickable" for="content1">
              <svg width="24px" height="17px" viewBox="0 0 24 17" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"> <title>Rectangle 88</title> <desc>Created with Sketch.</desc> <defs> <linearGradient x1="0%" y1="0%" x2="100%" y2="100%" id="linearGradient-1"> <stop stop-color="#EA3A44" offset="0%"></stop> <stop stop-color="#B70611" offset="100%"></stop> </linearGradient> </defs> <g id="Flow-1--Testing" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"> <g id="Both-HDFC--Aadhar" transform="translate(-28.000000, -502.000000)" fill="url(#linearGradient-1)"> <path d="M28,518.26087 L52,518.26087 L52,519 L28,519 L28,518.26087 Z M29.4117647,516.782609 L50.5882353,516.782609 L50.5882353,517.521739 L29.4117647,517.521739 L29.4117647,516.782609 Z M32.5351235,509.391304 L34.0468314,509.391304 L34.0468314,515.304348 L32.5351235,515.304348 L32.5351235,509.391304 Z M37.0702471,509.391304 L38.5819549,509.391304 L38.5819549,515.304348 L37.0702471,515.304348 L37.0702471,509.391304 Z M41.6053706,509.391304 L43.1170784,509.391304 L43.1170784,515.304348 L41.6053706,515.304348 L41.6053706,509.391304 Z M46.1404941,509.391304 L47.652202,509.391304 L47.652202,515.304348 L46.1404941,515.304348 L46.1404941,509.391304 Z M29.4117647,507.173913 L50.5882353,507.173913 L50.5882353,507.913043 L29.4117647,507.913043 L29.4117647,507.173913 Z M29.4117647,505.464674 L40,502 L50.5882353,505.464674 L50.5882353,506.157609 L29.4117647,506.157609 L29.4117647,505.464674 Z" id="Rectangle-88"></path> </g> </g> </svg>
              <span class="title">
                Netbanking
                <div class="sub-title">Via Netbanking login</div>
              </span>
            </label>
            <input type="radio" id="content1" name="auth_type" value="netbanking" hidden checked>
            <span class="arrow"></span>
          </div>
        @endif
        @if (in_array('aadhaar', $data['request']['content']['bank_details']['auth_types']))
          <div id="section2">
            <label class="accordion-heading pickable" for="content2">
              <svg width="24px" height="16px" viewBox="0 0 24 16" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"> <title>Group 11</title> <desc>Created with Sketch.</desc> <defs> <linearGradient x1="0%" y1="54.6514554%" x2="103.027754%" y2="39.6782836%" id="linearGradient-1"> <stop stop-color="#F53742" offset="0%"></stop> <stop stop-color="#CB0D1A" offset="98.6427774%"></stop> </linearGradient> <linearGradient x1="0%" y1="0%" x2="100%" y2="100%" id="linearGradient-2"> <stop stop-color="#EA3A44" offset="0%"></stop> <stop stop-color="#B70611" offset="100%"></stop> </linearGradient> </defs> <g id="Flow-1--Testing" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"> <g id="Group-11"> <g id="Group-2"> <rect id="cards-base" stroke="url(#linearGradient-1)" stroke-width="0.639" x="0.902833333" y="0.930611111" width="22.361" height="14.6943333" rx="1.27777778"></rect> <polygon id="Rectangle-24" fill="url(#linearGradient-2)" points="2.66666667 3.66666667 20.9966666 3.66666667 20.9966666 5 2.66666667 5"></polygon> <polygon id="Rectangle-24" fill="url(#linearGradient-2)" points="2.66666667 8 7.33333333 8 7.33333333 12.6666667 2.66666667 12.6666667"></polygon> <polygon id="Rectangle-24" fill="url(#linearGradient-2)" points="8.66666667 8.33333333 18 8.33333333 18 9.66666667 8.66666667 9.66666667"></polygon> <polygon id="Rectangle-24" fill="url(#linearGradient-2)" points="8.66666667 11 20.9966666 11 20.9966666 12.3333333 8.66666667 12.3333333"></polygon> </g> </g> </g> </svg>
              <span class="title">
                Aadhaar
                <div class="sub-title">Via Aadhaar Number</div>
              </span>
            </label>
            <input type="radio" id="content2" name="auth_type" value="aadhaar" hidden>
            @if (in_array('aadhaar', $data['request']['content']['bank_details']['auth_types']))
              <span class="arrow"></span>
            @endif

            <div class="content">
                @if (isset($data['request']['content']['input']['aadhaar[vid]']) && $data['request']['content']['input']['aadhaar[vid]'])
                    <div id="prefilled-aadhaar">
                        Aadhaar Virtual ID:
                        <br />
                        <strong>{{ $data['request']['content']['input']['aadhaar[vid]'] }}</strong>
                        <input type="hidden" name="aadhaar[vid]" disabled value="{{ $data['request']['content']['input']['aadhaar[vid]'] }}"/>
                    </div>
                @else
                    <div class="emandate-education-text">
                        <p>You need your Aadhaar VID to make payment.</p>
                        <div class='has-tooltip'>
                        <div class="text">What is VID?</div>
                        <div class="tooltip">
                            It is a 16-digit number introduced by UIDAI so that Aadhaar holders can use it instead of their Aadhaar number to maintain privacy.
                        </div>
                        </div>
                    </div>
                    <div id="emandate-aadhaar-radios">
                        <div class="input-radio">
                            <input type="radio" id="emandate-aadhaar-radio-no" value="no" checked>
                            <label for="emandate-aadhaar-radio-no">
                                <div class="radio-display"></div>
                                <div class="label-content">I do not have a VID</div>
                            </label>
                        </div>
                        <div class="input-radio">
                            <input type="radio" id="emandate-aadhaar-radio-yes" value="yes">
                            <label for="emandate-aadhaar-radio-yes">
                                <div class="radio-display"></div>
                                <div class="label-content">I have a VID</div>
                            </label>
                        </div>
                    </div>
                    <div class="steps">
                        <h6>Steps to complete payment:</h6>
                        <div class="step">
                            <svg class="step-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"><path d="M0 0h24v24H0z" fill="none"/><path d="M3 5H1v16l2 2h16v-2H3V5zm11 10h2V5h-4v2h2v8zm7-14H7L5 3v14l2 2h14l2-2V3l-2-2zm0 16H7V3h14v14z" fill="#3395FF" /></svg>
                            <div>Generate/Retrieve 16-digit VID by using your Aadhaar Number & OTP</div>
                        </div>
                        <div class="step">
                            <svg class="step-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"><path d="M0 0h24v24H0z" fill="none"/><path d="M3 5H1v16l2 2h16v-2H3V5zm18-4H7L5 3v14l2 2h14l2-2V3l-2-2zm0 16H7V3h14v14zm-4-4h-4v-2h2l2-2V7l-2-2h-4v2h4v2h-2l-2 2v4h6v-2z" fill="#3395FF" /></svg>
                            <div>Come back to this page and complete the payment using <strong>VID</strong> received on your Aadhaar <strong>registered mobile number</strong></div>
                        </div>
                    </div>
                @endif
            </div>
          </div>
        @endif
        </div>
      </main>
      <div class="action">
         <button type="submit" id="auth-btn">Authenticate</button>
      </div>
    </form>
    <a href="https://resident.uidai.gov.in/web/resident/vidgeneration" id="uidai-link" target="_blank" class="hidden">
  </body>
  <script type="text/javascript">
    function changeAuthButtonText (text) {
        document.querySelector('#auth-btn').innerHTML = text;
    }

    function methodChangeListener (e) {
        var hasPrefill = document.querySelector('#prefilled-aadhaar');
        if (e.target.value === 'aadhaar') {
            if (!hasPrefill) {
                if (document.querySelector('#emandate-aadhaar-radio-no') && document.querySelector('#emandate-aadhaar-radio-no').checked) {
                    changeAuthButtonText('Create Aadhaar VID');
                } else if (document.querySelector('#emandate-aadhaar-radio-yes') && document.querySelector('#emandate-aadhaar-radio-yes').checked) {
                    changeAuthButtonText('Proceed to Authenticate');
                }
            }
        } else {
            changeAuthButtonText('Authenticate');
        }
    }

    function vidRadioChangeListener (e) {
        if (e.target.value === 'no') {
            changeAuthButtonText('Create Aadhaar VID');
            document.querySelector('#emandate-aadhaar-radio-yes').checked = false;
        } else {
            changeAuthButtonText('Proceed to Authenticate');
            document.querySelector('#emandate-aadhaar-radio-no').checked = false;
        }
    }

    function submitListener (e) {
        var hasPrefill = document.querySelector('#prefilled-aadhaar');
        var methodIsAadhaar = document.querySelector('#content2').checked;
        if (methodIsAadhaar && !hasPrefill) {
            var hasVID = document.querySelector('#emandate-aadhaar-radio-yes') && document.querySelector('#emandate-aadhaar-radio-yes').checked;
            if (!hasVID) {
                e.preventDefault();
                document.querySelector('#emandate-aadhaar-radio-no').checked = false;
                document.querySelector('#emandate-aadhaar-radio-yes').checked = true;
                changeAuthButtonText('Proceed to Authenticate');
                document.querySelector('#uidai-link').click();
            }
        }
        authorize();
    }

    function attachListeners () {
        var inputs = document.querySelectorAll('input[name="auth_type"]');
        for (var i = 0; i < inputs.length; i++) {
            inputs[i].addEventListener('change', methodChangeListener);
        }

        inputs = document.querySelectorAll('#emandate-aadhaar-radios input')
        for (var i = 0; i < inputs.length; i++) {
            inputs[i].addEventListener('change', vidRadioChangeListener);
        }

        document.querySelector('#auth-btn').addEventListener('click', submitListener);
    }

    // attachListeners();

    var data = {!! json_encode($data) !!};
    console.log('Data...', data);

    var nbMthd = data['request']['content']['bank_details']['auth_types'].indexOf('netbanking');
    var adrMthd = data['request']['content']['bank_details']['auth_types'].indexOf('aadhaar');

    {{-- If auth_type was previously passed, i.e. it is present in the input, select it. --}}
    var preselectedAuthType = data['request']['content']['input']['auth_type'];
    var isPreselectedAuthAvailable = data['request']['content']['bank_details']['auth_types'].indexOf(preselectedAuthType) > -1;

    var netbankingRadio = document.querySelector('input[type=radio][name=auth_type][value=netbanking]');
    var debitcardRadio = document.querySelector('input[type=radio][name=auth_type][value=debitcard]');

    if (isPreselectedAuthAvailable) {
      if (preselectedAuthType === 'netbanking') {
        debitcardRadio && (debitcardRadio.checked = false);
        netbankingRadio && (netbankingRadio.checked = true);
      } else if (preselectedAuthType === 'debitcard') {
        netbankingRadio && (netbankingRadio.checked = false);
        debitcardRadio && (debitcardRadio.checked = true);
      }
    }

    if (nbMthd === -1) {
      document.querySelector('#section1 label + input').setAttribute('disabled', true);

      if (adrMthd !== -1) {
        document.querySelector('#section2 label + input').checked = true;
      }
    }

    if (adrMthd === -1) {
      document.querySelector('#section2 label + input').setAttribute('disabled', true);
      document.querySelector('#section2 .content input').setAttribute('disabled', true);

      if (nbMthd !== -1) {
        document.querySelector('#section1 label + input').checked = true;
      }
    }


    function authorize() {
      if (document.getElementById('content1').checked) {
        document.querySelector('#section2 .content [name="aadhaar[vid]"]').setAttribute('disabled', true);
      }
    }

    document.getElementsByName('bank_account[ifsc]')[0].addEventListener('input', function(e) {
        document.querySelector('#help-container #icon').className = '';

        if(e.target.value.length === 11) {
           var IFSC = e.target.value;
            httpGetAsync('https://ifsc.razorpay.com/' + IFSC, function(data) {
                var helpMsg = 'Invalid IFSC Code';
                var cls = 'show';
                if (data) {
                    helpMsg = '';
                    var info = JSON.parse(data)
                    var info = {
                        Bank: info.BANK,
                        Branch: info.BRANCH,
                        City: info.CITY,
                        State: info.STATE
                    }
                    for (var i in info) {
                      helpMsg += '\n' + '<span class="key">' + i + '</span>' + ': ' + info[i];
                    }
                } else {
                    cls += ' error';
                }

                document.querySelector('#help-container #icon').className = cls;
                document.getElementById('help').innerHTML = '<pre>' + helpMsg + '</pre>';
            });
        }
        if (e.target.value.length > 11) {
            e.target.value = e.target.value.substring(0,11);
        }
    });

    function httpGetAsync(theUrl, callback) {
        var xmlHttp = new XMLHttpRequest();
        xmlHttp.onreadystatechange = function() {
            var res;
            if (xmlHttp.readyState == 4 && xmlHttp.status == 200) {
                res = xmlHttp.responseText;
            }
            callback(res);
        }
        xmlHttp.open("GET", theUrl, true); // true for asynchronous
        xmlHttp.send(null);
    }
  </script>
</html>
