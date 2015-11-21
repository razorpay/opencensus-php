<?php
header('P3P: CP="We dont have any P3P Policy"');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
?>
<!doctype html>
<html>
	<head>
	</head>
	<body>
	<script>
		function readCookie(name){
			var nameEQ = name + "=";
			var ca = document.cookie.split(';');
			for(var i=0;i < ca.length;i++){
				var c = ca[i];
				while (c.charAt(0)==' ') c = c.substring(1,c.length);
				if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
			}
			return null;
		}
		setInterval(function(){
			var msg = readCookie('onComplete')
			if(msg){
				parent.postMessage(msg, '*')
			}
		}, 400)
	</script>
	</body>
</html>
