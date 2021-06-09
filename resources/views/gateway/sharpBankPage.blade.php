<!doctype html>
<html>
  <head>
    <title>Razorpay Bank</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">
    <meta http-equiv="pragma" content="no-cache">
    <meta http-equiv="cache-control" content="no-cache">
    <meta http-equiv="expires" content="0">
    <link rel="icon" href="/favicon.ico" type="image/x-icon" />
    <style>
      img{max-width: 100%; height: auto;}
      body{font-family: ubuntu,helvetica,verdana,sans-serif; font-size: 14px; text-align: center; color: #414141; padding-top: 40px; line-height: 24px;background:#fff;}
      label{position: absolute; top: 0; left: 0; right: 0; height: 100%; line-height: 32px; padding-left: 30px;}
      button{
        font-family: inherit;
        height: 60px;
        line-height: 60px;
        vertical-align: middle;
        text-decoration: none;
        border-radius: 2px;
        border: 0;
        width: 164px;
        font-size: 16px;
        background: none;
        margin: 0 5px;
        color: #fff;
        cursor: pointer;
        -webkit-appearance: none;
      }
      .em {
        font-size: 13px;
        line-height: 20px;
      }
      input[type=button]:hover{background-image: linear-gradient(transparent,rgba(0,0,0,.05) 40%,rgba(0,0,0,.1))}
      .grey{color: #777; margin-top: 20px; font-size: 12px; line-height: 18px;}
      .danger{background-color:#EF6050}
      .success{background-color:#27ae60}
      .warn{background-color:#e67e22}
    </style>
  </head>
  <body>
    <h1><img src="{{ $data['org_logo'] }}" width="316" height="67"></h1>
    <h3>Welcome to {{ $data['org_name'] }} Bank</h3>
    This is just a demo bank page.<br>
    You can choose whether to make this payment successful or not: <br><br>
    <form onsubmit="return false" method="post" action="{{{ $data['url'] }}}">
      @if ($data['method'] === 'emandate')
        <input type="hidden" name="emandate_success">
        <button data-val="S" class="success">Success</button>
        <button data-val="M" class="warn em">Payment successful but e-Mandate failed</button>
      @else
        <button data-val="S" class="success">Success</button>
      @endif
      <button data-val="F" class="danger">Failure</button>
      <input type="hidden" name="callback_url" value="{{{ $data['content']['callback_url'] }}}">
      @if (isset($data['language_code']) === true && str_contains($data['url'], '/gateway/mocksharp/payment/submit'))
        <input type="hidden" name="language_code" value="{{{ $data['language_code'] }}}">
      @endif
      <input type="hidden" name="success">
    </form>
    <script>
      document.forms[0].onclick = function(e) {
        var event = e || window.event;
        var target = event.target || event.srcElement;

        if (target.nodeName === 'BUTTON') {
          var value = target.getAttribute('data-val');
          var em = document.querySelector('[name=emandate_success]');
          var success = document.querySelector('[name=success]');
          if (em) {
            if (value === 'S') {
              success.value = em.value = 'S';
            } else if (value === 'M') {
              success.value = 'S';
              em.value = 'F';
            } else if (value === 'F') {
              success.value = 'F';
              em.parentNode.removeChild(em);
            }
          } else {
            success.value = value;
          }
          document.forms[0].submit();
        }
      }
    </script>
  </body>
</html>
