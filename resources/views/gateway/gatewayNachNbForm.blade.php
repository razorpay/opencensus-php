@extends('layouts.nach')
@section('content')
  <form name="form1" action="{{$data['request']['url']}}" method="post" style="margin: 40px 0 0; line-height: 46px; text-align: center;">
    <span onclick="cancelPayment(this)" style="color: #528FF0; cursor: pointer;">Cancel Payment</span>
    @foreach ($data['request']['content'] as $key => $value)
      <input type="hidden" name="{{$key}}" value="{{$value}}">
    @endforeach
    <button type="submit">Proceed</button>
  </form>
  <script>
    function cancelPayment(target) {
      target.style.pointerEvents = 'none';
      target.style.opacity = '0.5';
      var x = new XMLHttpRequest();
      var base = location.origin + "/v1/payments/{{$data['payment_id']}}/";
      x.onreadystatechange = function() {
        if (x.readyState === 4) {
          document.form1.innerHTML = '';
          document.form1.action = base + 'redirect_callback';
          document.form1.submit();
        }
      }
      x.open('get', base + 'cancel');
      x.send();
    }
  </script>
@endsection