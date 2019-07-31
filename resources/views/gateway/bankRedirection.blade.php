<!doctype html>
<html>
  <head>
    <title>Processing, Please wait...</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width">
    <meta charset="utf-8">
    @include('partials.loader')
  </head>
  <body onload="document.forms[0].submit()">
    <form action="{{ $data['url'] }}" method="{{ $data['method'] }}">
      @foreach ($data['content'] as $key => $value)
          <input type="hidden" name="{{ $key }}" value="{{ $value }}" />
      @endforeach
      <!-- <input type="submit" /> -->
    </form>
  </body>
</html>
