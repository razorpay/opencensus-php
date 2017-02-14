<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    @php
        $themeBgColor = '#00b74b';
        $themeFontColor = '#ffffff';
        $themed = 'background-color: ' . $themeBgColor . '; color: ' . $themeFontColor . ';';

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
            padding: 24px;
            background-color: #fff;
            border-left: 1px solid rgba(0,0,0,0.05);
            border-right: 1px solid rgba(0,0,0,0.05);
        ';
    @endphp
  </head>
  <body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0"
    style="line-height: 20px;width: 100%;margin: 0;padding: 0; font-size: 14px; color: rgba(0,0,0,0.87);"
  >
    <center>
        <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" style="max-width: 600px; table-layout: fixed; background-color: #fafafa; color: rgba(0,0,0,0.87);">
            <tbody>
                <tr>
                    <td style="{{ $themed }}; width: 36px;"></td>
                    <td style="{{ $themed }}; color: #fff;">
                        <div style="text-align: center; padding: 24px;">
                            <img
                                src="https://www.treebo.com/blog/wp-content/uploads/2016/12/New-logo-color-01.png"
                                style="width: 48px; height: 48px;margin-bottom: 8px;"
                             />
                             <h2 style="{{ $h2Style }}; color: {{ $themeFontColor }}">
                                Invoice from {{$merchant['name']}}
                            </h2>
                            <div>
                                Invoice Receipt: {{$invoice['receipt']}}
                            </div>

                             <div style="margin-top: 8px; color: {{ $themeFontColor }}">
                                <div>
                                    Treebo has sent you an invoice for {{$invoice['currency']}} {{$invoice['formatted_amount']}}
                                </div>
                                <div>
                                    THe invoice for the same is attached in this mail.
                                </div>
                             </div>
                        </div>
                    </td>
                    <td style="{{ $themed }}; width: 36px;"></td>
                </tr>

                @if (is_null($invoice['description']) !== false)
                <tr>
                    <td style="{{ $themed }}; width: 36px;"></td>
                    <td style="{{ $contentCell }}; border-top: 1px solid rgba(0,0,0,0.05);">
                        <div>
                            <label style="{{ $labelStyle }}">INVOICE SUMMARY</label>
                            <div>{{$invoice['description']}}</div>
                        </div>
                    </td>
                    <td style="{{ $themed }}; width: 36px;"></td>
                </tr>
                @endif

                <tr>
                    <td></td>
                    <td style="{{ $contentCell }};">
                        <div style="margin-bottom: 24px;">
                            <label style="{{ $labelStyle }}">BILLING TO</label>
                            <div>
                                {{$invoice['customer_details']['customer_name']}}
                                {{$invoice['customer_details']['customer_contact']}}
                                {{$invoice['customer_details']['customer_email']}}
                            </div>
                        </div>
                        <div>
                            <label style="{{ $labelStyle }}">INVOICE EXPIRY</label>
                            <div>After 60 days</div>
                        </div>
                    </td>
                    <td></td>
                </tr>
                <tr>
                    <td></td>
                    <td style="{{ $contentCell }}; border-bottom: 1px solid rgba(0,0,0,0.05); border-top: 1px dashed rgba(0,0,0,0.10);">
                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tbody>
                                <tr>
                                    <td>
                                        <label style="{{ $labelStyle }}">AMOUNT</label>
                                        <div style="font-weight: bold; font-size: 18px;">
                                            {{$invoice['currency']}} {{$invoice['formatted_amount']}}
                                        </div>
                                    </td>
                                    <td style="text-align: right;">
                                      <a href="{{$invoice['short_url']}}" target="_blank" style="
                                        text-decoration: none;
                                        padding: 7px 20px;
                                        display: inline-block;
                                        background-color: {{ $themeBgColor }};
                                        border: 1px solid {{ $themeBgColor }};
                                        border-radius: 5px;
                                        color: {{ $themeFontColor }};
                                        white-space: nowrap;
                                        cursor: pointer;
                                      ">
                                        PROCEED TO PAY
                                      </a>
                                    </td>
                                </tr>
                             </tbody>
                        </table>
                    </td>
                    <td></td>
                </tr>
                <tr>
                    <td></td>
                    <td style="padding: 24px; text-align: center;">
                        <div style="font-size: 12px; font-weight: bold; color: rgba(0,0,0,0.54);">
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
                    <td style="{{ $contentCell }}; border-bottom: 1px solid rgba(0,0,0,0.05); border-top: 1px solid rgba(0,0,0,0.10);">
                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tbody>
                                <tr>
                                    <td>
                                        <a href="">
                                            <img src="http://i.imgur.com/7uG8BA0.png" />
                                        </a>
                                    </td>
                                    <td style="text-align: right; padding-left: 40px; font-size: 10px; color: rgba(0,0,0,0.54);">
                                        <div>
                                            Sign up at <a href="">razorpay.com/invoices</a> to create invoices and accept payments for your business.
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
