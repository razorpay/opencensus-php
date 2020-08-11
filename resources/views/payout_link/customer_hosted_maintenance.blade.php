<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Payout Link</title>
    <style>
        body {
            margin: 0; padding:0;
        }
        #app {
            width: 100vw;
            height: 100vh;
            background: #e3e3e3;
        }
        #app .loading_container {
            width: 90%;
            margin: 0 auto;
            padding-top: 20vh;
            font-family: 'Helvetica', 'Arial', sans-serif
        }
        #app .loading_container h1{
            text-align: center;
            line-height: 30px;
        }
        @keyframes loader_keyframes {
            from {transform: rotate(0deg)}
            to {transform: rotate(1turn)}
        }
        .poweredBy{
            width: 36px;
            height: 2px;
            background: #3281FF;
            margin: 0 auto;
        }
        .securedFontContainer{
            font-size: 12px;
            line-height: 18px;
            color: #626262;
            display: flex;
            justify-content: center;
            margin-top:50px;
        }
    </style>
    <meta name="robots" content="noindex">
    <meta
            name="viewport"
            content="width=device-width, height=device-height, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0"
    />
</head>
<body>
<div id="app">
    <div class="loading_container">
        <h1>
            We are under maintenance. Please try again in sometime.
        </h1>
        <div class="poweredBy"></div>
        <div class="securedFontContainer">
            <img
                    src="https://cdn.razorpay.com/static/assets/razorpayx/payout-links/secured.svg"
                    style="width: 149px; margin-left: 3px;"
            />
        </div>
    </div>
</div>
</body>
</html>
