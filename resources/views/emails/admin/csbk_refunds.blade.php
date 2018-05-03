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
                            <p>PFA refund file.</p>
                            <table style="border: 1px solid grey;">
                                <thead>
                                <tr style="border: 1px solid grey">
                                    <th style="border: 1px solid grey">Date</th>
                                    <th style="border: 1px solid grey">No. Of Txns</th>
                                    <th style="border: 1px solid grey">Total Transaction Amt</th>
                                    <th style="border: 1px solid grey">No. Of Refund Txns</th>
                                    <th style="border: 1px solid grey">Total Refunds Amt</th>
                                    <th style="border: 1px solid grey">Net Amt</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr style="border: 1px solid grey">
                                    <td style="border: 1px solid grey">{{{$date}}}</td>
                                    <td style="border: 1px solid grey">{{{$count['claims']}}}</td>
                                    <td style="border: 1px solid grey">{{{$amount['claims']}}}</td>
                                    <td style="border: 1px solid grey">{{{$count['refunds']}}}</td>
                                    <td style="border: 1px solid grey">{{{$amount['refunds']}}}</td>
                                    <td style="border: 1px solid grey">{{{$amount['total']}}}</td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </table>

                <br><br>
                <div>Account details</div>
                <table style="border: 1px solid grey;">
                    <thead>
                    <tr style="border: 1px solid grey">
                        <th style="border: 1px solid grey">Bank</th>
                        <th style="border: 1px solid grey">Account Number</th>
                        <th style="border: 1px solid grey">Account name</th>
                        <th style="border: 1px solid grey">IFSC</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr style="border: 1px solid grey">
                        <td style="border: 1px solid grey">{{{$accountDetails['bankName']}}}</td>
                        <td style="border: 1px solid grey">{{{$accountDetails['accountNumber']}}}</td>
                        <td style="border: 1px solid grey">{{{$accountDetails['accountName']}}}</td>
                        <td style="border: 1px solid grey">{{{$accountDetails['ifsc']}}}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <!-- /content -->

        </td>
        <td style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 100%; line-height: 1.6em; margin: 0; padding: 0;"></td>
    </tr></table>
<!-- /body -->
</body>
</html>
