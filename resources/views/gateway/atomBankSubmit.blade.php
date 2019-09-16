<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Razorpay Test Bank Choice</title>

<script>
    function sub() {
        document.redirect.submit();
    }
</script>

</head>

<body onload="sub();">
This page will be auto-submitted <br />
<form id="redirect" name="redirect" onsubmit="return true;" action="{{{ $data['url'] }}}" method="post">
@if ($data['method'] === 'netbanking')
    <table class="wwFormTable">
        <select name="bankID" id="redirect_bankID">
            <option value="2001">Razorpay Test Bank</option>
        </select>
        <br /><br />
    </table>
@endif
    <input type="hidden" name="tempTxnId" value="{{{ $data['tempTxnId'] }}}" />
    <input type="hidden" name="348901664" value="25-0-1A77AE839A367C7EF0C0D9C6CA9BD2EC" />
    <input type="submit" value="submit" />
</form>

</body>
</html>
