<!doctype html>
<html style="height:100%;width:100%;">
<head>
<title>Processing, Please Wait...</title>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="{{$data['theme']['color']}}">
<meta http-equiv="refresh" content="0;url={{ $data['request']['url'] }}" />
@include('partials.redirectStyles')
</head>
<body>
  <div id='bg'></div>
  <div style="display:inline-block;vertical-align:middle;height:100%"></div>
  <div id='cntnt'>
    <div id="hdr">
      @if (isset($data['image']))
      <div id="logo"><img src="{{$data['image']}}"/></div>
      @endif
      <div id='name'>
        @if (isset($data['name']))
          {{$data['name']}}
        @else
          Redirecting...
        @endif
      </div>
    </div>
    <div id="ldr"></div>
    <div id="txt">
      <div style="display:inline-block;vertical-align:middle;white-space:normal;">
        <h2 id='title'>Loading Bank page&#x2026;</h2>
        <p id='msg'>Please wait while we redirect you to your Bank page</p>
      </div>
      <div style="display:inline-block;vertical-align:middle;height:100%"></div>
    </div>
    <div id='ftr'>
      <div style="display:inline-block;">Secured by <img style="vertical-align:middle;margin-bottom:5px;" height="20px" src="https://cdn.razorpay.com/logo.svg"></div>
      <div style="display:inline-block;vertical-align:middle;height:100%"></div>
    </div>
  </div>
  <script>
    setTimeout(function() {
      document.body.className = 'loaded';
    }, 10);

    setTimeout(function(){
      document.getElementById('title').innerHTML = 'Still trying to load...';
      document.getElementById('msg').innerHTML = 'The bank page is taking time to load.';
    }, 10000);
  </script>
</body>
</html>
