<!doctype html>
<html>
  <head>
    <title>Razorpay Bank</title>
    <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">
    <link rel="icon" href="/favicon.ico" type="image/x-icon" />
    <meta http-equiv="pragma" content="no-cache">
    <meta http-equiv="cache-control" content="no-cache">
    <meta http-equiv="expires" content="0">
    <style>
        img{max-width: 100%; height: auto;}
        body{font-family: ubuntu,helvetica,verdana,sans-serif; font-size: 14px; text-align: center; color: #414141; padding-top: 40px; line-height: 24px;background:#fff;}
        label{position: absolute; top: 0; left: 0; right: 0; height: 100%; line-height: 32px; padding-left: 30px;}
        input[type=button]{
            font-family: inherit;
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 2px;
            border: 0;
            width: 124px;
            background: none;
            margin: 0 5px;
            color: #fff;
            cursor: pointer;
            -webkit-appearance: none;
        }
        input[type=button]:hover{background-image: linear-gradient(transparent,rgba(0,0,0,.05) 40%,rgba(0,0,0,.1))}
        .grey{color: #777; margin-top: 20px; font-size: 12px; line-height: 18px;}
        .danger{background-color: #EF6050!important}
        .success{background-color: #61BC6D!important}
    </style>

    <script>
    function transfer(el)
    {
        document.getElementById('success').value = el.getAttribute('data-value');
        document.forms[0].submit();
    }
    </script>

  </head>
  <body>
    <h1><img src="/logo.gif" width="400" height="104"></h1>
    <h3>Welcome to Razorpay Bank</h3>
    This is just a demo bank page.<br>
    You can choose whether to make this payment successful or not: <br>
    <form  method="post" action="{{{ $url }}}">
    <p>
        <input type="button" value="Success" data-value="S" onclick="transfer(this)" class="success">
        <input type="button" value="Failure" data-value="F" onclick="transfer(this)" class="danger">
        <input type="hidden" name="callback_url" value="{{{ $content['callback_url'] }}}">
        <input type="hidden" name="success" id="success">
    </p>
    <p>

    </p>
    <p class="grey">
    </p>
    </form>
  </body>
</html>
