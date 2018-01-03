<!doctype html>
<html>
  <head>
    <title>Processing, Please wait...</title>
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
          padding: 4px 15px;
          margin: 20px 0 10px;
          color: #606060;
      }
      main {
        padding: 0 20px;
      }
      main input {
        margin: 5px 0 12px;
      }
      input {
        border: 1px solid #bbb;
        box-shadow: 0 2px 3px rgba(0,0,0,0.1) inset;
        border-radius: 2px;
        height: 36px;
        outline: none;
        width: 100%;
        box-sizing: border-box;
        -webkit-box-sizing: border-box;
        padding: 0 16px;
        font-size: 14px;
        font-family: inherit;
        color: #111;
      }
      .action {
        width: 100%;
        text-align: center;
      }
      button {
        height: 42px;
        padding: 0 20px;
        margin: 16px 10px;
        font-size: 14px;
        border: 1px solid #3395FF;
        background: #3395FF;
        color: #fff;
        cursor: pointer;
      }

      input[name=aadhaar] {
        padding-left: 58px;
      }

      .accordion-container {
        margin: 12px;
        border: 1px solid #ccc;
        border-radius: 0 2px 2px 0;
      }

      .accordion-heading {
        padding: 10px;
        display: block;
        //background-color: rgba(0,0,0,0.04);
        background: linear-gradient(180deg,rgba(0,0,0,0.01), rgba(0,0,0,0.06));
        cursor: pointer;
      }

      .accordion-heading:hover {
          background-color: rgba(0,0,0,0.04);
      }

      .content {
        height: 0;
        opacity: 0;
        transform: scale(0.8);
        overflow: hidden;
        transition: all 0.2s cubic-bezier(.4,1,1,1);
      }

      .content > div {
        padding: 20px 15px;
      }

      #section1 {
        position: relative;
      }

      #section2 {
        position: relative;
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

      input[name="accordion"][id="content2"]:checked + .arrow + .content {
        display: block;
        opacity: 1;
        transform: scale(1);
        height: 85px;
      }

      input[name="accordion"]:checked + .arrow:before {
        opacity: 1;
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
      }

      .arrow:before {
        content: '✓';
        position: absolute;
        font-size: 20px;
        font-weight: bold;
        top: -6px;
        left: 2px;
        color: #35b61b;
        opacity: 0;
        transition: all 0.3s;
      }

    </style>
  </head>
  <body>
    <img src="https://cdn.razorpay.com/logo.svg" id="logo" height="35px" style="height: 35px; margin: 20px auto;display: block;">
    <form action="<?= $data['request']['url'] ?>" method="<?= $data['request']['method'] ?>">
      @foreach ($data['request']['content'] as $key => $value)
        @if (is_array($value))
          @foreach ($value as $key2=>$value2)
            <input type='hidden' name='{{$key}}[{{$key2}}]' value='{{$value2}}'>
          @endforeach
        @else
          <input type='hidden' name='{{$key}}' value='{{$value}}'>
        @endif
      @endforeach
      <header>
        <img src="https://cdn.razorpay.com/bank/UTIB.gif" height= "30px">
        <div><b>Punjab And Maharashtra Bank</b></div>
        <span>₹ {{ $data['request']['content']['amount']/100 }}</span>
      </header>
        <div class="heading">Please fill Bank accounts details:</div>
        <main>
            @include('emandate.commonFields')
        </main>
        <div class="heading">Please select Authentication method:</div>
        <div class="accordion-container">
            <div id="section1">
                <label class="accordion-heading pickable" for="content1">
                    <img src="https://cdn.razorpay.com/bank/HDFC.gif" height="25px" />
                    <span class="title">
                        Netbanking
                        <div class="sub-title">Via Netbanking login</div>
                    </span>

                </label>
                <input type="radio" id="content1" name="accordion" hidden />
                <span class="arrow"></span>
            </div>

            <div id="section2">
                <label class="accordion-heading pickable" for="content2">
                    <img src="https://cdn.razorpay.com/bank/HDFC.gif" height="25px" />
                    <span class="title">
                        Aadhaar
                        <div class="sub-title">Via Aadhaar linked mobile OTP</div>
                    </span>
                </label>
                <input type="radio" id="content2" name="accordion" hidden />
                <span class="arrow"></span>
                <div class="content">
                    <div class="form">
                        <input
                          name='aadhaar_number'
                          type='number'
                          pattern='^\d{12}$'
                          required
                          placeholder='Enter your Aadhaar number'
                          value={{ $data['request']['content']['aadhaar_number'] ?? "" }} />
                    </div>
                </div>
            </div>
        </div>
        <div class="action">
           <button type="submit">Authenticate</button>
        </div>
    </form>
  </body>
</html>
