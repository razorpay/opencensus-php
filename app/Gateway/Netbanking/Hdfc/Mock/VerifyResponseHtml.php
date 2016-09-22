<html>
    <head>
        <META http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
            <link HREF="/css/RS_eng.css" TYPE="text/css" REL="STYLESHEET">
                <script language="javascript">

var url = "https://<?= $redirectUrl ?>/?ClientCode=cuteprakash2006yahoocom&MerchantCode=RAZPRAZORPAY&TxnAmount=7999.00&MerchantRefNo=3lGLlo5CFtSIkz&SuccessStaticFlag=N&FailureStaticFlag=N&Date=13/08/2015 16:08:07&TransactionId=XTXTV01&flgVerify=Y&BankRefNo=&flgSuccess=F&Message=";
//-----------------------------------------------------------------------------
function is_space1 (
    p_string
) {
    var l_new_string = p_string, l_i;
    var l_length = p_string.length;

    for (l_i=0;l_i<=l_length;l_i++) {
        var l_chr = l_new_string.charAt(l_i);
        if (l_chr == " ") {
            l_new_string = l_new_string.replace (l_chr,"+");
        }
    }
    return (l_new_string);
}
//-----------------------------------------------------------------------------
function logout(){
    document.frmLogin.submit();
    return false;
}
//-----------------------------------------------------------------------------

    </script>
        </head>
            <body onload="return logout()" bgcolor="#dfdfdf">
            <form action="entry" method="POST" name="frmLogin">
                <input value="XT" name="fldAppId" type="hidden">
                <input value="LGF" name="fldTxnId" type="hidden">
                <input value="01" name="fldScrnSeqNbr" type="hidden">
                <input value="VYMMHHBTGHMWUCYDWA" name="fldSessionId" type="hidden">
                <input value="VYMMHHBTGHMWUCYDWASKUYLKAWWKW" name="fldRequestId" type="hidden">
                <input value="<?= $redirectUrl ?>" name="REDIRECTURL" type="hidden">
                <input value="P" name="method" type="hidden">
                <input value="" name="methodtype" type="hidden">
            </form>
        </body>
    </html>
