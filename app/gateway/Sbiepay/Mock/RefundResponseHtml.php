<html>
    <head>
        </head>
            <body>
            <form name=serverposting action=http://www.example.com  method=post>
                <input type=hidden name=encRefundData  value=<?php echo $encData['encRefundData'] ?>>
                <input type=hidden name=Bank_Code  value=1000109>
                <input type=hidden name=merchIdVal  value=1000109>
            </form>
            <script language=javascript>document.serverposting.submit()</script>
        </body>
    </html>
