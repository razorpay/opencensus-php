<!doctype html>
<html>
  <head>
    <title>Processing, Please wait...</title>
    <meta name="viewport" content="width=device-width">
    <meta charset="utf-8">
  </head>
  <body onload="document.forms[0].action=location.hash.slice(1);document.forms[0].submit()">
    <form method="post">
      @foreach ($params as $key => $value)
        <input type="hidden" name="{{{ $key }}}" value="{{{ $value }}}">
      @endforeach
    </form>
  </body>
</html>
