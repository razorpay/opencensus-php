@include('partials/header')
<style>
  body {
    background: #F0F3F4;
    font-family: -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen,Ubuntu,Cantarell,'Open Sans','Helvetica Neue',sans-serif;
    font-size: 14px;
    color: #414141;
    margin: 0;
  }
  .container {
    max-width: 300px;
    margin: 30px auto;
    text-align: center;
  }
  img {
    margin: 15px auto;
    max-width: 240px;
    height: 80px;
    display: block;
  }
  input {
    width: 100%;
    padding: 16px 20px;
    border: 1px solid #eee;
    margin-top: -1px;
    background: #fff;
    box-sizing: border-box;
    outline: none;
    font-size: 14px;
  }
  input[type=submit] {
    border-radius: 2px;
    cursor: pointer;
    margin-top: 20px;
    border: none;
    background: #3498db;
    color: #fff;
  }
</style>
</head>
<body>
<form class="container" action="/admin/signin" method="post" onsubmit="return false">
  <b>Admin Login</b>
  <img alt="Logo" src="{{$org['login_logo_url']}}">
  <input autofocus name="username" placeholder="Username">
  <input type="password" name="password" placeholder="Password">
  <input type="submit" value="Login">
</form>
<script>
function readCookie(name) {
  var nameEQ = name + "=";
  var ca = document.cookie.split(';');
  for(var i=0;i < ca.length;i++) {
    var c = ca[i];
    while (c.charAt(0)==' ') c = c.substring(1,c.length);
    if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
  }
}
document.forms[0].onsubmit = function(e) {
  e.preventDefault();
  xhr = new XMLHttpRequest()
  var data = 'username=' + encodeURIComponent(document.querySelector('input').value) +
    '&password=' + encodeURIComponent(document.querySelector('input[type=password]').value)

  xhr.onreadystatechange = function() {
    if (xhr.readyState === 4) {
      debugger
    }
  }
  xhr.open(this.method, this.action)
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8')
  xhr.setRequestHeader('X-XSRF-TOKEN', readCookie('XSRF-TOKEN'))
  xhr.setRequestHeader('Accept', 'application/json, text/plain, */*')
  xhr.send(data)
}
</script>