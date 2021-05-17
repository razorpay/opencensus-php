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
                <table style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 100%; line-height: 1.6em; width: 100%; margin: 0; padding: 0;">
                    <tr style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 100%; line-height: 1.6em; margin: 0; padding: 0;">
                        <td style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 100%; line-height: 1.6em; margin: 0; padding: 0;">
                            <p>Dear Team,</p>
                            <p>Please find the below Payment Summary Report for -- DCB Bank Payment Gateway transactions.</p>
                            <table style="border: 1px solid grey;">
                                <thead>
                                <tr style="border: 1px solid grey">
                                    <th style="border: 1px solid grey">Sr. No.</th>
                                    <th style="border: 1px solid grey">Particulars</th>
                                    <th style="border: 1px solid grey">Amount (Rs.)</th>
                                    <th style="border: 1px solid grey">No of Txns.</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr style="border: 1px solid grey">
                                    <td style="border: 1px solid grey; text-align: center">1</td>
                                    <td style="border: 1px solid grey; text-align: center">Sale transactions</td>
                                    <td style="border: 1px solid grey; text-align: center">{{{$amount['claims']}}}</td>
                                    <td style="border: 1px solid grey; text-align: center">{{{$count['claims']}}}</td>
                                </tr>
                                <tr style="border: 1px solid grey">
                                    <td style="border: 1px solid grey; text-align: center">2</td>
                                    <td style="border: 1px solid grey; text-align: center">Refunds</td>
                                    <td style="border: 1px solid grey; text-align: center">{{{$amount['refunds']}}}</td>
                                    <td style="border: 1px solid grey; text-align: center">{{{$count['refunds']}}}</td>
                                </tr>
                                <tr style="border: 1px solid grey">
                                    <td style="border: 1px solid grey; text-align: center">3</td>
                                    <td style="border: 1px solid grey; text-align: center">Amount to be remitted in Nodal account</td>
                                    <td style="border: 1px solid grey; text-align: center">{{{$amount['total']}}}</td>
                                </tr>
                                </tbody>
                            </table>
                            <p>Kindly arrange to transfer Amount to the below mentioned nodal A/C details<br>
                                <b>Account Number - </b>{{{$account['accountNumber']}}}<br>
                                <b>Account Title - </b>{{{$account['accountName']}}}<br>
                                <b>Bank Name - </b> {{{$account['bankName']}}}<br>
                                <b>Bank Branch City - </b> {{{$account['branchCity']}}}<br>
                                <b>Bank Branch Location - </b> {{{$account['branchLocation']}}}<br>
                                <b>Bank IFSC Code - </b>{{{$account['ifsc']}}}<br>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            <!-- /content -->

        </td>
        <td style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 100%; line-height: 1.6em; margin: 0; padding: 0;"></td>
    </tr></table>
<!-- /body -->
</body>
</html>
