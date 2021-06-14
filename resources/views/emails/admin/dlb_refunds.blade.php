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
        <td class="container" bgcolor="#FFFFFF" style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 100%; line-height: 1.6em; clear: both !important; display: block !important; max-width: 600px !important; Margin: 0 auto; padding: 20px; border: 1px solid #f0f0f0;">

            <!-- content -->
            <div class="content" style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 100%; line-height: 1.6em; display: block; max-width: 600px; margin: 0 auto; padding: 0;">
                <table style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 100%; line-height: 1.6em; width: 100%; margin: 0; padding: 0;"><tr style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 100%; line-height: 1.6em; margin: 0; padding: 0;">
                        <td style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 100%; line-height: 1.6em; margin: 0; padding: 0;">
                            <p>Hi Team,</p>
                                <br>
                                <p>Please credit to us as per below details and find the attached Sale & Refund reference file.</p>
                            <table style="border: 1px solid grey;">
                                <thead>
                                <tr style="border: 1px solid grey">
                                    <th style="border: 1px solid grey">Sr. No.</th>
                                    <th style="border: 1px solid grey">Particulars</th>
                                    <th style="border: 1px solid grey">Amount (Rs.)</th>
                                    <th style="border: 1px solid grey">No of Transactions</th>
                                </tr>
                                </thead>
                                <tbody>

                                <tr style="border: 1px solid grey">
                                    <td style="border: 1px solid grey">1</td>
                                    <td style="border: 1px solid grey">Collections for {{{$date}}}</td>
                                    <td style="border: 1px solid grey">{{{$amount['claims']}}}</td>
                                    <td style="border: 1px solid grey">{{{$count['claims']}}}</td>
                                </tr>
                                <tr style="border: 1px solid grey">
                                    <td style="border: 1px solid grey">2</td>
                                    <td style="border: 1px solid grey">Refunds</td>
                                    <td style="border: 1px solid grey">{{{$amount['refunds']}}}</td>
                                    <td style="border: 1px solid grey">{{{$count['refunds']}}}</td>
                                </tr>
                                <tr style="border: 1px solid grey">
                                    <td style="border: 1px solid grey"></td>
                                    <td style="border: 1px solid grey">Amount to be remitted to Nodal A/c</td>
                                    <td style="border: 1px solid grey">{{{$amount['total']}}}</td>
                                    <td style="border: 1px solid grey"></td>
                                </tr>

                                </tbody>
                            </table>
                            <p>
                                Nodal A/c:<br>
                                Account Name - {{{$account['accountName']}}}<br>
                                Account No. - {{{$account['accountNumber']}}}<br>
                                IFSC Code - {{{$account['ifsc']}}}<br>
                                Bank Name- {{{$account['bank']}}}<br>
                            </p>
                        </td>
                    </tr></table>
                <p>Note: - As per RBI norm, All refund has been processed within TAT</p>

                <p>For any transaction related issue, please write to finops@razorpay.com </p><br>

                <p>For any escalation, please write to</p><br>
                <p>1st Level:- finances.recon@razorpay.com</p><br>
                <p>2nd Level:- amit.mohanty@razorpay.com</p><br>
                <p>In case of any Reconciliation file & fund related issue, please write finances.recon@razorpay.com</p> <br>

                <p>Thanks & Regards</p>
                <p>Financial Operations</p>
                <p>Razorpay Payments Private Limited</p>
            </div>
            <!-- /content -->

        </td>
        <td style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 100%; line-height: 1.6em; margin: 0; padding: 0;"></td>
    </tr></table>
<!-- /body -->
</body>
</html>
