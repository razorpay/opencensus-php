{{--
  The code in the first <script> tag is minified and will be used while rendering the page.
  If you're looking for the source, check the 2nd script tag. Make sure it's commented out.
--}}
<script>
!function(e){e.track=Boolean;try{if(/razorpay\.in$/.test(location.origin))return;if("object"!=typeof e.events)return;var n=e.events.props;if(0===Object.keys(n).length)return;var t,o=e.events,r=o.page,a=o.load,s=o.unload,i=o.error,c="https://lumberjack.razorpay.com/v1/track",u="MC40OTMwNzgyMDM3MDgwNjI3Nw9YnGzW",p="function"==typeof navigator.sendBeacon,d=Date.now(),f=[{name:"ua_parser",input_key:"user_agent",output_key:"user_agent_parsed"}];function l(e,o){(o=o||{}).beacon=p,o.time_since_render=Date.now()-d,o.url=location.href,function(e,n){if(e&&n)Object.keys(n).forEach(function(t){e[t]=n[t]})}(o,n);var a={addons:f,events:[{event:r+":"+e,properties:o,timestamp:Date.now()}]},s=encodeURIComponent(btoa(unescape(encodeURIComponent(JSON.stringify(a))))),i=JSON.stringify({key:u,data:s});p?navigator.sendBeacon(c,i):((t=new XMLHttpRequest).open("post",c,!0),t.send(i))}a&&l("load"),s&&e.addEventListener("unload",function(){l("unload")}),i&&e.addEventListener("error",function(e){l("error",{message:e.message,line:e.line,col:e.col,stack:e.error&&e.error.stack})})}catch(e){}e.track=l}(window);
</script>
{{--
<script>
(function(window) {
  window.track = Boolean; // No-op
  try {
  // Ignore tracking on razorpay.in
    if (/razorpay\.in$/.test(location.origin)) {
      return;
    }

    if (typeof window.events !== 'object') {
      return;
    }

    // Default properties to be sent with every event payload
    var props = window.events.props;

    // events.props should have atleast have one unique key,
    // to identify the events (such as payment_id)
    if (Object.keys(props).length === 0) {
      return;
    }

    var config = window.events;

    // Name of the page
    var page = config.page;

    // Track page load
    var load = config.load;

    // Track page unload
    var unload = config.unload;

    // Track page errors
    var error = config.error;

    var url = 'https://lumberjack.razorpay.com/v1/track';
    var key = 'MC40OTMwNzgyMDM3MDgwNjI3Nw9YnGzW';
    var useBeacon = typeof navigator.sendBeacon === 'function';
    var renderTime = Date.now();
    var addons = [
      {
        name: 'ua_parser',
        input_key: 'user_agent',
        output_key: 'user_agent_parsed'
      }
    ];
    var xhr;

    function copyKeys(dest, src) {
      if (!dest || !src) return;
      Object.keys(src).forEach(function (key) {
        dest[key] = src[key];
      });
      return dest;
    }

    function track(event, properties) {
      properties = properties || {};
      properties.beacon = useBeacon;
      properties.time_since_render = Date.now() - renderTime;
      properties.url = location.href;

      // Copy default properties
      copyKeys(properties, props);

      var payload = {
        addons: addons,
        events: [{
          event: page + ':' + event,
          properties: properties,
          timestamp: Date.now()
        }]
      };

      var data = encodeURIComponent(btoa(unescape(encodeURIComponent(JSON.stringify(payload)))));
      var body = JSON.stringify({ key: key, data: data });

      if (useBeacon) {
        navigator.sendBeacon(url, body);
      } else {
        xhr = new XMLHttpRequest();
        xhr.open('post', url, true);
        // Content-type doesn't need to be set, lumberjack parses JSON automatically.
        xhr.send(body);
      }
    }

    if (load) {
      track('load');
    }

    if (unload) {
      window.addEventListener('unload', function () {
        track('unload');
      });
    }

    if (error) {
      // This is only work if the error occurs after this.
      window.addEventListener('error', function(event) {
        var properties = {
          message: event.message,
          line: event.line,
          col: event.col,
          stack: event.error && event.error.stack
        };
        track('error', properties);
      });
    }
  } catch (e) {}
  window.track = track;
})(window);
</script>
--}}
