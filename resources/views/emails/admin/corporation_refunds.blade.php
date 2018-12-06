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
                            <table style="border: 1px solid grey;">
                                <thead>
                                <tr style="border: 1px solid grey">
                                    <th style="border: 1px solid grey">TRANSACTION DATE</th>
                                    <th style="border: 1px solid grey">NO. OF ENTRIES</th>
                                    <th style="border: 1px solid grey">TOTAL AMOUNT</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr style="border: 1px solid grey">
                                    <td style="border: 1px solid grey">{{{$date}}}</td>
                                    <td style="border: 1px solid grey">{{{$count['claims']}}}</td>
                                    <td style="border: 1px solid grey">{{{$amount['claims']}}}</td>
                                </tr>
                                </tbody>
                                <thead>
                                <tr style="border: 1px solid grey">
                                    <th style="border: 1px solid grey">REFUND</th>
                                    <th style="border: 1px solid grey">NO. OF ENTRIES</th>
                                    <th style="border: 1px solid grey">REFUND AMOUNT</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr style="border: 1px solid grey">
                                    <td style="border: 1px solid grey">{{{''}}}</td>
                                    <td style="border: 1px solid grey">{{{$count['refunds']}}}</td>
                                    <td style="border: 1px solid grey">{{{$amount['refunds']}}}</td>
                                </tr>
                                <tr style="border: 1px solid grey">
                                    <td style="border: 1px solid grey">{{{'RTGS/NEFT AMOUNT'}}}</td>
                                    <td style="border: 1px solid grey">{{{''}}}</td>
                                    <td style="border: 1px solid grey">{{{$amount['total']}}}</td>
                                </tr>
                                </tbody>
                            </table>
                            <p>Nodal Account Details:</p>
                            <p>Account Name - {{{$account['accountName']}}}</p>
                            <p>Account No. - {{{$account['accountNumber']}}}</p>
                            <p>IFSC Code - {{{$account['ifsc']}}}</p>
                            <p>Bank Name- {{{$account['bankName']}}}</p>
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
