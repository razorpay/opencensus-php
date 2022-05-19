<!DOCTYPE html>
<html style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 100%; line-height: 1.6em; margin: 0; padding: 0;">
<head>
    <meta name="viewport" content="width=device-width">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>{{{$subject}}}</title>
</head>
<body bgcolor="#f6f6f6" style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 100%; line-height: 1.6em; -webkit-font-smoothing: antialiased; height: 100%; -webkit-text-size-adjust: none; width: 100% !important; margin: 0; padding: 0;">

<!-- body -->
<table class="body-wrap" bgcolor="#f6f6f6" style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 100%; line-height: 1.6em; width: 100%; margin: 0; padding: 20px;"><tr style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 100%; line-height: 1.6em; margin: 0; padding: 0;">
        <td style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 100%; line-height: 1.6em; margin: 0; padding: 0;"></td>
        <td class="container" bgcolor="#FFFFFF" style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 100%; line-height: 1.6em; clear: both !important; display: block !important; max-width: 750px !important; Margin: 0 auto; padding: 20px; border: 1px solid #f0f0f0;">

            <!-- content -->
            <div class="content" style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 100%; line-height: 1.6em; display: block; max-width: 600px; margin: 0 auto; padding: 0;">
                <table style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 100%; line-height: 1.6em; width: 100%; margin: 0; padding: 0;"><tr style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 100%; line-height: 1.6em; margin: 0; padding: 0;">
                        <td style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 100%; line-height: 1.6em; margin: 0; padding: 0;">
                            <p>PFA Claims and Refund summary for DBS Bank.</p>
                            <table style="border: 1px solid grey;">
                                <thead>
                                <tr style="border: 1px solid grey">
                                    <th style="border: 1px solid grey">Sr. No.</th>
                                    <th style="border: 1px solid grey">Status</th>
                                    <th style="border: 1px solid grey">Number of txn pertaining to Razorpay</th>
                                    <th style="border: 1px solid grey">Amount pertaining to RazorPay</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr style="border: 1px solid grey">
                                    <td style="border: 1px solid grey">1</td>
                                    <td style="border: 1px solid grey">Success</td>
                                    <td style="border: 1px solid grey">{{{$count['claims']}}}</td>
                                    <td style="border: 1px solid grey">{{{$amount['claims']}}}</td>
                                </tr>
                                <tr style="border: 1px solid grey">
                                    <td style="border: 1px solid grey">2</td>
                                    <td style="border: 1px solid grey">Refunds STP</td>
                                    <td style="border: 1px solid grey">{{{$count['refunds']}}}</td>
                                    <td style="border: 1px solid grey">{{{$amount['refunds']}}}</td>
                                </tr>
                                <tr style="border: 1px solid grey">
                                    <td style="border: 1px solid grey">3</td>
                                    <td style="border: 1px solid grey">Refunds Manual</td>
                                    <td style="border: 1px solid grey">{{{$count['refunds_manual']}}}</td>
                                    <td style="border: 1px solid grey">{{{$amount['refunds_manual']}}}</td>
                                </tr>
                                <tr style="border: 1px solid grey">
                                    <td style="border: 1px solid grey"></td>
                                    <td style="border: 1px solid grey"></td>
                                    <td style="border: 1px solid grey">Claim Amount</td>
                                    <td style="border: 1px solid grey">{{{$amount['total']}}}</td>
                                </tr>
                                </tbody>
                            </table>
                            <p>
                                Nodal A/c:<br>
                                Account Name - {{{$accountDetails['accountName']}}}<br>
                                Account No. - {{{$accountDetails['accountNumber']}}}<br>
                                IFSC Code - {{{$accountDetails['ifsc']}}}<br>
                                Bank Name- {{{$accountDetails['bankName']}}}<br>
                            </p>
                        </td>
                    </tr></table>
            </div>
            <!-- /content -->

        </td>
        <td style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 100%; line-height: 1.6em; margin: 0; padding: 0;"></td>
    </tr></table>
<!-- /body -->
</body>
</html>
