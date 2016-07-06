<!doctype html>
<html lang="en">

    <body>
    <form id="form1" name="form1" action="{{$data['request']['url']}}" method="post" onsubmit="return true;">
        OTP <input type="text" name="otp" value="Fill the received otp">
        <br />
        <input type="hidden" name="type" value="otp">
    </form>
    <br>
    <form id="form2" name="form2">
        <input type="hidden" name="type" value="{{$data['type']}}">
        <input type="hidden" name="gateway" value="{{$data['gateway']}}">
    </form>

    </body>
</html>