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
        max-width: 410px;
        margin: 20px auto 0px;
        background: #fff;
        border-radius: 4px;
        box-shadow: 0 2px 7px rgba(0,0,0,0.09);
      }
      header {
        border-bottom: 1px solid #eee;
        line-height: 64px;
        padding: 0 20px;
        text-align: right;
        font-size: 22px;
      }
      main {
        padding: 20px 20px 40px;
        text-align: center;
      }
      input {
        border: 1px solid #bbb;
        box-shadow: 0 2px 3px rgba(0,0,0,0.1) inset;
        border-radius: 2px 0 0 2px;
        height: 40px;
        outline: none;
        width: 160px;
        padding: 0 16px;
        font-family: inherit;
        color: #111;
        letter-spacing: 2px;
        font-size: 18px;
        font-weight: bold;
        vertical-align: bottom;
      }
      button {
        height: 42px;
        border: 1px solid #3395FF;
        background: #3395FF;
        border-radius: 0 2px 2px 0;
        color: #fff;
        vertical-align: bottom;
        margin-left: -1px;
        padding: 0 20px;
        cursor: pointer;
        outline: none;
      }
    </style>
  </head>
  <body>
    <img src="https://cdn.razorpay.com/logo.svg" id="logo" height="35px" style="margin:30px auto 10px; display:block">
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
        <img src="https://cdn.razorpay.com/wallet/{{ $data['request']['content']['wallet'] }}.png" style='float: left;height: 30px;margin: 17px 0;'>
        <div>₹ {{ $data['request']['content']['amount']/100 }}</div>
      </header>
      <main>
        Enter 10 digit Indian phone number associated with your {{ $data['request']['content']['wallet'] }} account
        <br><br>
        <input name='contact' autofocus type='tel' pattern='^\d{10}$' required><button>Submit</button>
      </main>
    </form>
  </body>
</html>
