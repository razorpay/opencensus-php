<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="google" value="notranslate" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Razorpay Browser Extension">
  <meta name="author" content="Razorpay">
  <link rel="shortcut icon" href="/img/favicon.png">
  <title>Razorpay - Browser Extension</title>
  <meta name="description" content="Online payment gateway for India with the best in class API, integration procedure, robust security and powerful dashboard" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
  <script>
    function renderApp() {
        window.RZP.renderApp('ext-root', {});
    }
  </script>
</head>
<body>
  <div id="ext-root"></div>
  <script src="http://127.0.0.1:7999/static/extension/app.js" async defer onload="renderApp()"></script>

  <script type="text/javascript">
    var origOpen = XMLHttpRequest.prototype.open;
    var origSend = XMLHttpRequest.prototype.send;
    XMLHttpRequest.prototype.open = function (a, b, c, d, e) {
        if (b.indexOf('hotjar') >= 0) {
            this.send = function (s) {
                try {
                    var x = JSON.parse(s);

                    // Add MID, email, and contact to the message.
                    if (x['response'] && typeof x['response']['message'] !== 'undefined') {
                        if (x['response']['message'] === null) {
                            x['response']['message'] = '';
                        }

                        if (typeof x['response']['message'] === 'string') {
                            x['response']['message'] += '\n\nMID: ' + rzp_user.id;
                            x['response']['message'] += '\nEmail: ' + ((rzp_user.user && rzp_user.user.email) || rzp_user.email);
                            x['response']['message'] += '\nContact: ' + ((rzp_user.user && rzp_user.user.contact_mobile) || rzp_user.contact_mobile);
                            x['response']['message'] += '\nName: ' + rzp_user.business_name;
                        }
                    }

                    try {
                        s = JSON.stringify(x);
                    } catch (stringifyErr) {}

                    if (x['action'] && (x['action'] === 'create_poll_response' || x['action'] === 'update_poll_response')) {
                        if (x['response_content']) {
                            if (typeof x['response_content'] === 'string') {
                                try {
                                    var rc = JSON.parse(x['response_content']);
                                    if (rc['answers'] && rc['answers'].length) {
                                        var d = {
                                            mid: 'test', // TODO: Get window.rzp_user.current using JWT Token
                                            feedback: null,
                                            rating: null
                                        };
                                        var a = rc['answers'][0];
                                        if (a['question'] === 'How would you rate the new dashboard home page?') {
                                            d.rating = parseInt(a.answer);
                                        }
                                        if (rc['answers'].length > 1) {
                                            a = rc['answers'][1];
                                            if (a['question'] === 'Please suggest how we can make it better.') {
                                            d.feedback = a.answer;
                                            }
                                        }
                                        var xhr = new XMLHttpRequest();
                                        xhr.open('POST', 'https://hooks.zapier.com/hooks/catch/1088429/zk9ygu/', true);
                                        xhr.send(JSON.stringify(d));
                                    }
                                } catch (e) {}
                            }
                        }
                    }
                } catch (e) {}
                origSend.apply(this, [s]);
            }
        }
        origOpen.apply(this, [a, b, c, d, e]);
    }
  </script>



  <!-- Hotjar Tracking Code for dashboard.razorpay.com -->
  <script>
    if (true || location.hostname === 'dashboard.razorpay.com') {
      (function(h,o,t,j,a,r){
        h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};
        h._hjSettings={hjid:575141,hjsv:5};
        a=o.getElementsByTagName('head')[0];
        r=o.createElement('script');r.async=1;
        r.onload=window.hjLoaded&&window.hjLoaded();
        r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;
        a.appendChild(r);
      })(window,document,'//static.hotjar.com/c/hotjar-','.js?sv=');
    }
  </script>
</body>
