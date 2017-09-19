
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml">
<head><title>
    Verification
</title>
    <style type="text/css">
        .spaced input[type="radio"]
        {
            margin-right: 20px;
            margin-left: 20px;
        }
    </style>
    <link href="Admin/CSS/StyleSheet.css" rel="stylesheet" type="text/css" />
    <script src="ADMIN/JS/Default.js" language="javascript" type="text/javascript"></script>

    <script type="text/javascript">

        function Custom_url() {
            var url = document.getElementById('hfRU').value;
            document.forms[0].action = url;
            document.forms[0].method = "POST";
            var v = document.getElementById('__VIEWSTATE');
            var p = document.getElementById('__EVENTTARGET');
            v.parentNode.removeChild(v);
            document.forms[0].submit();
        }
    </script>

<script type="text/javascript">
    function HideLabel() {
        var seconds = 2;
        setTimeout(function () {
            document.getElementById("lblMessage").style.display = "none";
        }, seconds * 1000);
    };
</script>
</head>
<body>
    <form method="post" action="verification.aspx" id="form1">
<input type="hidden" name="__VIEWSTATE" id="__VIEWSTATE" value="/wEPDwULLTEyODE1OTIyODFkZPQarSvsWWQqphQ2e6FIpS6TQygW6tK5pRsCU5rZxCq7" />

<input type="hidden" name="__VIEWSTATEGENERATOR" id="__VIEWSTATEGENERATOR" value="38DBBDAB" />
<input type="hidden" name="__EVENTVALIDATION" id="__EVENTVALIDATION" value="/wEdAAMwq0xfYe9SIZe6hi0PpcD+nMEpVgEfURfaTmMkoRwTgP+0yJ1DGFOzeVCSwhOJw88Y8yL3sLhtMs33+Ea422RNI2z1H2Jmvhj9R2SptF+Zlw==" />
    <div style="position: absolute; top: 1px; height: 66px !important; width: 100% !important;">
            <img src="ADMIN/Images/PNBlogo.png" id="Img1" alt="Punjab National Bank" style="width: 100%" />
            </div>
        <div style="margin-left: 400px; margin-top: 100px; margin-right: 400px; padding: 10px; background-color: lightgrey;">
            <table cellpadding="0" cellspacing="0" border="0" align="center">
                <tr>
                    <td style="padding-left:206px">
                        <span id="lblMessage"></span>
                        <input type="hidden" name="hfRU" id="hfRU" value="https://www.razorpay.com" />
        <input name="encdata" type="hidden" id="encdata" value="{{encdata}}" />
                    </td>
                </tr>
            </table>
        </div>


<script type="text/javascript">
//<![CDATA[
Custom_url();//]]>
</script>
</form>
</body>
</html>
