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
                            <p>“Dear Team,</p>
                            <p>Request you to transfer fund to our Nodal account for transaction dated {{{$from}}} to {{{$to}}} .</p>
                            <table style="border: 1px solid grey;">
                                <thead>
                                <tr style="border: 1px solid grey">
                                    <th style="border: 1px solid grey">Sr. No</th>
                                    <th style="border: 1px solid grey">Particular</th>
                                    <th style="border: 1px solid grey">Amount</th>
                                    <th style="border: 1px solid grey">No of Txns.</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr style="border: 1px solid grey">
                                    <td style="border: 1px solid grey; text-align: center">1</td>
                                    <td style="border: 1px solid grey">Transaction  for {{{$from}}} to {{{$to}}}</td>
                                    <td style="border: 1px solid grey; text-align: center">{{{$amount['claims']}}}</td>
                                    <td style="border: 1px solid grey; text-align: center">{{{$count['claims']}}}</td>
                                </tr>
                                <tr style="border: 1px solid grey">
                                    <td style="border: 1px solid grey; text-align: center">2</td>
                                    <td style="border: 1px solid grey">Refund</td>
                                    <td style="border: 1px solid grey; text-align: center">{{{$amount['refunds']}}}</td>
                                    <td style="border: 1px solid grey; text-align: center">{{{$count['refunds']}}}</td>
                                </tr>
                                <tr style="border: 1px solid grey">
                                    <th colspan="2">Amount to be remitted in Nodal account</th>
                                    <td colspan="2" style="border: 1px solid grey; text-align: center">{{{$amount['total']}}}</td>
                                </tr>
                                </tbody>
                            </table>
                            <p>
                                <b>Please find below our nodal bank details:-</b><br>
                                <b>Account Number - </b>{{{$account['accountNumber']}}}<br>
                                <b>Bank Name - </b> {{{$account['bank']}}}<br>
                                <b>Beneficiary Name - </b>{{{$account['accountName']}}}<br>
                                <b>IFSC Code - </b>{{{$account['ifsc']}}}<br>
                            </p>
                            <p>
                                Note: - As per RBI norm, All refund has been processed within TAT<br>
                            </p>
                            <p>
                                For any transaction related issue, please write to {{{$rzpEmailId['transaction']}}}<br>
                                For any escalation, please write to <br>
                                1st Level:- {{{$rzpEmailId['l1']}}}<br>
                                2nd Level:- {{{$rzpEmailId['l2']}}}<br>
                            </p>
                            <p>
                                In case of any Reconciliation file & fund related issue, please write to – {{{$rzpEmailId['recon']}}}”
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
