<!doctype html>
<html>
  <head>
    <title>Processing, Please wait...</title>
    <meta name="viewport" content="width=device-width">
    <meta charset="utf-8">
    @include('partials.loader')
  </head>
  <body onload="document.forms[0].action=location.hash.slice(1);document.forms[0].submit">
    <div class="loader vis" style="position:absolute;top:115px;left:50%;margin-left:-12px"></div>
    <form method="post">
      @foreach ($params as $key => $value)
        <input type="hidden" name="{{{ $key }}}" value="{{{ $value }}}">
      @endforeach
    </form>
  </body>
</html>
