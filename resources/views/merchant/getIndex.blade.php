@include('partials/head')

<link rel='shortcut icon' href='/img/logo.png'>

@if ($isConfirmed and $isPreSignupComplete)
  <% for (var css in htmlWebpackPlugin.files.css) { %>
    <link href="<%= htmlWebpackPlugin.files.css[css] %>" rel="stylesheet">
  <% } %>
@else
  <link rel='stylesheet' href='css/generated{{asset('style.css')}}' type='text/css' />
@endif

@include('partials/common')

@if ($isConfirmed and $isPreSignupComplete)
  <script type="text/javascript">
    window.rzp_user = {!! $user !!};
    window.rzp_org = {!! $org !!};
  </script>
  <% for (var chunk in htmlWebpackPlugin.files.chunks) { %>
  <script src="<%= htmlWebpackPlugin.files.chunks[chunk].entry %>"></script>
  <% } %>
@else
  <!-- jQuery & angular -->
  <script src='{{asset('js/generated/pre.js')}}'></script>
  <!-- Merchant Js-->
  <script src='{{asset('js/generated/merchant.js')}}'></script>
@endif

<!-- supportkiy code -->
@if(env('APP_ENV') !== 'testing')
   @include('partials/supportkit')
@endif

@include('partials/footer')
