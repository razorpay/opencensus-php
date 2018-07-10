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
      .btn[disabled]{
        opacity: 0.6;
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
        width: 92%;
        max-width: 320px;
        margin: 30px 0;
      }
      #pro {display: none}
      @keyframes spin {
        0% {
            transform: scale(0.5);
            opacity: 0;
            border-width: 8px;
        }

        20% {
            transform: scale(0.6);
            opacity: 0.8;
            border-width: 4px;
        }

        90% {
            transform: scale(1);
            opacity: 0;
        }
      }

    .spin {
        margin: 0;
        margin-left: 8px;
        height: 1em;
        width: 1em;
        vertical-align: middle;
        display: inline-block;
        border-radius: 50%;
        border: 4px solid #ffffff;
        animation: spin 1.3s linear infinite;
        box-sizing: border-box;
        opacity: 0;
    }
    </style>
  </head>
  <body>
    <form action='{{$url}}' method='post'>
      @foreach ($input as $key=>$value)
          <input type='hidden' name='{{$key}}' value='{{$value}}'>
      @endforeach
      <div id='receipt'>
        <h2 style='font-weight: normal'>Fees Breakup</h2>
        <div class='td'><span>Amount</span></div>
        <div class='td r'><span>₹{{$data['originalAmount']}}</span></div>
        <div class='td'><span>Gateway Fees</span></div>
        <div class='td r'><span>₹{{$data['razorpay_fee']}}</span></div>
        <div class='td'><span>GST on Gateway Fees</span></div>
        <div class='td r'><span>₹{{$data['tax']}}</span></div>
        <div class='td b'><span>Total</span></div>
        <div class='td r b'><span>₹{{$data['amount']}}</span></div>
        <div style='clear: both'></div>
        <button class='btn' type='submit' value='continue'>Continue</button>
      </div>
      <p>Secure payments by</p>
      <img src='https://cdn.razorpay.com/logo.svg' height='40px'>
    </form>
    <div style='vertical-align: middle; display: inline-block; height: 96%'></div>
<script>
  document.forms[0].onsubmit = function() {
    var btn = document.querySelector('.btn');
    btn.disabled = true;
    btn.innerHTML = 'Processing';
    let span = document.createElement('span');
    span.className = 'spin';
    btn.appendChild(span);
  }
</script>
