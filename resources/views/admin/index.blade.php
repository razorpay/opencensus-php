@include('partials/header')
<script>
  var org = {!! json_encode($org['data']) !!};
  var user = {!! json_encode($user['data']) !!};
</script>
</head>
<body>
  <div id="react-root" class="react-root"></div>
  <script src="{{$cdn}}/dist/admin-entry.js"></script>
</body>
</html>
