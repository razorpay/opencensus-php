<?php
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
header('P3P: CP="NO P3P"');
?>
<html>
<body>
  <div style="text-align: center; position: fixed; top: 50%; width: 100%; left: 0; margin-top: -20px;">
  Processing, Please Wait...
  </div>
  <form method="post"></form>
  <script>
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
    var load = JSON.parse(g('submitPayload'));
    document.cookie = 'submitPayload=;expires=Thu, 01 Jan 1970 00:00:01 GMT;';
    var form = document.forms[0];
    form.setAttribute('action', load.url);

    var formHTML = '';
    for(i in load.data){
      var j = i.replace(/"/g,''); // attribute sanitize
      formHTML += '<input type="hidden" name="'+j+'" value="'+load.data[i]+'">';
    }
    form.innerHTML = formHTML;
    form.submit();
  </script>
</body>
</html>