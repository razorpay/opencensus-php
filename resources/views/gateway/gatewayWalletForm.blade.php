<!doctype html>
<html>
  <head>
    <title>Processing, Please wait...</title>
    <meta name="viewport" content="width=device-width">
    <meta charset="utf-8">
    @include('partials.loader')
  </head>
  <body onload="document.forms[0].submit()">
    <form action="<?= $data['request']['url'] ?>" method="<?= $data['request']['method'] ?>">
      @foreach ($data['request']['content'] as $key => $value)
        @if (is_array($value))
          @foreach ($value as $key2=>$value2)
            <input type='hidden' name='{{$key}}[{{$key2}}]' value='{{$value2}}'>
          @endforeach
        @else
          <input type='hidden' name='{{$key}}' value='{{$value}}'>
        @endif
      @endforeach
      <!-- <input type="submit" /> -->
    </form>
  </body>
</html>
