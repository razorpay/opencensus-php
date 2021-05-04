<?php
    $isOrgHDFC = (json_decode($org, true)['custom_code']) === "hdfc";
?>


@include('partials/header')

@if ($isConfirmed and $isPreSignupComplete)

@elseif ($newAuthFlow === false)
  <link rel='stylesheet' href="{{$cdnDashboardUrl}}/css/generated/signup.css" type='text/css' />

@endif

@if ($newAuthFlow === true)
  <title>Create your Razorpay Account - Razorpay</title>
  <meta name="description" content="Welcome to Razorpay! Create your free Razorpay account today. Sign up for free to join the millions of users that trust us with their payments, banking & working capital." />
  @include('partials/new-auth')
@else
  <title>Razorpay Dashboard</title>
  <meta name="description" content="Online payment gateway for India with the best in class API, integration procedure, robust security and powerful dashboard" />
  @include('partials/common')
@endif

<script>
var _dcq = _dcq || [];
var _dcs = _dcs || {};
_dcs.account = '9421167';

(function() {
  var dc = document.createElement('script');
  dc.type = 'text/javascript'; dc.async = true;
  dc.src = '//tag.getdrip.com/9421167.js';
  var s = document.getElementsByTagName('script')[0];
  s.parentNode.insertBefore(dc, s);
})();

</script>
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-928471290"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-928471290');
</script>
<script async defer src="https://apis.google.com/js/api:client.js"></script>

<script>
  // @Todo: remove onload and onerror after debugging the missing display_google_auth event issue
  function scriptEvent(eventName, scriptSrc, type) {
    try {
      if (window.rzpQ) {
        switch (type) {
          case 'success':
            window.rzpQ.push(
              window.rzpQ.now().onbr().success(eventName, {
                mode: 'live',
                src: scriptSrc,
              }),
            );
            break;
          case 'failed':
            window.rzpQ.push(
              window.rzpQ.now().onbr().failed(eventName, {
                mode: 'live',
                src: scriptSrc,
              }),
            );
            break;
          default:
            console.error("er::", scriptSrc);
        }
      } else {
        var checkRzpqInterval = setInterval(() => {
          if (window.rzpQ) {
            scriptEvent(eventName, scriptSrc, type);
            clearInterval(checkRzpqInterval);
          }
        }, 200);
      }
    } catch (err) {
      console.error("err::", err);
    }
  }
  var gAuthOneTapscript = document.createElement('script');
  var onetapScriptSrc = 'https://accounts.google.com/gsi/client';
  gAuthOneTapscript.async = true;
  document.documentElement.appendChild(gAuthOneTapscript);
  gAuthOneTapscript.onerror = function () {
    window.isOneTapScriptFailed = true;
    scriptEvent('signup.google_onetap_script_load', onetapScriptSrc, 'failed');
  };
  gAuthOneTapscript.onload = function () {
    window.isOneTapScriptFailed = false;
    scriptEvent('signup.google_onetap_script_load', onetapScriptSrc, 'success');
  };
  gAuthOneTapscript.src = onetapScriptSrc;
</script>

<script>
  document.domain = window.location.hostname.split(".").slice(-2).join(".");

  window.RZP = window.RZP || {};

  if (
    window.parent !== window &&
    ~window.parent.location.href.indexOf("{{ config('app.banking_service_url') }}")
  ) {

    document.write("<script src='{{ config('app.banking_service_url') }}/dist/pgClient.js'>\<\/script>");

    window.RZP.appHost = "{{ config('app.banking_service_url') }}";
    window.RZP.appName = "businessbanking";

    window.rzpTicketSystem = {
      hostname: window.RZP.appHost
    };
  }
</script>
<!-- Hotjar Tracking Code for dashboard.razorpay.com -->
@if(env('APP_ENV') === 'production')
  @include('partials/hotjar')
@endif

@if ($isConfirmed and $isPreSignupComplete)
  <script type="text/javascript">
    window.rzp_user = {!! $user !!};
    window.rzp_org = {!! $org !!};
    window.notifications = {!! $notifications !!};
    window.api_host = "{!! $api_host !!}"
    window.custom_notes = {!! $custom_notes !!};
    window.pl_expiry_in_hrs = {!! $pl_expiry_in_hrs !!};
    window.pl_extra_fields = {!! $pl_extra_fields !!};
    window.pl_customized_form_fields = {!! $pl_customized_form_fields !!};
    window.is_pl_customer_name_field_enabled = {!! $is_pl_customer_name_field_enabled !!};
    window.session_id = "{!! $session_id !!}"
  </script>

  @if(env('APP_ENV') === 'production')
    @include('partials/sentry')
  @endif
  <script src="https://www.recaptcha.net/recaptcha/api.js?render=explicit"></script>
  <script src="{{$cdnDashboardUrl}}/dist/merchant-entry.js"></script>
@else
  <script type="text/javascript">
      window.session_id = "{!! $session_id !!}"
  </script>
  @if($requestPath !== $rootPath and $newAuthFlow === false)
    <script>
      window.location.href = "{!! $redirectUrl !!}"
    </script>
  @endif

  @if ($newAuthFlow === true)
    <script src="{{$cdnDashboardUrl}}/dist/newAuth-entry.js"></script>
  @else
    <script src='{{$cdnDashboardUrl}}/js/generated/signup.js'></script>
  @endif

@endif


@include('partials/footer')
