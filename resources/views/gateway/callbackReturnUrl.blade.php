<!doctype html>
<html>
  <head>
    <title>Processing, Please wait...</title>
    <meta name="viewport" content="width=device-width">
    <meta charset="utf-8">
    @include('partials.loader')
  </head>
  <body onload="document.forms[0].submit()">
    <form action="<?= $data['request']['url'] ?>" method="post">
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
