@include('partials/header')
</head>

<body>
<form class="container" method="post" action="/admin/2fa/otp-verify" onsubmit="return false">
    <div class="auth-heading">2-Step Verification</div>
    <img alt="Logo" src="{{$org['login_logo_url']}}">
    <div class="auth-text mb-5">
        A 6-digit OTP has been sent to your email. OTP will expire in 5 mins.
    </div>
    <input type="text" name="otp" placeholder="Six digit OTP" id="otp" maxlength="6" required />
    <div class="auth-text details-label mt-5">Didn't receive an Email?
        <span id="resendBtn" class="highlight-clickable-text" onclick="resentOtp()">Resend</span>
    </div>
    <input type="submit" value="Verify">
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
    let resendBtn = document.querySelector('#resendBtn');
    document.forms[0].onsubmit = function(e) {
        e.preventDefault();
        if (xhr) {
            return;
        }
        xhr = new XMLHttpRequest()
        var formData = 'otp=' + encodeURIComponent(document.querySelector('input').value)
        var submitBtn = document.querySelector('input[type=submit]');
        submitBtn.disabled = true
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                let data = xhr.responseText;
                xhr = null;
                submitBtn.disabled = false
                data = JSON.parse(data);
                localStorage.setItem("enter-2fa", JSON.stringify(data))
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
            case "BAD_REQUEST_LOCKED_ADMIN_LOGIN":
                window.history.pushState({}, '', '/admin/account_block')
                return location.reload()
        }
    }
    function resentOtp() {
        if (xhr) {
            return;
        }
        xhr = new XMLHttpRequest()
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                let data = xhr.responseText;
                xhr = null;
                resendBtn.innerHTML = 'Sending...'
                data = JSON.parse(data);
                localStorage.setItem("resend-otp", JSON.stringify(data))

                if (data.success) {
                    return location.reload();
                }
                errorText.innerHTML = data.errors.join('\n');
                errorText.style.display = 'block';
            }
        }
        xhr.open('POST', '/admin/2fa/otp-resend')
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8')
        xhr.setRequestHeader('X-XSRF-TOKEN', decodeURIComponent(readCookie('XSRF-TOKEN')))
        xhr.send()
    };
</script>
