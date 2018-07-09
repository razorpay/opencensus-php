<?php

$baseurl = $_SERVER['HTTP_HOST'] . '/v1';

$key_id = 'rzp_test_1DP5mmOlF5G5ag';
$secret = 'thisissupersecret';

$private_url ='http://' .$key_id.':'.$secret.'@'.$baseurl;
?>
<script src="https://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
        crossorigin="anonymous"></script>

<script type="application/javascript" >

    $(document).ready(function ()
    {
        $("#link").hide();
        var createVirtualAccount = function(requestBody, callback)
        {
            $.ajax({
                    type : "POST",
                    url : "<?= $private_url ?>/virtual_accounts",
                    contentType : "application/json",
                    data : JSON.stringify(requestBody),
            }).done(function (response)
            {
                callback(null, response);

            }).fail(function (xhr)
            {
                callback(xhr);
            });

        };

        $('#bharat_qr_form').submit(function (e)
        {
            e.preventDefault();

            var requestBody = {
                amount_expected : $(this).find('.amount').val(),
                receivers: {
                    types: [
                        "qr_code"
                    ],
                },
            };

            createVirtualAccount(requestBody,
                function (error, response)
                {
                    if (error !== null)
                    {
                        return;
                    }
                    else
                    {
                        var short_url  = response.receivers[0].short_url;

                        $("#link").attr("href", short_url);

                        $("#link").show();

                    }


                });
        });

        return false;
    });
</script>
<!DOCTYPE HTML PUBLIC "-//W3C//Dtd HTML 4.0 transitional//EN">
<HTML>
<HEAD>
    <TITLE>Merchant Bharat Qr generation</TITLE>
</HEAD>

<BODY>
<form id="bharat_qr_form">
    <table border="1" align="center"  width="300">
        <tr>
            <th colspan="50" bgcolor="brown" ><font  size = 2 color = White face = verdana >Enter Parameters</th>
        </tr>
        <tr>
            <td colspan="40">Amount (in Paise): </b> </td>
            <td><input type="text" class="amount" name="amount"></td>
        </tr>
        <br />
        <tr>
            <td colspan="100" align="center"><input type="submit" value="Generate Bharat Qr"></td>
        </tr>
    </table>
    <br>
    <div align="center">
    <a href="https://www.w3schools.com" id="link">Please click here to download Bharat Qr</a>
    </div>
</form>

</BODY>
</HTML>