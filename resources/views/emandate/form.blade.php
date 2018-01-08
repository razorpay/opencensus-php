<!doctype html>
<html>
  <head>
    <title>Emandate Processing...</title>
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

      .common input {
        margin: 5px 0 12px;
      }
      input {
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
        height: 0;
        opacity: 0;
        transform: scale(0.8);
        overflow: hidden;
        transition: all 0.2s cubic-bezier(.4,1,1,1);
      }

      .content > div {
        padding: 20px 12px;
      }

      #section1 {
        position: relative;
      }

      #section2 {
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

      input[name="auth_type"][id="content2"]:checked + .arrow + .content {
        display: block;
        opacity: 1;
        transform: scale(1);
        height: 85px;
      }

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


    </style>
  </head>
  <body>
    <img src="https://cdn.razorpay.com/logo.svg" id="logo" height="35px" style="height: 35px; margin: 20px auto;display: block;">
    <form action="<?= $data['request']['url'] ?>" method="<?= $data['request']['method'] ?>">
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
        <span>₹ {{ $data['request']['content']['input']['amount']/100 }}</span>
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
          <div id="section1" class={{ in_array('netbanking', $data['request']['content']['bank_details']['auth_types']) ? '' : 'disabled'}}>
            <label class="accordion-heading pickable" for="content1">
              <svg width="24px" height="17px" viewBox="0 0 24 17" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"> <title>Rectangle 88</title> <desc>Created with Sketch.</desc> <defs> <linearGradient x1="0%" y1="0%" x2="100%" y2="100%" id="linearGradient-1"> <stop stop-color="#EA3A44" offset="0%"></stop> <stop stop-color="#B70611" offset="100%"></stop> </linearGradient> </defs> <g id="Flow-1--Testing" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"> <g id="Both-HDFC--Aadhar" transform="translate(-28.000000, -502.000000)" fill="url(#linearGradient-1)"> <path d="M28,518.26087 L52,518.26087 L52,519 L28,519 L28,518.26087 Z M29.4117647,516.782609 L50.5882353,516.782609 L50.5882353,517.521739 L29.4117647,517.521739 L29.4117647,516.782609 Z M32.5351235,509.391304 L34.0468314,509.391304 L34.0468314,515.304348 L32.5351235,515.304348 L32.5351235,509.391304 Z M37.0702471,509.391304 L38.5819549,509.391304 L38.5819549,515.304348 L37.0702471,515.304348 L37.0702471,509.391304 Z M41.6053706,509.391304 L43.1170784,509.391304 L43.1170784,515.304348 L41.6053706,515.304348 L41.6053706,509.391304 Z M46.1404941,509.391304 L47.652202,509.391304 L47.652202,515.304348 L46.1404941,515.304348 L46.1404941,509.391304 Z M29.4117647,507.173913 L50.5882353,507.173913 L50.5882353,507.913043 L29.4117647,507.913043 L29.4117647,507.173913 Z M29.4117647,505.464674 L40,502 L50.5882353,505.464674 L50.5882353,506.157609 L29.4117647,506.157609 L29.4117647,505.464674 Z" id="Rectangle-88"></path> </g> </g> </svg>
              <span class="title">
                Netbanking
                <div class="sub-title">Via Netbanking login</div>
              </span>
            </label>
            <input type="radio" id="content1" name="auth_type" value="netbanking" hidden>
            @if (in_array('netbanking', $data['request']['content']['bank_details']['auth_types']))
                <span class="arrow"></span>
            @endif
          </div>

          <div class="separate"></div>
          <div id="section2" class={{ in_array('aadhaar', $data['request']['content']['bank_details']['auth_types']) ? '' : 'disabled'}}>
            <label class="accordion-heading pickable" for="content2">
              <svg width="24px" height="16px" viewBox="0 0 24 16" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"> <title>Group 11</title> <desc>Created with Sketch.</desc> <defs> <linearGradient x1="0%" y1="54.6514554%" x2="103.027754%" y2="39.6782836%" id="linearGradient-1"> <stop stop-color="#F53742" offset="0%"></stop> <stop stop-color="#CB0D1A" offset="98.6427774%"></stop> </linearGradient> <linearGradient x1="0%" y1="0%" x2="100%" y2="100%" id="linearGradient-2"> <stop stop-color="#EA3A44" offset="0%"></stop> <stop stop-color="#B70611" offset="100%"></stop> </linearGradient> </defs> <g id="Flow-1--Testing" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"> <g id="Group-11"> <g id="Group-2"> <rect id="cards-base" stroke="url(#linearGradient-1)" stroke-width="0.639" x="0.902833333" y="0.930611111" width="22.361" height="14.6943333" rx="1.27777778"></rect> <polygon id="Rectangle-24" fill="url(#linearGradient-2)" points="2.66666667 3.66666667 20.9966666 3.66666667 20.9966666 5 2.66666667 5"></polygon> <polygon id="Rectangle-24" fill="url(#linearGradient-2)" points="2.66666667 8 7.33333333 8 7.33333333 12.6666667 2.66666667 12.6666667"></polygon> <polygon id="Rectangle-24" fill="url(#linearGradient-2)" points="8.66666667 8.33333333 18 8.33333333 18 9.66666667 8.66666667 9.66666667"></polygon> <polygon id="Rectangle-24" fill="url(#linearGradient-2)" points="8.66666667 11 20.9966666 11 20.9966666 12.3333333 8.66666667 12.3333333"></polygon> </g> </g> </g> </svg>
              <span class="title">
                Aadhaar
                <div class="sub-title">Via Aadhaar linked mobile OTP</div>
              </span>
            </label>
            <input type="radio" id="content2" name="auth_type" value="aadhaar" hidden>
            @if (in_array('aadhaar', $data['request']['content']['bank_details']['auth_types']))
              <span class="arrow"></span>
            @endif

            <div class="content">
                <div>
                  <input
                    name='aadhaar[number]''
                    type="tel"
                    pattern='^\d{12}$'
                    required
                    placeholder='Enter your Aadhaar number'
                    value={{ $data['request']['content']['input']['aadhaar']['number'] ?? "" }} >
              </div>
            </div>
          </div>
        </div>
      </main>
      <div class="action">
         <button type="submit" onclick="authorize()">Authenticate</button>
      </div>
    </form>
  </body>
  <script type="text/javascript">
    var data = {!! json_encode($data) !!};
    console.log('Data...', data);

    var nbMthd = data['request']['content']['bank_details']['auth_types'].indexOf('netbanking');
    var adrMthd = data['request']['content']['bank_details']['auth_types'].indexOf('aadhaar');

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
        document.querySelector('#section2 .content [name="aadhaar[number]"]').setAttribute('disabled', true);
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
