@include('partials/head')
<script>
  var org = {!! json_encode($org) !!};
  var user = {!! json_encode($user) !!};
</script>
</head>
<body>
  <div id="react-root" class="react-root"></div>
  <script src="{{$cdn}}/dist/admin-entry.js"></script>
</body>
</html>
