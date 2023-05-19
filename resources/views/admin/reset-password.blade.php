@include('partials/header')
  <link rel="stylesheet" href="/css/admin-auth.css">
</head>

<body>
  <form class="admin-auth container" method="post" action="/admin/reset_password" onsubmit="return false">
    <div class="auth-heading">Reset Password</div>
    <img alt="Logo" src="{{$org['login_logo_url']}}">
    <input type="email" name="email" disabled>
    <input type="password" name="password" placeholder="Enter Password" required autofocus>
    <input type="password" name="confirm-password" placeholder="Confirm Password" required>
    <input type="submit" value="Reset Password">
    <div id="errorText"></div>
  </form>
  <div class="pass-reset-successful">
    <span>Password Reset Successful</span>
    <a href="/admin">Login</a>
  </div>
</body>

<script>
  window.addEventListener("load", setEmail);

  // Returns email from url like https://da......?email=abc@gmail.com
  // Cannot use URLSearchParams since it doesn't work for abc+1@gmail.com
  function getValueFromParams(key) {
    var queryString = window.location.search.substr(1);
    var queryParams = queryString.split('&');
    var value = null;
    for (var i = 0; i < queryParams.length; i++) {
        var pair = queryParams[i].split('=');
        if (pair[0] === key) {
            value = decodeURIComponent(pair[1]);
            break;
        }
    }
    return value;
  }

  function setEmail() {
    var email = getValueFromParams('email');
    var emailElement = document.querySelector('input[type=email]')
    emailElement.value = email;
  }

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
    xhr = new XMLHttpRequest();

    var params = new URLSearchParams(window.location.search)
    var email = getValueFromParams('email');
    var token = params.get('token');
    var pass = document.querySelector('input[name=password]').value;
    var confirmPass = document.querySelector('input[name=confirm-password]').value;

    var formData = 'email=' + encodeURIComponent(email) +
      '&password=' + encodeURIComponent(pass) +
      '&password_confirmation=' + encodeURIComponent(confirmPass) +
      '&token=' + encodeURIComponent(token)
    var submitBtn = document.querySelector('input[type=submit]');
    submitBtn.disabled = true

    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4) {
        let data = xhr.responseText;
        xhr = null;
        submitBtn.disabled = false
        data = JSON.parse(data);

        if (data.success) {
          var successDiv = document.querySelector('.pass-reset-successful');
          successDiv.style.display = 'block';
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
