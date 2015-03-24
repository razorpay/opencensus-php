<!doctype html>
<head>
    <title>Razorpay - Payment in progress</title>
</head>
<body>
<script>
//
// everything is wrapped in the XD function to reduce namespace collisions
//
var XD = function() {

    var interval_id,
        last_hash,
        cache_bust = 1,
        attached_callback,
        window = this;

    return {
        postMessage : function(message, target_url, target) {
            if (!target_url) {
                return;
            }
            //
            // default to parent
            //
            target = target || parent;

            if (window['postMessage']) {
                //
                // the browser supports window.postMessage, so call it with a
                // targetOrigin set appropriately, based on the target_url
                // parameter.
                //
                target['postMessage'](message, target_url.replace( /([^:]+:\/\/[^\/]+).*/, '$1'));
            }
            else if (target_url) {
                //
                // the browser does not support window.postMessage,
                // so use the window.location.hash fragment hack
                //
                target.location = target_url.replace(/#.*$/, '') + '#' +
                                  (+new Date) + (cache_bust++) + '&' + message;
            }
        },
        receiveMessage : function(callback, source_origin) {
            //
            // browser supports window.postMessage
            //
            if (window['postMessage']) {
                //
                // bind the callback to the actual event associated with
                // window.postMessage
                //
                if (callback) {
                    attached_callback = function(e) {
                        if ((typeof source_origin === 'string' && e.origin !== source_origin)
                        || ((Object.prototype.toString.call(source_origin) === "[object Function]") &&
                            (source_origin(e.origin) === !1))) {
                             return !1;
                         }

                         callback(e);
                     };
                 }
                 if (window['addEventListener']) {
                     window[callback ? 'addEventListener' : 'removeEventListener']('message', attached_callback, !1);
                 } else {
                     window[callback ? 'attachEvent' : 'detachEvent']('onmessage', attached_callback);
                 }
             } else {
                 //
                 // a polling loop is started & callback is called whenever
                 // the location.hash changes
                 //
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
}();

// Do not remove the below 'callback data' comments because they help
// during tests for extracting callback data from js

// Callback data //
var data = {{json_encode($data);}};
// Callback data //

if(window.parent === window){
    // We are in popup mode
    XD.postMessage(data, '*', window.opener);
    document.cookie = "rzp="+JSON.stringify(data)+"; path=/";
}
else {
    // We are in iframe mode
    XD.postMessage(data, '*', window.parent);
}

</script>

<pre> <?php echo json_encode($data, JSON_PRETTY_PRINT); ?> </pre>

<!--Your payment is currently in progress. Please wait.-->
</body>
</html>
