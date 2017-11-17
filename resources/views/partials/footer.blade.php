<script>
  if (window.location.hostname=="dashboard.razorpay.com") {
    (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
    })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

    ga('create', 'UA-53341507-2', 'auto');
    ga('send', 'pageview');

    if (document.cookie.match('signup_pixel=1')) {
      document.cookie = 'signup_pixel=;domain=.razorpay.com;expires=Thu, 01 Jan 1970 00:00:01 GMT';
      new Image().src = '//www.facebook.com/tr?id=697927486977350&ev=CompleteRegistration'
    }
  } else {
      ga = function () {};
  }
  </script>
</body>
</html>
