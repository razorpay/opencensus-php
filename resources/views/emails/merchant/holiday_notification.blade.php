<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><meta name="viewport" content="width=device-width"></head>
  <body>
    <div style ="background-image: linear-gradient(to bottom, #1b3fa6 0%, #1b3fa6 200px, #F8F9F9 200px, #F8F9F9 90%);height:100%;  position:absolute;">
    <div style="text-align:center; margin-bottom: 30px;"><img style=" margin-top: 30px; height:30px;" src="https://cdn.razorpay.com/logo_invert.png"></div>
    <div style ="background: #F8F9F9;margin-top: -75px; width:593px; margin:auto">
    <table style="width:550px">
      <div style="background-color: #FFFFFF;margin-bottom:6px;padding:20px; width:550px; text-align: center">
        <img style=" margin-top: 30px; height:60px;" src="https://cdn.razorpay.com/static/assets/email/notification.png">
        <h2 style ="color: #0D2366; font-family: Trebuchet MS;font-style: normal;font-weight: bold;font-size: 25px;line-height: 24px;">{{{$subject}}}</h2>
        <p style="font-family: Trebuchet MS;font-style: normal;font-weight: bold;font-size: 18px;line-height: 25px;text-align: center;color: #515978;">Settlements will not be processed on following days due to bank holidays</p>
        <div><div style ="height: 7px;width: 35px;position: absolute;background: #2DD589;margin:auto;margin-bottom:15px"></div></div>
      </div>
    </table>
      {!! $body !!} 
    <table style="width:600px;">
      <div style = "text-align:center;margin-bottom:16px;margin-top:8px"><p style ="padding-left:50px;padding-right:50px;font-family: Trebuchet MS;font-style: normal;font-weight: normal;font-size: 14px;line-height: 16px;text-align: unset; color: #7B8199;margin-bottom:10px;margin-top:0px">
      If you have any issue with the service from Razorpay, Please raise your request <a href="https://dashboard.razorpay.com/#/app/dashboard#request">here</a></p>
      </div>
    </table>
    </div>
    </div>
  </body>
</html>