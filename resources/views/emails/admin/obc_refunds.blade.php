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
                <table border="1">
                    <tr>
                        <td colspan="9" align="center">PGI NAME - OBC NetBanking eCommerce Payment Settlement Summary</td>
                    </tr>
                    <tr>
                        <td colspan="9" align="center">Settlement Date: {{{$date}}}</td>
                    </tr>
                    <tr>
                        <td colspan="2" align="center"></td>
                        <td colspan="2" align="center">REJECTED</td>
                        <td colspan="2" align="center">TOTAL SETTLED</td>
                        <td colspan="2" align="center">REFUNDS</td>
                        <td>NET</td>
                    </tr>
                    <tr>
                        <td align="center">SL#</td>
                        <td align="center">Trxn Date</td>
                        <td align="center">No. of Dupl/ Rej Trxn</td>
                        <td align="center">Rej. Trxn Amount</td>
                        <td align="center">No. of Trxns</td>
                        <td align="center">Trxn Amount</td>
                        <td align="center">No. of Refunds</td>
                        <td align="center">Refund Amount</td>
                        <td align="center">Net Amt.Payable to PGI/ To be Remitted to Nodal A/c</td>
                    </tr>
                    <tr>
                        <td>1</td>
                        <td>{{{$date}}}</td>
                        <td align="right">0</td>
                        <td align="right">0.00</td>
                        <td align="right"> {{{$count['claims']}}}</td>
                        <td align="right">{{{$amount['claims']}}}</td>
                        <td align="right">{{{$count['refunds']}}}</td>
                        <td align="right">{{{$amount['refunds']}}}</td>
                        <td align="right">{{{$amount['total']}}}</td>
                    </tr>

                </table>
    </tr></table>
<!-- /body -->
</body>
</html>
