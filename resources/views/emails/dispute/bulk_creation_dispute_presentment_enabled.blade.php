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
            ' . $headerColumnStyle . 'DISPUTE ID</th>
            ' . $headerColumnStyle . 'PAYMENT ID</th>
            ' . $headerColumnStyle . 'AMOUNT</th>
            ' . $headerColumnStyle . 'CASE ID</th>
            ' . $headerColumnStyle . 'PHASE</th>
            ' . $headerColumnStyle . 'RESPOND BY</th>';

        if ($phase === 'chargeback') {
            $headerRow .= '' . $headerColumnStyle . 'CHARGEBACK REASON</th>';
        }

        foreach ($disputesDataTable as $key => $dispute)
        {
            $paymentLink = 'https://dashboard.razorpay.com/#/app/payments/' . $dispute['payment_id'];
            $disputeLink = 'https://dashboard.razorpay.com/#/app/disputes/' . $dispute['dispute_id'];
            $disputesTable .= '
                  <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; padding: 0; vertical-align: top; text-align: left;">
                    ' . $rowColumnStyle . '
                    <a href='. $disputeLink . ' target="_blank" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; text-decoration: none; color: #39ACE5;">
                    '. $dispute['dispute_id'] . ' </a>
                    </td>
                    ' . $rowColumnStyle . '
                    <a href='. $paymentLink . ' target="_blank" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; text-decoration: none; color: #39ACE5;">
                    '. $dispute['payment_id'] . ' </a>
                    </td>
                    ' . $rowColumnStyle . $dispute['amount'] . '</td>
                    ' . $rowColumnStyle . $dispute['case_id'] . '</td>
                    ' . $rowColumnStyle . $dispute['phase'] . '</td>
                    ' . $rowColumnStyle . $dispute['respond_by'] . '</td>';

            if ($phase === 'chargeback') {
                $disputesTable .= '' . $rowColumnStyle . $dispute['gateway_description'] . '</td';
            }

            $disputesTable .= '</tr>';
        }
    @endphp
