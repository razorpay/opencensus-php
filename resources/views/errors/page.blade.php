<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Lato&display=swap');

    .container {
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
    }
    body {
        font-family: 'Lato', sans-serif;
        font-size: 14px;
        line-height: 1.42857143;
        color: #58666e;
        background-color: #f0f3f4;
    }
    .error-oops {
        font-size: 120px;
    }
    .error-message {
        font-size: 30px;
        letter-spacing: .2px;
        margin: 0;
        text-align: center;
    }
    .error-request-id {
        margin-top: 10px;
        text-align: center;
    }
    .footer {
        margin-top: 230px;
    }
    .error-content {
        margin-top: 260px;
    }
    .logo {
        width: 200px;
    }
</style>
<body>
<div class="container">
    <div class="error-content">
        <div class="error-oops">
            OOPS!
        </div>
        <div class="error-message">
            {{ $http_status_code }}, {{$error_message}}.
        </div>
        <div class="error-request-id">
            Error Code: {{$request_id}}
        </div>
    </div>
    <div class="footer">
        <img class="logo" src="https://cdn.razorpay.com/logo-small.png">
    </div>
</div>
</body>
</html>
