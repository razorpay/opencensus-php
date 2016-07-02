<!doctype html>
<html style="height:100%">
  <head>
  <script>
try{
  var payment_id = "{{$data['payment_id']}}";
  if (typeof(CheckoutBridge) !== 'undefined' && typeof(CheckoutBridge.setPaymentID) === 'function') {
    CheckoutBridge.setPaymentID(payment_id);
  } else if(window.opener){
  opener.setPaymentID(payment_id);
  }
} catch(e){}
</script>
    <title>Processing, Please Wait...</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="pragma" content="no-cache">
    <meta http-equiv="cache-control" content="no-cache">
    <style>
#top{text-align:left;border-bottom:1px solid #ddd;padding-bottom:16px;margin-bottom:-50px}
.spin{width:60px;height:60px;margin:0 auto;margin-bottom:-60px;position:relative;top:-30px}
.spin div{width:100%;height:100%;vertical-align:middle;display:inline-block;
  opacity:0;border-radius:50%;border:4px solid #BC3726;
  -webkit-animation:spin 1.3s linear infinite;animation:spin 1.3s linear infinite;
  -webkit-box-sizing:border-box;box-sizing:border-box}
#spin2 div{-webkit-animation-delay:0.65s;animation-delay:0.65s}
@-webkit-keyframes spin{0%{-webkit-transform:scale(0.5);opacity:0;border-width:8px}
20%{-webkit-transform:scale(0.6);opacity:0.8;border-width:4px}
90%{-webkit-transform:scale(1);opacity:0}}
@keyframes spin{0%{transform:scale(0.5);opacity:0;border-width:8px}
20%{transform:scale(0.6);opacity:0.8;border-width:4px}
90%{transform:scale(1);opacity:0}}
@media(max-height:400px){#top{border:none}}
    </style>
  </head>
  <body onload="document.form1.submit()" style="overflow:hidden;text-align:center;height:100%;white-space:nowrap;margin:0;padding:0;font-family:ubuntu,verdana,helvetica,sans-serif">
    <div style="display:inline-block;vertical-align:middle;width:90%;max-width:600px;height:60%;max-height:440px;position:relative;padding-bottom:60px">
      @if (isset($data['amount']) && isset($data['image']))
        <div id="top">
          <span style="font-size:44px;float:right;line-height:52px;color:#666">₹ {{$data['amount']}}</span>
          <img src="{{$data['image']}}" height="52px">
        </div>
      @endif
      <div style="margin-top:20%">
        <div style="text-align:center;margin-bottom:75px;font-size:22px;color:#666">Processing Payment</div>
        <div class="spin"><div></div></div>
        <div class="spin" id="spin2"><div></div></div>
        <img id="power" style="width:160px;margin-top:80px" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAUAAAABDCAMAAADarSyCAAAAGFBMVEUtLS1VVVVvb2+JiYmhoaHAwMDa2tr+/v4xw2ptAAAEsklEQVR4Ae2Zja7jqg5G8f/7v/EdQr4GQ+6c3Uba6tHxkma3EHDTldiQTosI41Z8AFtEtIjS9zHSBUp7QBls1h5QWOP2gOKhvoLaF1AURVEURVEURVEUBfEEUSveJBasHL6FxEYZfAePDW3Fj+HouHXgUv7Dv7eqMcJFtB9g0bnmoPVvQ46rb/I8IeUtgZRzNjrWz0fN/xDupjxGZkafqA2ErhWdjgPK7WywqKow4RJfkQlTuA3QeBuNgf22QEurhqBFMWNIdXAOslw4UVCFjhfSo9tSmCWOLingH0qgMYuUf1UgRFlaUBwXNNmySFhavrN3OUfECrctThao+PwPKnlqijDe6kgPHh3jlfq5Mo0c6kdxjFaBPEa9Zq9XSG9uwD7EzbRjcEORIfhzM4eeIeNsEga4mb0WJ0JkTwJ9WtDoqcCRAE7IsCN8jwsrFN7HGL6/XcloSaBiXxzB+f7MNS/589s1hnigkDUXT577YK352duhXQ2yFa/o4vYJETpllav6odTGe1kFhoe7dH+mFqFjqPVZs8A++TBiYbhOCbnZw9C+S9R1AuVqBT06jgrNVi/Fvl87mT/BHmyhBJ/bw/g4J2mED90F0mjoSMPWeJyrJoGGu26Yg8dFT4bbDGWndq3S8wE9e6c7OBne3cAvxglCavsQ9jPbkG4W1kPyrcDRLb1xNvU88SRQxpkacnfVw7Gi2I2IHtishHyMyXrQ4CyV5wbBViM5IztCIW/p6S5EHHXER0Z5v7J0LxAX3jrdlIWuqzB09dHSLTBUAIsZV36JSqB7avHmyhepSb2ettj3h2688Wn8g7twLsQjOf8mEAjEZYEEgWM+JG97GE25x5Hxpfx1fEtWabzYRgOS+udkbD1G7RkUwce/wY1AzgIdI5vdCWQ478cVRrdNNJQlrTrubZSuJPl+gUhSdWvoOcsOEAACGe1n9BhTseXrK6PsQSAawML+bw308VeWDKHlLgjO6y5GTLJ1y0805O6ek7lB44Vn6TjohOEPgDA7rfU/c51G7YZAHB5D5W4V9jQTh4BeX8ihJ284DL3kSQhdLTSSVOT73DCs1fOyg+nmDxcQdR2bVBsRTdXP3HJV66d3diaBeh72cUqmtu8DPYLwLVpiyknD+7ThoCXFOaunmxtQ7raP/rcbkHKpBU9+SyBHucay5a9OSwKbXNPofDsJdEOcTr7EmCtznGXjp3mNofXxZHKBQTexEYYxYL9KCP8AVjVTQmt6wiUmenU2Um3jzzUU6570CKT0OkSNRHiur/smetmhYK1VEcEaY3HgAD0qzPK6RqMrxXZhZvFk2HpkTxdgQO3LWZcQTt840NLIeB+YsDQI/tZN9D7CIqNpLLdvB/ubNQ0Hvv9GZWeht3/4Tcr5/sHNZudpju6ytX015LYVae0IWqwH/U7t4tyNSf7A50CAKcRnC/VDO5QeoNnceyRUI/UjshIip0L71RCK9O9gbzgRLMDfDTG1X4PeKGrpmbrIT3Efui7e+K9l34cWEp2fF0ttRcJ/bCUvwEVeFj5dgAvqPB9YFEVRFEVRFEXxLVB7ROnjVjyAm7fiAd5C2scUGu3R76/lrwsMV6a3KVg9Iv4HYt5CPwj+3dsAAAAASUVORK5CYII=">
      </div>
    </div>
    <div style="display:inline-block;vertical-align:middle;height:90%;width:0"></div>

    <form id="form1" name="form1" action="{{$data['request']['url']}}" method="post" onsubmit="return true;">
    @foreach ($data['request']['content'] as $key => $value)
       <input type="hidden" name="{{$key}}" value="{{$value}}">
       <br />
    @endforeach
    </form>
    <br>
    <form id="form2" name="form2">
       <input type="hidden" name="type" value="{{$data['type']}}">
       <input type="hidden" name="gateway" value="{{$data['gateway']}}">
    </form>
  </body>
</html>
