<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <title>Razorpay Bank</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">
    <link rel="icon" href="/favicon.ico" type="image/x-icon" />
    <meta http-equiv="pragma" content="no-cache">
    <meta http-equiv="cache-control" content="no-cache">
    <meta http-equiv="expires" content="0">
    <style>
        img{max-width: 100%; height: auto;}
        body{font-family: sans-serif; font-size: 14px; text-align: center; color: #414141; padding-top: 40px; line-height: 24px;background:#fff;}
        label{position: absolute; top: 0; left: 0; right: 0; height: 100%; line-height: 32px; padding-left: 30px;}
        input[type=button]{display: inline-block; font-family: inherit; padding: 12px 20px; text-decoration: none; border-radius: 2px; border: none; width: 124px; background: none; margin: 0 5px; color: #fff; cursor: pointer;}
        input[type=button]:hover{background-image: linear-gradient(transparent,rgba(0,0,0,.05) 40%,rgba(0,0,0,.1))}
        .grey{color: #777; margin-top: 20px; font-size: 12px; line-height: 18px;}
        .danger{background-color: rgb(202, 60, 60)!important}
        .success{background-color: rgb(28, 184, 65)!important}
    </style>

    <script>
    function transfer(el)
    {
        var status = "";
        for(var i=0;i<document.forms[0].success.length;i++){
            if(document.forms[0].success[i].checked == true){
                status = document.forms[0].success[i].value;
            }
        }
        var ITC = {{{ $data['ITC'] }}};
        var BID = '{{{ $data['BID'] }}}';
        var amt = '{{{ $data['amount'] }}}';
        var clientCode = "{{{ $data['clientCode'] }}}";
        var tempTxnId = "{{{ $data['tempTxnId'] }}}";
        var url = "{{{ $data['url'] }}}" + "&ITC=" + ITC + "&BID=" + BID + "&ClientCode=" + clientCode + "&amt=" + amt + "&Status="+status;
        url = url + "&tempTxnId="+tempTxnId;

        document.forms[0].action=url;
        document.getElementById('success').value = el.getAttribute('data-value');
        document.forms[0].submit();
    }
    </script>


  </head>

  <body>
    <h1><img src="https://cdn.razorpay.com/logo.svg" width="316" height="67"></h1>
    <h3>Welcome to Razorpay Bank</h3>
    This is just a demo netbanking page.<br>
    You can choose whether to make this payment successful or not: <br>
    <form  method="post" action="" onsubmit="return false;">
    <p>
        <input type="button" value="Success" data-value="S" onclick="transfer(this)" class="success">
        <input type="button" value="Failure" data-value="F" onclick="transfer(this)" class="danger">
        <input type="hidden" name="success" id="success">
    </p>
    <p>

    </p>
    <p class="grey">
        Transaction ID: {{{ $data['ITC'] }}}<br>
        Amount: {{{ $data['amount'] }}}
    </p>
    </form>
  </body>
</html>
