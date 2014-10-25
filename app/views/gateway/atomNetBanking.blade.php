<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>

    <title>Razorpay Bank</title>

    <meta http-equiv="pragma" content="no-cache">
    <meta http-equiv="cache-control" content="no-cache">
    <meta http-equiv="expires" content="0">
    <meta http-equiv="keywords" content="keyword1,keyword2,keyword3">
    <meta http-equiv="description" content="This is my page">
    <!--
    <link rel="stylesheet" type="text/css" href="styles.css">
    -->

    <script>
    function transfer()
    {
        var status = "";
        for (var i=0; i<document.forms[0].success.length; i++) {
            if (document.forms[0].success[i].checked == true) {
                status = document.forms[0].success[i].value;
            }
        }

        var ITC = {{{$data['transaction_id']}}};
        var BID = {{{$data['transaction_id'].'1'}}};
        var amt = {{{$data['amount'].'00'}}};
        var clientCode = "007";
        //var url ="http://172.20.114.216/paynetz/atom?ITC=" + ITC + "&BID=" + BID + "&ClientCode=" + clientCode + "&amt=" + amt + "&Status=" + status;
        var url ="gateway/atom/netBanking?ITC=" + ITC + "&BID=" + BID + "&ClientCode=" + clientCode + "&amt=" + amt + "&Status=" + status;
        //var url ="http://payment.atomtech.in/paynetz/atom?ITC=" + ITC + "&BID=" + BID + "&ClientCode=" + clientCode + "&amt=" + amt + "&Status="+status;
        alert("url : " + url);
        document.forms[0].action=url;
        document.forms[0].submit();
    }
    </script>


  </head>

  <body>
    Welcome to Razorpay Bank  <br>

    <form  method="post" action="">
    Txn ID: {{{$data['transactio_id']}}}
    Amt: 50.0000
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
