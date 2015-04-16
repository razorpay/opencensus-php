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
		source = ''
		if(location.hash)
			source = location.hash.slice(1)

		function createCookie(name, value, days){
			if (days) {
				var date = new Date();
				date.setTime(date.getTime()+(days*24*60*60*1000));
				var expires = "; expires="+date.toGMTString();
			}
			else var expires = "";
			document.cookie = name+"="+value+expires+"; path=/";
		}

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

		// post message based on cookie polling
		function post_message(){
			var msg = readCookie('rzp')
			if(msg){
				parent.postMessage(msg, '*')
				createCookie('rzp', '', -1)
			}
		}
		setInterval(post_message, 300)

		var listener = function(e){
			if(e.data){
				post_message()
				var msg = (typeof e.data == 'string') ? e.data : JSON.stringify(e.data);
				createCookie('rzp-receive', msg)
			}
		}
    if (window.addEventListener) {
      window.addEventListener('message', listener, false);
    } else {
      window.attachEvent('onmessage', listener);
    }

	</script>
	</body>
</html>
