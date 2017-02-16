<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    @php
        $themeBgColor = $merchant['brand_color'];
        $themeFontColor = $merchant['brand_text_color'];
        $themed = 'background-color: ' . $themeBgColor . '; color: ' . $themeFontColor . ';';
        $fontFamily = 'Verdana, Arial, sans-serif';

        $h2Style = '
            margin:0;
            font-size:20px;
            line-height:24px;'
        ;

        $labelStyle = '
            font-size: 12px;
            color: rgba(0, 0, 0, 0.54);
            font-weight: bold;
        ';

        $contentCell = '
            width: 94%;
            padding: 24px 4%;
            background-color: #fff;
            border-left: 1px solid rgba(0,0,0,0.05);
            border-right: 1px solid rgba(0,0,0,0.05);
            padding-bottom: 0;
            font-family: Verdana, Arial, sans-serif;
        ';
    @endphp
  </head>
  <body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0"
    style="
        line-height: 20px;
        width: 100%;
        margin: 0;
        padding: 0;
        font-size: 14px;
        color: rgba(0,0,0,0.87);
        font-family: {{ $fontFamily }};
    ">
    <center style="font-family: {{ $fontFamily }};">
        <table style="
            max-width: 600px;
            table-layout: fixed;
            background-color: #fafafa;
            color: rgba(0,0,0,0.87);
        " border="0" cellpadding="0" cellspacing="0" height="100%" width="100%">
            <tbody>
                <tr>
                    <td style="{{ $themed }};"></td>
                    <td style="{{ $contentCell }}; {{ $themed }};">
                        <div style="text-align: center; padding: 24px 4%;">
                            <img
                                src="https://www.treebo.com/blog/wp-content/uploads/2016/12/New-logo-color-01.png"
                                style="width: 48px; height: 48px;margin-bottom: 8px;"
                             />
                             <h2 style="{{ $h2Style }}; color: {{ $themeFontColor }}">
                                Invoice from {{$merchant['name']}}
                            </h2>
                            <div style="color: {{ $themeFontColor }}">
                                @if ($invoice['receipt'])
                                    Invoice Receipt: {{$invoice['receipt']}}
                                @else
                                    Invoice Id: {{$invoice['id']}}
                                @endif
                            </div>

                             <div style="margin-top: 12px; color: {{ $themeFontColor }}">
                                <div style="color: {{ $themeFontColor }}">
                                    INVOICE EXPIRED
                                </div>
                                <div style="color: {{ $themeFontColor }}">
                                    The invoice for the same is attached in this mail.
                                </div>
                             </div>
                        </div>
                    </td>
                    <td style="{{ $themed }};"></td>
                </tr>

                @if ($invoice['description'])
                <tr>
                    <td style="{{ $themed }};"></td>
                    <td style="{{ $contentCell }}; border-top: 1px solid rgba(0,0,0,0.05);">
                        <div>
                            <label style="{{ $labelStyle }}">INVOICE SUMMARY</label>
                            <div>{{$invoice['description']}}</div>
                        </div>
                    </td>
                    <td style="{{ $themed }};"></td>
                </tr>
                @endif

                <tr>
                    @if ($invoice['description'])
                        <td></td>
                    @else
                        <td style="{{ $themed }};"></td>
                    @endif
                    <td style="{{ $contentCell }};">
                        <div>
                            <label style="{{ $labelStyle }}">BILLING TO</label>
                            <div>
                                @if ($invoice['customer_details']['customer_name'])
                                    {{$invoice['customer_details']['customer_name']}}
                                    @if ($invoice['customer_details']['customer_contact'])
                                        , {{$invoice['customer_details']['customer_contact']}}
                                    @endif
                                @endif

                                <div>
                                    {{$invoice['customer_details']['customer_email']}}
                                </div>
                            </div>
                        </div>
                    </td>
                    @if ($invoice['description'])
                        <td></td>
                    @else
                        <td style="{{ $themed }};"></td>
                    @endif
                </tr>

                <tr>
                    <td></td>
                    <td style="{{ $contentCell }}; padding-bottom: 24px;">
                        <div>
                            <label style="{{ $labelStyle }}">INVOICE EXPIRED ON</label>
                            <div>{{$invoice['date']}}</div>
                        </div>
                    </td>
                    <td></td>
                </tr>

                <tr>
                    <td></td>
                    <td style="
                        {{ $contentCell }};
                        border-bottom: 1px solid rgba(0,0,0,0.05);
                        border-top: 1px dashed rgba(0,0,0,0.10);
                        padding-bottom: 24px;
                    ">
                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tbody>
                                <tr>
                                    <td style="font-family: {{ $fontFamily }};">
                                        <label style="{{ $labelStyle }}">AMOUNT</label>
                                        <div style="font-weight: bold; font-size: 18px;">
                                            {{$invoice['currency']}} {{$invoice['formatted_amount']}}
                                        </div>
                                    </td>
                                    <td style="font-family: {{ $fontFamily }}; text-align: right;">
                                    </td>
                                </tr>
                             </tbody>
                        </table>
                    </td>
                    <td></td>
                </tr>
                <tr>
                    <td></td>
                    <td style="
                        padding: 24px 4%;
                        text-align: center;
                        font-family: {{ $fontFamily }};
                    ">
                        <div style="
                            font-size: 12px;
                            font-weight: bold;
                            color: rgba(0,0,0,0.54);
                        ">
                            {{$merchant['name']}}
                        </div>
                        <div style="font-size: 10px; color: rgba(0,0,0,0.54);">
                            1st Floor, Hosur Road, Adugodi, Bangalore
                        </div>
                    </td>
                    <td></td>
                </tr>
                <tr>
                    <td></td>
                    <td style="
                        {{ $contentCell }};
                        border-bottom: 1px solid rgba(0,0,0,0.05);
                        border-top: 1px solid rgba(0,0,0,0.10);
                    ">
                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tbody>
                                <tr>
                                    <td style="
                                        font-family: {{ $fontFamily }};
                                        vertical-align: top
                                    ">
                                        <a href="https://razorpay.com/" target="_blank">
                                            <img style="height: 24px;" src="https://razorpay.com/images/logo-black.png"/>
                                        </a>
                                    </td>
                                    <td style="
                                        text-align: right;
                                        padding-left: 10%;
                                        padding-bottom: 24px;
                                        font-size: 10px;
                                        color: rgba(0,0,0,0.54);
                                        font-family: {{ $fontFamily }};
                                    ">
                                        <div>
                                            Sign up at <a href="https://razorpay.com/" target="_blank">razorpay.com/invoices</a> to create invoices and accept payments for your business.
                                        </div>
                                    </td>
                                </tr>
                             </tbody>
                        </table>
                    </td>
                    <td></td>
                </tr>
            </tbody>
          </table>
      </center>
  </body>
</html>
