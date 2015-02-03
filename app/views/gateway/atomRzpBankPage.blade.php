<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <title>Razorpay Bank</title>

    <meta http-equiv="pragma" content="no-cache">
    <meta http-equiv="cache-control" content="no-cache">
    <meta http-equiv="expires" content="0">
    <meta http-equiv="keywords" content="keyword1,keyword2,keyword3">
    <meta http-equiv="description" content="This is my page">

    <script>
    function transfer()
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
        document.forms[0].submit();
    }
    </script>


  </head>

  <body>
    Welcome to Razorpay Bank  <br />
    This is just a demo bank net-banking page. <br />
    You can choose whether to make this payment successful or not from
    following options. <br />

    <form  method="post" action="">
    Bank Transaction ID: {{{ $data['ITC'] }}}
    Amountt: {{{ $data['amount'] }}}
    <p>
        <input type="radio" name="success" checked="checked" value="S"> Success
        <input type="radio" name="success" value="F"> Failure
    </p>
    <p>
    <input type="button" value="Click To Transfer Funds" onclick="transfer();">
    </p>
    </form>
  </body>
</html>
