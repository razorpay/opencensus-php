<!doctype html>
<html lang="en">
    <script>
        function sub() {
            document.form1.submit();
        }
    </script>

    <body onload="sub();">
    <form id="form1" name="form1" action="{{$data['request']['url']}}" method="post" onsubmit="return true;">

@foreach ($data['request']['content'] as $key => $value)
        <!-- {{$key}}: --> <input type="hidden" name="{{$key}}" value="{{$value}}">
        <br />
@endforeach
<!--         <input type="submit" value="Submit" >
 -->    </form>
    <br>
    <form id="form2" name="form2">
        <input type="hidden" name="type" value="{{$data['type']}}">
        <input type="hidden" name="gateway" value="{{$data['gateway']}}">
    </form>

    </body>
</html>