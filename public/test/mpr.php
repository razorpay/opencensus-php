<?php
require('../scripts/sanitizeParams.php');

$baseurl = $_SERVER['HTTP_HOST'] . '/v1';

$key_id = 'rzp_test';
$secret = 'DASHBOARD_AUTH_PASS';

$public_url = $key_id.'@'.$baseurl;
$private_url = $key_id.':'.$secret.'@'.$baseurl;
?>

<!DOCTYPE HTML PUBLIC "-//W3C//Dtd HTML 4.0 transitional//EN">
<HTML>
<HEAD>
    <TITLE>Merchant Payment Report Upload Page for HDFC MPR</TITLE>
</HEAD>

<BODY>
<form enctype="multipart/form-data" method="post" action="//<?=$private_url?>/gateway/hdfc/mpr">
<table border="1" align="center"  width="300">
    <tr>
    <th colspan="50" bgcolor="brown" ><font  size = 2 color = White face = verdana >Enter Parameters</th>
    </tr>
    <tr>
        <td colspan="40">Hdfc Mpr: </b> </td>
        <td><input type="file" name="hdfc_mpr"></td>
    </tr>
    <br />
    <tr>
        <td colspan="100" align="center"><input type="submit" value="Upload Hdfc Mpr file"></td>
    </tr>
</form>
</table>

</BODY>
</HTML>