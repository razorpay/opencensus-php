<!doctype html>
<html lang='en' style='background: #f3f3f3; color: #333; height: 100%;font-size: 16px;font-family:ubuntu,helvetica,sans-serif;text-align:center'>
  <head>
    <title>Fees Breakup</title>
    <meta charset='utf-8'>
    <meta http-equiv='pragma' content='no-cache'>
    <meta http-equiv='cache-control' content='no-cache'>
    <meta name='viewport' content='user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1'>
    <style>
      .btn{
        font-family: inherit;
        padding: 0 26px;
        text-decoration: none;
        border-radius: 2px;
        background: #1AACE5;
        color: #fff;
        border: 1px solid #1f8dd6;
        margin-top: 30px;
        line-height: 46px;
        font-size: 1.1em;
        cursor: pointer;
      }
      .btn:active{
        box-shadow: 0 0 0 1px rgba(0,0,0,.15) inset,0 0 6px rgba(0,0,0,.2) inset;
      }
      img{
        width: 192px;
      }
      p{
        font-style: italic;
        font-size: 14px;
        margin: 40px 0 0;
        position: relative;
        left: 16px;
      }
      body{
        height: 100%;
        margin: 0;
        white-space: nowrap;
      }
      .td{
        float: left;
        width: 50%;
        text-align: left;
        white-space: nowrap;
      }
      .r{
        text-align: right;
      }
      .b{
        font-weight: bold;
        border-top: 1px dashed #555;
        margin-top: 10px;
        padding-top: 10px;
      }
      span{
        line-height: 40px;
        display: block;
      }
      #receipt{
        background: #fff;
        padding: 20px 30px;
        box-shadow: 0 0 6px rgba(0, 0, 0, 0.1);
        position: relative;
      }
      #receipt:before, #receipt:after{
        content: '';
        width: 100%;
        position: absolute;
        height: 6px;
        left: 0;
        background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAGCAYAAAD68A/GAAAABGdBTUEAALGPC/xhBQAAAFFJREFUCB1jYEAD////zwJhNGFULlCBCxD/hmIXVFkoDyipBsTvgBgGQGw1FMVAAUEgvglTgUSDxATBioEMFiDejSSJzgTJsTAAianoMlj4UwHZ5JVrRZTiPgAAAABJRU5ErkJggg==);
      }
      #receipt:before{
        top: -4px;
      }
      #receipt:after{
        bottom: -5px;
        transform: rotateX(180deg);
        -ms-transform: rotateX(180deg);
        -moz-transform: rotateX(180deg);
        -webkit-transform: rotateX(180deg);
      }
      form{
        vertical-align: middle;
        display: inline-block;
        white-space: normal;
        width: 80%;
        max-width: 290px;
        margin: 30px 0;
      }
    </style>
  </head>
  <body>
    <form action='{{$url}}' method='post'>
      @foreach ($input as $key=>$value)
        @if (is_array($value))
          @foreach ($value as $key2=>$value2)
            <input type='hidden' name='{{$key}}[{{$key2}}]' value='{{$value2}}'>
          @endforeach
        @else
          <input type='hidden' name='{{$key}}' value='{{$value}}'>
        @endif
      @endforeach
      <div id='receipt'>
        <h2 style='font-weight: normal'>Fees Breakup</h2>
        <div class='td'><span>Amount</span></div>
        <div class='td r'><span>₹{{$data['originalAmount']}}</span></div>
        <div class='td'><span>Gateway Fees</span></div>
        <div class='td r'><span>₹{{$data['razorpay_fee']}}</span></div>
        <div class='td'><span>GST</span></div>
        <div class='td r'><span>₹{{$data['serviceTax']}}</span></div>
        <div class='td b'><span>Total</span></div>
        <div class='td r b'><span>₹{{$data['amount']}}</span></div>
        <div style='clear: both'></div>
        <input class='btn' type='submit' value='Continue'>
      </div>
      <p>Secure payments by</p>
      <img src='data:image/png;base64,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'>
    </form>
    <div style='vertical-align: middle; display: inline-block; height: 96%'></div>
  </body>
</html>
