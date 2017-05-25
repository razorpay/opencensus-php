  <!-- Google Tag Manager -->
  <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
  new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
  j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
  'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
  })(window,document,'script','dataLayer','GTM-K69HR4C');</script>
  <!-- End Google Tag Manager -->

</head>
<body ng-controller="AppCtrl">
  <!-- Google Tag Manager (noscript) -->
  <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-K69HR4C"
  height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
  <!-- End Google Tag Manager (noscript) -->

  <!-- The Splash Screen Begins (must be first) -->
  <div id="splash" ng-if="flag" style="position: absolute;top: 50%;left: 50%;margin-left: -20px;margin-top: -40px;">
    <svg version='1.1' id='loader-1' xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' x='0px' y='0px' width='40px' height='40px' viewBox='0 0 50 50' style='enable-background:new 0 0 50 50;' xml:space='preserve'>
      <path fill='#7266ba' d='M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z' transform='rotate(80.1374 25 25)'>
        <animateTransform attributeType='xml' attributeName='transform' type='rotate' from='0 25 25' to='360 25 25' dur='0.6s' repeatCount='indefinite'></animateTransform>
      </path>
    </svg>
  </div>
  <!-- Splash Screen ends -->
  <div class="app" id="app" ng-class="{'app-header-fixed':app.settings.headerFixed, 'app-aside-fixed':app.settings.asideFixed, 'app-aside-folded':app.settings.asideFolded}" ui-view></div>

  <script type='text/javascript'>
    window.__lo_site_id = 77197;

    (function() {
      var wa = document.createElement('script'); wa.type = 'text/javascript'; wa.async = true;
      wa.src = 'https://d10lpsik1i8c69.cloudfront.net/w.js';
      var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(wa, s);
      })();
  </script>
  <script type="text/javascript" src="https://www.googleadservices.com/pagead/conversion_async.js" charset="utf-8"></script>
