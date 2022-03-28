<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
<head style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
    <meta name="viewport" content="width=device-width" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
</head>
<body style="width: 100% !important; min-width: 100%; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">
<p style="color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px; margin-bottom: 10px;">
    @php
        $brand_color = '#6A75ED';
        $brand_text_color = '#FFFFFF';
        $disputesTable = '';
        $headerColumnStyle = '<th class="content" style="word-break: break-word; -webkit-hyphens: auto;
        -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top;
        font-family: -apple-system, ' .
        "'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande' " .
        ',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding: 1%;
         background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; color: #000000;
         padding-bottom: 24px; padding-top: 0px;">
         <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; font-weight: bold;
         line-height: 20px; color: #212121;"> <br style="font-family: -apple-system, BlinkMacSystemFont, Arial,
         sans-serif; line-height: 20px; color: #212121;">';
        $rowColumnStyle = '<td class="content" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto;
        hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,' .
        "'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande' " .
        ',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 12px; line-height: 19px; padding: 1%;
        background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; color: #000000;
        padding-bottom: 24px; padding-top: 0px;">
        <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">';
        $headerRow = '
            ' . $headerColumnStyle . 'Payment ID</th>
            ' . $headerColumnStyle . 'Transaction Date</th>
            ' . $headerColumnStyle . 'Amount</th>
            ' . $headerColumnStyle . 'Currency</th>';
        $dataTable = '';
        foreach ($merchantDataTable as $row)
        {
            $paymentLink = 'https://dashboard.razorpay.com/#/app/payments/' . $row['payment_id'];
            $dataTable .= '
                  <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; padding: 0; vertical-align: top; text-align: left;">
                    ' . $rowColumnStyle . '
                    <a href='. $paymentLink . ' target="_blank" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; text-decoration: none; color: #39ACE5;">
                    '. $row['payment_id'] . ' </a>
                    </td>
                    ' . $rowColumnStyle . $row['transaction_date'] . '</td>
                    ' . $rowColumnStyle . $row['amount'] . '</td>
                    ' . $rowColumnStyle . $row['currency'] . '</td>
                  </tr>';
        }
    @endphp
</p>
<center style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; width: 100%; min-width: 580px; background-color: #fafafa;">

    <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; text-align: center; background-color: {{ $brand_color }}; color: {{ $brand_text_color }}; padding: 30px 0 60px !important;">

        <h2 style="font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; word-break: normal; margin: 0; font-size: 20px; line-height: 24px; text-align: center; color: {{ $brand_text_color }};">
            Fraud Notification(s) received against payment(s)
        </h2>

        <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; margin-top: 12px; color: {{ $brand_text_color }};">
            <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: {{ $brand_text_color }};">
                We have received a fraud alert from the Card Schemes/Networks for the below captioned payment(s) which are initiated through International cards.
            </div>
        </div>
    </div>

    <table class="table" border="0" cellpadding="0" cellspacing="0" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; width: 100%; background-color: #fafafa; margin-top: -100px !important; height: 100%; max-width: 800px; margin: 0 auto; font-size: 12px;"><tbody style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
        <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; padding: 0; vertical-align: top; text-align: left;">
            @php
                echo $headerRow;
            @endphp
        </tr>
        @php
            echo $dataTable;
        @endphp
        </tbody>
    </table>

    <table class="table" border="0" cellpadding="0" cellspacing="0" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; width: 100%; background-color: #fafafa; height: 100%; max-width: 800px; margin: 0 auto; font-size: 12px;"><tbody style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
        <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; padding: 0; vertical-align: top; text-align: left;">
            <td class="content" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding: 24px 4%; padding-bottom: 0; background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; color: #000000; border-top: dashed 1px rgba(0,0,0,0.1); border-bottom: solid 1px rgba(0,0,0,0.05); padding-top: 0px;">
                <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #000000;">
                    <br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
                    Dear Merchant,
                    <br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
                    <br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
                    We request you to immediately stop the services & arrange for a refund of the reported transactions. Additionally, please do share the below details as requested by the Networks.
                    <ol>
                        <li>Nature of Transaction. (Mobile recharge, product purchase, etc.)</li>
                        <li>Details of the person who did the transaction. (Name, Contact no., address, e-mail, etc. Please mention how these details were gathered)</li>
                        <li>In case of mobile recharge please furnish beneficiary mobile no. with the name of the service provider for us to direct the dispute appropriately.</li>
                        <li>In case of a product purchase, please furnish the invoice copy, Beneficiary Name, Contact no., address, e-mail, etc.</li>
                        <li>IP address of the transaction with the time slot.</li>
                        <li>Customer KYC docs (if available/applicable).</li>
                    </ol>
                    <br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
                    Thanks and Regards,
                    <br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
                    Razorpay Risk Team
                    <br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;"><br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
                </div>
            </td>
        </tr>
        <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; padding: 0; vertical-align: top; text-align: left; height: 30px;"></tr>
        <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; padding: 0; vertical-align: top; text-align: left;">
            <td class="content" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding: 24px 4%; padding-bottom: 0; background-color: #fff; border-left: 1px solid #f2f2f2; color: #000000; border-right: 1px solid #f2f2f2; vertical-align: top; border-bottom: 1px solid #f2f2f2; border-top: 1px solid #f2f2f2;">

                <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; width: 100%;"><tbody style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;"><tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; padding: 0; vertical-align: top; text-align: left;">
                        <td class="content" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px; vertical-align: top;">
                            <a href="https://razorpay.com/" target="_blank" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; text-decoration: none; color: #39ACE5; height: 24px;">
                                <img style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; width: auto; max-width: 100%; float: left; clear: both; display: block; border: none; height: 24px;" src="https://razorpay.com/images/logo-black.png"></a>
                        </td>
                        <td class="content" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; padding: 0; margin: 0; text-align: left; font-size: 14px; line-height: 19px;">
                            <div class="footerRZP" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; text-align: right; padding-left: 10%; padding-bottom: 24px; font-size: 10px; color: #757575;">
                                For any queries, please reply to this email.
                            </div>
                        </td>
                    </tr></tbody></table>
            </td>
        </tr>
        <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; padding: 0; vertical-align: top; text-align: left;">
            <td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; color: #222222; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding: 24px 4%; padding-bottom: 0;"></td>
        </tr>
        </tbody>
    </table>
</center>

</body>
</html>
