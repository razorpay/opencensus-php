<?php
header('P3P: CP="We dont have any P3P Policy"');
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

		XD = (function(){

		var interval_id,
		last_hash,
		cache_bust = 1,
		attached_callback;
		// window = this;

		return {
			postMessage : function(message, target_url, target) {
			  if (!target_url) {
			    return;
			  }
			  target = target || parent;  // default to parent
			  if (window['postMessage']) {
			    // the browser supports window.postMessage, so call it with a targetOrigin
			    // set appropriately, based on the target_url parameter.
			    target['postMessage'](message, target_url.replace( /([^:]+:\/\/[^\/]+).*/, '$1'));
			  } else if (target_url) {
			    // the browser does not support window.postMessage, so use the window.location.hash fragment hack
			    target.location = target_url.replace(/#.*$/, '') + '#' + (+new Date) + (cache_bust++) + '&' + message;
			  }
			},
			receiveMessage : function(callback, source_origin) {
			  // browser supports window.postMessage
			  if (window['postMessage']) {
			    // bind the callback to the actual event associated with window.postMessage
			    if (callback) {
			      attached_callback = function(e) {
			        if ((typeof source_origin === 'string' && e.origin !== source_origin)
			        || (Object.prototype.toString.call(source_origin) === "[object Function]" && source_origin(e.origin) === !1)) {
			           return !1;
			         }
			         callback(e);
			       };
			    }
			    else if(!attached_callback){
			      return
			    }
			    if (window['addEventListener']) {
			      window[callback ? 'addEventListener' : 'removeEventListener']('message', attached_callback, !1);
			    } else {
			      window[callback ? 'attachEvent' : 'detachEvent']('onmessage', attached_callback);
			    }
			    if(!callback){
			      attached_callback = null
			    }
			  } else {
			     // a polling loop is started & callback is called whenever the location.hash changes
			    interval_id && clearInterval(interval_id);
			    interval_id = null;
			    if (callback) {
			      interval_id = setInterval(function() {
			        var hash = document.location.hash,
			        re = /^#?\d+&/;
			        if (hash !== last_hash && re.test(hash)) {
			          last_hash = hash;
			          callback({data: hash.replace(re, '')});
			        }
			      }, 100);
			    }
			  }
			}
			};
		})();
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
				XD.postMessage(JSON.parse(msg), '*', parent)
				createCookie('rzp', '', -1)
			}
		}
		setInterval(post_message, 300)

		XD.receiveMessage(function(e){
			if(e.data){
				post_message()
				createCookie('rzp-receive', JSON.stringify(e.data))
			}
		}, source)

	</script>
	</body>
</html>