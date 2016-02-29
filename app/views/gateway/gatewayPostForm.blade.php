<!doctype html>
<html lang="en" style="height: 100%; overflow: hidden;font-family:ubuntu,helvetica,sans-serif;">
    <script>
        try{

            var payment_id = "{{$data['payment_id']}}";
            if (typeof(CheckoutBridge) !== 'undefined' && typeof(CheckoutBridge.setPaymentID) === 'function') {
                CheckoutBridge.setPaymentID(payment_id);
            }
            else if(window.opener){
                opener.setPaymentID(payment_id);
            }

        } catch(e){}
    </script>

    <body onload="document.form1.submit()" style="text-align: center; height: 100%">
    <div style="position: relative; top: 50%; margin-top: -100px;">
        <div>Please wait while your transaction is processed...</div>
        <div id="powered" style="display: none; font-size: 13px; position: absolute; right: 50%; bottom: 35px; letter-spacing: 0.25px; color: #888; margin-right: -96px;">powered by</div>
        <script>
            try{
                var parent = document.currentScript.parentNode;
                var src = localStorage.getItem('rzppowered');
                var img = document.createElement('img');
                img.src = src;
                img.width = 192;
                img.style.marginTop = '60px';
                parent.appendChild(img);
                document.querySelector('powered').style.display = 'block';
            }
            catch(e){}
        </script>
    </div>
    <form id="form1" name="form1" action="{{$data['request']['url']}}" method="post" onsubmit="return true;">

@foreach ($data['request']['content'] as $key => $value)
        <!-- {{$key}}: --> <input type="hidden" name="{{$key}}" value="{{$value}}">
        <br />
@endforeach
<!--         <input type="submit" value="Submit" >
 -->    </form>
    <br>
    <form id="form2" name="form2">
        <input type="hidden" name="type" value="{{$data['type']}}">
        <input type="hidden" name="gateway" value="{{$data['gateway']}}">
    </form>

    </body>
</html>
