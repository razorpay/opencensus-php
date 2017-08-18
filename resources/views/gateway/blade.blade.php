<!doctype html>
<html lang="en" style="height: 100%; overflow: hidden;font-family:ubuntu,helvetica,sans-serif;">
    <body>
    <form id="form1" name="form1">

@foreach ($data as $key => $value)
        <!-- {{$key}}: --> <input type="hidden" name="{{$key}}" value="{{$value}}">
        <br />
@endforeach
<!--         <input type="submit" value="Submit" >
 -->    </form>
    <br>

    </body>
</html>
