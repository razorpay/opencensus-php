@include('partials/header')
  <link rel="stylesheet" href="/css/admin-auth.css">
</head>

<body>
  <form class="admin-auth container" method="post" action="/admin/forgot_password" onsubmit="return false">
    <div class="auth-heading">Forgot Password</div>
    <img alt="Logo" src="{{$org['login_logo_url']}}">
    <input type="email" name="email" placeholder="Enter Email" required autofocus>
    <input type="submit" value="Send Reset Link">
    <div id="errorText"></div>
    <div id="successText">
      <div>
        <svg focusable="false" aria-hidden="true" fill="rgba(255, 255, 255, 1.0)" height="20" width="20" data-testid="ds-icon" viewBox="0 0 24 24">
          <g clip-path="url(#prefix__clip0)" fill="light.900">
            <path d="M4.158 7.147a9 9 0 0110.505-2.374 1 1 0 10.814-1.826A11 11 0 1022 13v-.93a1 1 0 10-2 0V13A9 9 0 114.158 7.146z"></path>
            <path d="M22.707 4.707a1 1 0 00-1.414-1.414L11 13.586l-2.293-2.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l11-11z"></path>
          </g>
          <defs>
            <clipPath id="prefix__clip0">
              <path d="M0 0h24v24H0z"></path>
            </clipPath>
          </defs>
        </svg>
      </div>
      <p>
      We have sent a reset password link to your email.
      Didn't receive the email? Check email address again or look in your spam folder.
      </p>
      <button type="button" onclick="hideSuccessText()">
        <svg focusable="false" aria-hidden="true" fill="rgba(255, 255, 255, 1.0)" height="16" width="16" data-testid="ds-icon" viewBox="0 0 24 24">
          <path d="M18.7071 6.70711C19.0976 6.31658 19.0976 5.68342 18.7071 5.29289C18.3166 4.90237 17.6834 4.90237 17.2929 5.29289L12 10.5858L6.70711 5.29289C6.31658 4.90237 5.68342 4.90237 5.29289 5.29289C4.90237 5.68342 4.90237 6.31658 5.29289 6.70711L10.5858 12L5.29289 17.2929C4.90237 17.6834 4.90237 18.3166 5.29289 18.7071C5.68342 19.0976 6.31658 19.0976 6.70711 18.7071L12 13.4142L17.2929 18.7071C17.6834 19.0976 18.3166 19.0976 18.7071 18.7071C19.0976 18.3166 19.0976 17.6834 18.7071 17.2929L13.4142 12L18.7071 6.70711Z"></path>
        </svg>
      </button>
    </div>
  </form>
</body>

<script>
  var xhr;
  var errorText = document.querySelector('#errorText');
  var successText = document.querySelector("#successText");

  function readCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
      var c = ca[i];
      while (c.charAt(0)==' ') c = c.substring(1,c.length);
      if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
  }

  function hideSuccessText() {
    successText.style.display = 'none';
  }

  document.forms[0].onsubmit = function(e) {
    e.preventDefault();
    if (xhr) {
      return;
    }
    xhr = new XMLHttpRequest()
    var resetPassUrl = window.location.origin;
    var formData = 'email=' + encodeURIComponent(document.querySelector('input[type=email]').value) +
      '&reset_password_url=' + encodeURIComponent(resetPassUrl)
    var submitBtn = document.querySelector('input[type=submit]');
    submitBtn.disabled = true

    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4) {
        let data = xhr.responseText;
        xhr = null;
        submitBtn.disabled = false
        data = JSON.parse(data);

        if (data.success) {
          successText.style.display = "flex";
          return;
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
