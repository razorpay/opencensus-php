<!doctype html>
<html lang="en">
    <body>
    <form name="form1" action="{{$data['request']['url']}}" method="post">

@foreach ($data['request']['content'] as $key => $value)
        <input type="text" name="{{$key}}" value="{{$value}}">
        <br />
@endforeach
        <input type="submit" value="Submit" >
    </form>
    <br>
    Submit within 30 secs max!
    </body>
</html>