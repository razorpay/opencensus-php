<!doctype html>
<html lang="en">
    <script>
        function sub() {
            document.form1.submit();
        }
    </script>

<body onload="sub();">
<div style="color: #555; font-weight: bold; text-align: center; position: absolute; top: 50%; width: 100%; margin-top: -30px;">
Please wait while your payment gets processed...
</div>
<form id="form1" name="form1" action="<?= $data['request']['url'] ?>" method="post" onsubmit="return true;">
    @foreach ($data['request']['content'] as $key => $value)
        <input type="hidden" name="{{{ $key }}}" value="{{{ $value }}}" />
    @endforeach
    <!-- <input type="submit" /> -->
</form>

<form id="form2" name="form2">
    <input type="hidden" name="type" value="return" />
</form>
</body>
</html>
