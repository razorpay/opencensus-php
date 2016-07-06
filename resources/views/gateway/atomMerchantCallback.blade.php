<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />

<script>
    function sub() {
        document.merchant_callback.submit();
    }
</script>

</head>

<body onload="sub();">

<form id="merchant_callback" name="merchant_callback" onsubmit="return true;" action="{{{ $url }}}" method="post">
@foreach ($data as $key => $value)
    <input type="hidden" name="{{{ $key }}}" value="{{{ $value }}}" />
@endforeach
</form>

</body>
</html>
