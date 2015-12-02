<?php
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
header('P3P: CP="NO P3P"');
?>
<html>
<head>
  <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">
</head>
<body>
  <form style="text-align: center; position: fixed; top: 50%; width: 100%; left: 0; margin-top: -20px; font-size: 16px;">
    Processing, Please Wait...
  </form>
  <script>
    var form = document.forms[0];
    function g(name){
      var nameEQ = name + "=";
      var ca = document.cookie.split(';');
      for(var i=0;i < ca.length;i++){
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
      }
      return null;
    }
    setInterval(function(){
      var next = g('nextRequest');

      if(next){
        var load = JSON.parse(atob(next));
        document.cookie = 'nextRequest=;expires=Thu, 01 Jan 1970 00:00:01 GMT;';
        if(load.result){
          form.innerHTML = load.result + '<br>You can close this tab now.';
          return;
        }
        form.setAttribute('action', load.url);
        form.setAttribute('method', load.method || 'get');

        var formHTML = '';
        if(typeof load.content === 'object'){
          for(var i in load.content){
            var j = i.replace(/"/g,''); // attribute sanitize
            formHTML += '<input type="hidden" name="'+j+'" value="'+load.content[i]+'">';
          }
        }
        form.innerHTML += formHTML;
        form.submit();
      }
    }, 300)
  </script>
</body>
</html>