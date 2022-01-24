@include('partials/header')
  <link rel="stylesheet" href="/css/admin-auth.css">
</head>

<body>
  <form class="admin-auth container" method="post" action="/admin/signin" onsubmit="return false">
    <div class="auth-heading">Admin Login</div>
    <img alt="Logo" src="{{$org['login_logo_url']}}">
    <input type="text" name="username" placeholder="Username" required autofocus>
    <input type="password" name="password" placeholder="Password" required>
    <input type="submit" value="Login">
    <div id="errorText"></div>
  </form>
</body>

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

  var xhr;
  let errorText = document.querySelector('#errorText');

  document.forms[0].onsubmit = function(e) {
    e.preventDefault();
    if (xhr) {
      return;
    }
    xhr = new XMLHttpRequest()
    var formData = 'username=' + encodeURIComponent(document.querySelector('input').value) +
      '&password=' + encodeURIComponent(document.querySelector('input[type=password]').value)
    var submitBtn = document.querySelector('input[type=submit]');
    submitBtn.disabled = true

    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4) {
        let data = xhr.responseText;
        xhr = null;
        submitBtn.disabled = false
        data = JSON.parse(data);

        if (data.success) {
          return location.reload();
        }
        if(data.errors && data.errors.length) {
          let firstError = data.errors[0]
          if (firstError.hasOwnProperty("internal_error_code")) {
            handleErrorsWithInternalCode(firstError);
            return;
          }
          errorText.innerHTML = data.errors.join('\n');
          errorText.style.display = 'block';
        }
      }
    }

    xhr.open(this.method, this.action)
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8')
    xhr.setRequestHeader('X-XSRF-TOKEN', decodeURIComponent(readCookie('XSRF-TOKEN')))
    xhr.send(formData)
  }

  function handleErrorsWithInternalCode(error) {
    switch(error.internal_error_code) {
      case "BAD_REQUEST_ADMIN_2FA_LOGIN_OTP_REQUIRED":
        window.history.pushState({}, '', '/admin/enter-2fa')
        return location.reload();
      case "BAD_REQUEST_LOCKED_ADMIN_LOGIN":
        window.history.pushState({}, '', '/admin/account_block')
        return location.reload();
    }
  };
</script>
