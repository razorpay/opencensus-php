<?php
$baseurl = "api.razorpay.com";

$key_id = 'd9c6bf091a1a64cb5678d8c1';
$secret = 'thisissupersecret';

$public_url = $key_id.'@'.$baseurl;
$private_url = $key_id.':'.$secret.'@'.$baseurl;
?>

<!DOCTYPE HTML PUBLIC "-//W3C//Dtd HTML 4.0 transitional//EN">
<HTML>
<HEAD>
	<TITLE>Testing Page-TranPortal VbyV</TITLE>
</HEAD>

<BODY>
<table border="1" align="center"  width="100%" >
	<tr>
	<td align="left" width="90%"><font  size = 5 color = darkblue face = verdana ><b>Testing Page</td>
	<td align="right"width="10%"><IMG SRC="images/fss1.JPG" WIDTH="169" HEIGHT="37" BORDER="0" ALT=""></td>
	</tr>
</table>
<br><br>
<form method="post" id="txnform" action="//<?=$public_url?>/transactions">
<table border="1" align="center"  width="300">
	<tr>
	<th colspan="50" bgcolor="brown" ><font  size = 2 color = White face = verdana >Enter Parameters</th>
	</tr>
	<tr>
		<td colspan="40">Card No: </b> </td>
		<td><input type="text" name="card[number]" value="4012001037490014" size="25"></td>
	</tr>
	<TR>
		<TD colspan="40">CVV:</TD>
		<TD><input size="3" type="text" name="card[cvv]" value="880" maxlength=4></TD>
	</TR>
	<TR>
		<TD colspan ='40'>Exp Date:</TD>
        <td><input type="text" name="card[expiry_month]" value="11"></td>
        <td><input type="text" name="card[expiry_year]" value="2015"></td>
		<tr>
		<TD colspan='40'>Amount:</TD>
		<td><input type="text" name="amount" size="25" value="500"></td>
		</tr>
		<!--<TR>
		<TD colspan='40'>Hold:</TD>
		<td>
		<select name="hold">
			<option value="0" selected>No</option>
			<option value="1">Yes</option>
			</select>
		</td>
	</tr>-->
		<TR>
		<TD colspan='40'>CardHolder/Member Name:</TD>
		<td><input type="text" name="card[name]" size="25" value="shashank"></td>
		<td><input type="text" name="udf[email]" size="25" value="shk@gmail.com"></td>
		<td><input type="text" name="udf[contact]" size="25" value="1234567890"></td>
		<input type="hidden" value="INR" name="currency">
		</TR>
		<tr>
	<td colspan="100" align="center"><input type="submit" value="  Submit  "></td>
	</tr>
	<tr>
	<th colspan="50" bgcolor="brown" height="15"></th>
	</tr>
</form>
</table>
<br><br>
<div style="text-align:center">
<h3>Test Capture/Refund</h3>
<form name ="refund" method="post" action="http://<?=$private_url?>/transactions/">
<input type="text" id="refund_id" placeholder="Enter transaction id to refund"/>
<input type="submit" value="Refund" onClick="javascript:document.refund.action = document.refund.action + document.getElementById('refund_id').value +'/refund'; document.refund.submit(); return false;"/>
</form>
<form name ="capture" method="post" action="http://<?=$private_url?>/transactions/">
<input type="text" id="capture_id" placeholder="Enter transaction id to capture"/>
<input type="submit" value="Capture" onClick="javascript:document.capture.action = document.capture.action + document.getElementById('capture_id').value +'/capture'; document.capture.submit(); return false;"/>
</form>
</div>

<table width="96%" border="0" cellspacing="0" cellpadding="0">
<tr>
	<td height="2" bgcolor="black" class="titleline"></td>
</tr>
</table>
<table border="1" align="center"  width="100%" >
	<tr>
	<td align="left" width="90%"><font  size = 5 color = darkblue face = verdana ><b>Sample Page</td>
	<td align="right"width="10%"><IMG SRC="images/fss1.JPG" WIDTH="169" HEIGHT="37" BORDER="0" ALT=""></td>
	</tr>
</table>

</BODY>
</HTML>