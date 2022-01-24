@include('partials/header')
    <link rel="stylesheet" href="/css/admin-auth.css">
</head>
<body>
<div class="admin-auth container">
    <div class="auth-heading">Account Blocked</div>
    <img alt="Logo" src="{{$org['login_logo_url']}}">
    <div class="auth-text mb-5 px-10">
        Your account has been blocked due to too many wrong OTP attempts.
    </div>
    <div class="auth-text mb-5 px-10">
        A cooling off period of <b>15 minutes</b> exists post your account is unlocked, so please try logging in post that.
    </div>
    <div class="auth-text px-10">Please contact your admin to unblock your account.&nbsp;
        <span class="highlight-clickable-text" onclick="backToLogin()">Back to Login</span>
    </div>
</div>
</body>

<script>
    function backToLogin() {
        window.history.pushState({}, '', '/admin')
        return location.reload();
    };
</script>
