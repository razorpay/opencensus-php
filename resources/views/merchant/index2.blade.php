@if (($isConfirmed || $isMobileConfirmed) and $isPreSignupComplete and (app('request')->input('auth_source') !== 'website' and app('request')->input('auth_source') !== 'website_homepage'))
  <script type="text/javascript">
    window.rzp_user = {!! $user !!};
    window.rzp_org = {!! $org !!};
    window.old_notifications = {!! $old_notifications !!};
    window.new_notifications = {!! $new_notifications !!};
    window.notifications = {!! $notifications !!};
    window.api_host = "{!! $api_host !!}";
    window.custom_notes = {!! $custom_notes !!};
    window.pl_expiry_in_hrs = {!! $pl_expiry_in_hrs !!};
    window.pl_extra_fields = {!! $pl_extra_fields !!};
    window.pl_customized_form_fields = {!! $pl_customized_form_fields !!};
    window.is_pl_customer_name_field_enabled = {!! $is_pl_customer_name_field_enabled !!};
    window.session_id = "{!! $session_id !!}";
    window.is_banking_request = {!! $is_banking_request !!};
  </script>
  <script async src="{{$cdnDashboardUrl}}/dist/merchant-entry.js"></script>
@else
  <!-- head tag ends here -->
  @include('partials/new-auth')
  <script type="text/javascript">
    window.session_id = "{!! $session_id !!}";
    window.isAuthPage = true;
  </script>
  @if($requestPath !== $rootPath && !$isAuthPath)
    <script>
      window.location.href = "{!! $redirectUrl !!}";
    </script>
  @endif
  <script>
    // @Todo: remove onload and onerror after debugging the missing display_google_auth event issue
    function trackScriptEvent(eventName, type) {
      try {
        if (window.rzpQ) {
          switch (type) {
            case 'success':
              window.rzpQ.push(
                window.rzpQ.now().onbr().success(eventName, {
                  mode: 'live',
                }),
              );
              break;
            case 'failed':
              window.rzpQ.push(
                window.rzpQ.now().onbr().failed(eventName, {
                  mode: 'live',
                }),
              );
              break;
            default:
              console.error("error script event");
          }
        } else {
          var checkRzpqInterval = setInterval(() => {
            if (window.rzpQ) {
              trackScriptEvent(eventName, type);
              clearInterval(checkRzpqInterval);
            }
          }, 200);
        }
      } catch (err) {
        console.error("err::", err);
      }
    }
    function oneTapError() {
      window.isOneTapScriptFailed = true;
      trackScriptEvent('signup.google_onetap_script_load', 'failed');
    };
    function oneTapSuccess() {
      window.isOneTapScriptFailed = false;
      trackScriptEvent('signup.google_onetap_script_load', 'success');
    };
    trackScriptEvent('signup.google_onetap_script_attach', 'success');
  </script>
  <script defer src="https://accounts.google.com/gsi/client" onerror="oneTapError()" onload="oneTapSuccess()"></script>
  @if($isNewAuthReArch)
    <script src="{{$cdnDashboardAssetsUrl}}/dashboard/core-bundles/newauth-dashboard/newauth-dashboard.entry.js"></script>
  @else
    <script src="{{$cdnDashboardUrl}}/dist/newAuth-entry.js"></script>
  @endif
@endif

@include('partials/blade-coverage-script')
@include('partials/footer')