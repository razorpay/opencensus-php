<?php set_time_limit(0); ?> 

<!DOCTYPE HTML PUBLIC "-//W3C//Dtd HTML 4.0 transitional//EN">
<HTML>
<HEAD>
	<TITLE>Sample Page-TranPortal VbyV</TITLE>
<!-- 	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
	<script>
		$(document).ready(function()
	  {
	    $('#txnform').submit(function()
	    {
	      var key = $('#usernameInput').val();

	      $.ajax(
	        {
	          'username' : key,
	          'url'      : 'http://rzp/transactions',
	          'type'     : 'POST',
	          'success'  : function(){ window.location = 'http://www.website.com/basic-auth-file.php'; },
	          'error'    : function(){ alert('Bad Login Details');},
	        }
	      );

	      return false;
	    });
	  });
	</script>
 -->
 </HEAD>

<BODY>
<table border="1" align="center"  width="100%" >
	<tr>
	<td align="left" width="90%"><font  size = 5 color = darkblue face = verdana ><b>Sample Page</td>
	<td align="right"width="10%"><IMG SRC="images/fss1.JPG" WIDTH="169" HEIGHT="37" BORDER="0" ALT=""></td>
	</tr>
</table>
<br><br>  
<form method="post" id="txnform" action="http://d9c6bf091a1a64cb5678d8c1d5e7360f:@rzp/transactions">	
<table border="1" align="center"  width="300">
	<tr>
	<th colspan="50" bgcolor="brown" ><font  size = 2 color = White face = verdana >Enter Parameters</th>
	</tr>
	<tr>
		<td colspan="40">Card No: </b> </td>
		<td><input type="text" name="card[number]" value="1234567890" size="25"></td>
	</tr>
	<TR>
		<TD colspan="40">CVV:</TD>
		<TD><input size="3" type="text" name="card[cvv]" value="880" maxlength=4></TD>
	</TR>
	<TR>
		<TD colspan ='40'>ExpMonth:</TD>
		<TD colspan='40'><select type="text" name="card[expiry_month]" value="1" >
			<option value="1">1</option> 
			<option value="2">2</option> 
			<option value="3">3</option> 
			<option value="4">4</option> 
			<option value="5">5</option> 
			<option value="6">6</option> 
			<option value="7">7</option> 
			<option value="8">8</option> 
			<option value="9">9</option> 
			<option value="10">10</option> 
			<option value="11">11</option> 
			<option value="12" selected>12</option> 
			</select>
		ExpYear:
		<select type="text" name="card[expiry_year]" value="2014">
			<option value="2011">2011</option> 
			<option value="2012">2012</option> 
			<option value="2013"selected>2013</option> 
			<option value="2014">2014</option> 
			<option value="2015">2015</option> 
			<option value="2016">2016</option>
			<option value="2013">2017</option> 
			<option value="2014">2018</option> 
			<option value="2015">2019</option> 
			<option value="2016">2020</option> 
			</select>
		</TD></tr>  
		<TR>
		<TD colspan='40'>Amount:</TD>
		<td><input type="text" name="amount" size="25" value="500"></td>
		<tr>
		<TR>
		<TD colspan='40'>Process:</TD>
		<td><input type="text" name="process" size="25" value="1"></td>
		<tr>
		<TR>
		<TD colspan='40'>CardHolder/Member Name:</TD>
		<td><input type="text" name="card[cardholder]" size="25" value="shashank"></td>
		<td><input type="text" name="udf[email]" size="25" value="shk@gmail.com"></td>
		<td><input type="text" name="udf[contact]" size="25" value="1234567890"></td>
		<input type="hidden" value="INR" name="currency">
		<tr>
	<td colspan="100" align="center"><input type="submit" value="  Submit  "></td>
	</tr>
	<tr>
	<th colspan="50" bgcolor="brown" height="15"></th>
	</tr>
</table>
<br><br>
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
</form>	
</BODY>
</HTML>
<!-- Disclaimer:- Important Note in Sample Pages
- This is a sample demonstration page only ment for demonstration, this page should not be used in production
- Transaction data should only be accepted once from a browser at the point of input, and then kept in a way that does not allow others to modify it (example server session, database  etc.)
- Any transaction information displayed to a customer, such as amount,card no  should be passed only as display information and the actual transactional data should be retrieved from the secure source last thing at the point of processing the transaction.
- Any information passed through the customer's browser can potentially be modified/edited/changed/deleted by the customer, or even by third parties to fraudulently alter the transaction data/information. Therefore, all transaction information should not be passed through the browser to Payment Gateway in a way that could potentially be modified (example hidden form fields). 
 -->