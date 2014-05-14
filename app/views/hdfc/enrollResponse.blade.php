<!doctype html>
    <html lang="en">
    <!-- <BODY OnLoad="OnLoadEvent();"> -->
            <body>
            <form name="form1" action="{{$data['url']}}" method="post">
                <input type="text" name="PaReq" value="{{$data['PAReq']}}">
                <br />
                <input type="text" name="MD" value="{{$data['paymentid']}}">
                <br />
                <input type="text" name="TermUrl" value="{{$callbackUrl}}">
                <br />
                <input type="submit" value="Submit" >
            </form>
            <br>
            Submit within 30 secs max!
            </body>
        </html>