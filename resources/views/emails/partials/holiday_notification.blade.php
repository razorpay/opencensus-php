@if($holidays)
<table style="width:550px">
    <div style="background-color: #FFFFFF;margin-bottom:6px;padding:20px;width: 550px">
        <table><tr style = "width:100%"><th><div style = "padding-right:100px"><h3 style="width:220px;font-family: Trebuchet MS;font-style: normal;font-weight: normal;font-size: 18px;line-height: 20px; color: #7B8199;margin-top:15px">Holiday</h3></div></th><th><h3 style="text-align:right;padding-right:20px;font-family: Trebuchet MS;font-style: normal;font-weight: normal;font-size: 18px;line-height: 20px; color: #7B8199;margin-top:15px;width:200px">Date</h3></th></tr>
        </table>
        <div style = "height :1px; position: absolute;background: #D5DBDE;opacity: 100%;;mix-blend-mode: normal;width:550px"></div>
        @foreach ($holidays as $holiday)
        <table>
        <tr style = "width:100%"><th><div style = "padding-right:100px"><h3 style="width:220px;font-family: Trebuchet MS;font-style: normal;font-weight: normal;font-size: 18px;line-height: 24px; color: #515978; margin-bottom:0px ;margin-top:16px">{{{$holiday['reason']}}}</h3></div></th><th><h3 style="text-align: right;padding-right:20px; font-family: Trebuchet MS;font-style: normal;font-weight: normal;font-size: 18px;;line-height: 24px; color: #0D2366; margin-top:16px; padding-right:10px;width:200px">{{{$holiday['date']->toFormattedDateString()}}}</h3></th></tr>
        </table>
        @endforeach
    </div>
</table>
@endif
<table style="width:550px">
    <div style="background-color: #FFFFFF;border-top: 2px solid #528FF0; margin-bottom:6px;padding:20px;width: 550px">
        <h3 style ="color: #646D8B;font-family: Trebuchet MS;font-style: normal;font-weight: bold;font-size: 18;line-height: 20px;margin-top:5px;">Settlements will be next processed on {{{$nextWorkingDayString}}}.</h3>
        <p style ="font-family: Trebuchet MS;font-style: normal;font-weight: normal;font-size: 16px;line-height: 18px;color: #7B8199; text-align: left;letter-spacing: 0px;margin-bottom:3px">Thank you for partnering with Razorpay</p>
    </div>
</table>
@if($settleNowEnabled === 'enabled')
<table style="width:550px">
    <div style="background-color: #FFFFFF;border-top: 2px solid #528FF0; margin-bottom:6px;padding:20px;width: 550px">
        <h3 style ="color: #646D8B;font-family: Trebuchet MS;font-style: normal;font-weight: bold;font-size: 18px;line-height: 20px;margin-top:5px">Want to get settlements even on bank holidays?</h3>
        <p style ="font-family: Trebuchet MS;font-style: normal;font-weight: normal;font-size: 16px;line-height: 24px;color: #7B8199; text-align: left;letter-spacing: 0px;">Get money settled in your bank account any time with <b>On-Demand Settlement</b> from your Razorpay Dashboard</p>
        <a href = "https://dashboard.razorpay.com/#/app/settlements"><div style = "width:130px; background-color:#528ff0; border-radius: 3px; margin:auto; padding: 13px; padding-left: 53px; white-space: nowrap;" ><p style = "margin:auto; color:#fff; display: inline-block;Trebuchet MS;font-style: normal;font-weight: bold;">Settle Now</p></div></a>
    </div>
</table>
@endif