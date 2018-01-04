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

      div:not('.disabled') .accordion-heading:hover {
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

      .accordion-heading img {
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
              <img src="https://cdn.razorpay.com/bank/HDFC.gif" height="25px" />
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
              <img src="https://cdn.razorpay.com/bank/HDFC.gif" height="25px" />
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
         <button type="submit">Authenticate</button>
      </div>
    </form>
  </body>
  <script type="text/javascript">
    var data = {!! json_encode($data) !!};
    console.log('Data...', data);

    if (data['request']['content']['bank_details']['auth_types'].indexOf('netbanking') === -1) {
        document.querySelector('#section1 label + input').setAttribute('disabled', true);
    }

    if (data['request']['content']['bank_details']['auth_types'].indexOf('aadhaar') === -1) {
        document.querySelector('#section2 label + input').setAttribute('disabled', true);
        document.querySelector('#section2 .content input').setAttribute('disabled', true);
    }


    console.log(document.getElementsByName('bank_account[ifsc]')[0]);
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

                console.log('....here...', helpMsg);
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