</p>
<center style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; width: 100%; min-width: 580px; background-color: #fafafa;">
    <table border="0" cellpadding="0" cellspacing="0" style="width: 100%; max-width: 800px; margin: 0 auto; padding: 30px; border: 1px solid black; border-radius: 15px; background-color: #ffffff;">
        <tr>
            <td style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; text-align: center;">
                <table border="0" cellpadding="0" cellspacing="0" style="width: 100%;">
                    <tr>
                        <td style="padding-left: 130px; text-align: center;">
                            <h2 style="font-family: -apple-system, '.SFNSDisplay', 'Oxygen', 'Ubuntu', 'Roboto', 'Segoe UI', 'Helvetica Neue', 'Lucida Grande', sans-serif; font-weight: normal; padding: 0; word-break: normal; margin: 0; font-size: 20px; line-height: 24px;">
                                <b> Dispute(s) received against <b> {{ $totalPayments }} </b> payment(s). </b>
                            </h2>
                            <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; margin-top: 12px;">
                                <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px;">
                                    Please respond by the dates mentioned
                                </div>
                            </div>
                        </td>
                        <td style="text-align: right; padding-right: 34px; vertical-align: middle;">
                            <img src="https://cdn.razorpay.com/static/assets/logo/rzp.png" style="width: 60px; object-fit: contain;"/>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="table" border="0" cellpadding="0" cellspacing="0" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; width: 100%; background-color: #fafafa; margin-top: -100px !important; height: 100%; max-width: 800px; margin: 0 auto; font-size: 12px;"><tbody style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
        <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; padding: 0; vertical-align: top; text-align: left;">
            @php
                echo $headerRow;
            @endphp
        </tr>
        @php
            echo $disputesTable;
        @endphp
        </tbody></table>
    <table class="table" border="0" cellpadding="0" cellspacing="0" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; border-spacing: 0; border-collapse: collapse; padding: 0; vertical-align: top; text-align: left; width: 100%; background-color: #fafafa; height: 100%; max-width: 800px; margin: 0 auto; font-size: 12px;"><tbody style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
        <tr style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121; padding: 0; vertical-align: top; text-align: left;">
            <td class="content" style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; font-family: -apple-system,'.SFNSDisplay','Oxygen','Ubuntu','Roboto','Segoe UI','Helvetica Neue','Lucida Grande',sans-serif; font-weight: normal; margin: 0; text-align: left; font-size: 14px; line-height: 19px; padding: 24px 4%; padding-bottom: 0; background-color: #fff; border-left: 1px solid #f2f2f2; border-right: 1px solid #f2f2f2; color: #000000; border-top: dashed 1px rgba(0,0,0,0.1); border-bottom: solid 1px rgba(0,0,0,0.05); padding-top: 0px;">
                <div style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #000000;">
                    <br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
                    Hi Team {{ $merchant['name'] }}
                    <br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;"><br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
                    @switch($phase)
                        @case('chargeback')
                        @case('retrieval')
                        We have received dispute(s) against <b> {{ $totalPayments }} </b> payment(s) mentioned above.  Kindly upload all proofs like invoices, proof of delivery of product/service and any relevant screenshots pertaining to each dispute by visiting your <a href="https://dashboard.razorpay.com/"> Razorpay dashboard</a>. The failure to do so within the TAT can lead to the corresponding amount getting debited from the current balance.
                        <br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;"><br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
                        @break
                        @case('pre_arbitration')
                        We have received Pre-Arbitration for the <b> {{ $totalPayments }} </b> payments mentioned above. Please provide additional compelling evidence (apart from the ones already shared during the chargeback stage) to establish that the services were delivered. Suggested evidence includes:
                        <br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;"><br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
                        <ol>
                            <li>Company letterhead with an explanation of the case.</li>
                            <li>Customer email confirmation in case when customer has agreed to withdraw the Pre-Arbitration. [along with their identity proof]</li>
                            <li>Cancellation, Refund & Return Policy of your business that addresses the grievance.</li>
                        </ol>
                        @break
                        @case('arbitration')
                        We have received an arbitration chargeback(s) for the above mentioned <b> {{ $totalPayments }} </b> payments. This essentially means that the cardholder has re-contested  the same transaction(s) for the third time. Arbitration chargeback requests are exceptions/chargeable & decided by dedicated committee of the card network (i.e. Visa, Mastercard, Rupay, as applicable) .
                        <br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;"><br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
                        In case you wish to challenge this Arbitration, we will be holding the transaction amount + the applicable arbitration fees until the verdict is given by the Arbitration committee.
                        <br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;"><br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
                        If you wish to accept this Arbitration, the acceptance fee levied will be lesser than the contestation fee.
                        <br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;"><br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
                        Note: This case(s) will be deemed accepted in the absence of a response.
                        <br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;"><br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
                        @break
                        @case('fraud')
                        We have received fraud chargeback(s) for the payment(s) mentioned above. These payments have been reported as never authorised / fraud by the cardholder.
                        <br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;"><br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
                        Kindly upload all proofs like invoices, proof of delivery of product/service and any relevant screenshots pertaining to each dispute by visiting your <a href="https://dashboard.razorpay.com/"> Razorpay dashboard</a>.
                        The failure to do so can lead to  the corresponding amount getting debited from the current balance.
                        @if($hasDeductAtOnset === true)
                            As per guidelines from our banking partner, one or more of the above payments have been debited from your current balance. The corresponding amount would be reversed if our banking partner resolves dispute in your favour.
                        @endif
                        <br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;"><br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
                        @break
                    @endswitch
                    To provide you a seamless experience, we have moved all dispute management correspondence to the Razorpay dashboard. Henceforth, responses received via email will not be considered. <a href="https://razorpay.com/docs/payments/disputes/dashboard/"> Click here</a> to know how to respond to chargebacks on the Razorpay Dashboard.
                    <br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;"><br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
                    Please refer to the <a href="https://razorpay.com/blog/chargebacks/"> Chargeback Guide</a> as mentioned on our website for best practices, Timeframe for chargeback phases, fees and list of Chargeback Documents for specific business types. If you have further questions regarding the chargeback please reply to this email.
                    <br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;"><br style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 20px; color: #212121;">
                    Thanks,<br>
                    Chargebacks
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
        </tbody></table>
</center>

</body>
</html>
