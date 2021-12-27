<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width" />
    <style>
        p {
            font-family: Trebuchet MS;
            font-style: normal;
            font-size: 14px;
            line-height: 20px;
            text-align: center;
            color: #515978;
        }
        h4 {
            color: #0d2366;
            font-family: Trebuchet MS;
            font-style: normal;
            font-weight: bold;
            font-size: 25px;
            line-height: 25px;
            margin-bottom: 10px;
        }
        .link {
            text-decoration: none;
            color: #528ff0;
        }
        .logo-container {
            background-image: linear-gradient(
                to bottom,
                #1b3fa6 0%,
                #1b3fa6 200px,
                #f8f9f9 200px,
                #f8f9f9 90%
            );
            height: 100%;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo-image {
            margin-top: 30px;
            height: 30px;
        }
        .auto-margin {
            margin: auto;
        }
        .main-container {
            max-width: 588px;
        }
        .main-body {
            background-color: #ffffff;
            margin-bottom: 20px;
            padding: 20px;
            max-width: 550px;
            text-align: center;
            margin-left: auto;
            margin-right: auto;
        }
        .warning-image {
            margin-top: 20px;
            height: 40px;
        }
        .divider-green {
            background-color: #2dd589;
            width: 30px;
            height: 5px;
            margin: auto;
        }
        .button-container {
            text-align: center;
            max-width: 250px;
            margin: auto;
            padding: 10px 0px 10px 0px;
        }
        .button-container a {
            color: white;
            text-decoration: unset;
        }
        .button-container div {
            padding: 15px 0px 15px 0px;
            background: #528ff0;
            border-radius: 3px;
            margin: 10px 0px;
            color: white;
        }
        .footer {
            background-color: #ffffff;
            margin-bottom: 6px;
            padding: 20px;
            max-width: 550px;
            text-align: center;
            margin-left: auto;
            margin-right: auto;
        }
        .bottom-image {
            text-align: center;
            margin-top: 20px;
            margin-bottom: 5px;
        }
        .bottom-image img {
            height: 32px;
        }
        .text-left {
            text-align: left;
        }
    </style>
</head>
<body style="font-family: Trebuchet MS">
<div class="logo-container">
    <!-- Razorpay logo -->
    <div class="logo">
        @if ($custom_branding === false)
            <img
                class="logo-image"
                src="https://cdn.razorpay.com/logo_invert.png"
            />
        @endif
    </div>
    <div class="main-container auto-margin">
        <div class="main-body">
            <div>
                <img
                    class="warning-image"
                    src="https://cdn.razorpay.com/static/assets/email/attention.png"
                />
                <h4>
                    Settlements on Temporary hold
                </h4>
                <p>
                    Your settlements are currently not being processed
                </p>
            </div>
            <div class="divider-green"></div>
            <div>
                <p>
                    Settlements to your bank account account ending with {{$settlement['ba_number']}}
                    for your Razorpay Id: {{$merchant['id']}} failed with the error: {{$settlement['failure_reason']}}.
                </p>
                <p>
                    <strong>We will be unable to process further settlements until this is resolved.</strong>
                </p>
            </div>
            <div>
                <p>
                    <strong>What can I do to fix this?</strong>
                    <br />
                    Please verify your bank account details with us
                    <a class="link" href="{{$merchant['profile_link']}}" target="_blank"><strong>here</strong></a>,
                    update them as needed or provide an alternative account, if needed.
                </p>
            </div>
            <div>
                <p>
                    Once you update the bank details, pending failed settlements will be retried automatically within one working day.
                    We urge you to act promptly to ensure that your business operations are not hindered.
                </p>
            </div>
            <div class="button-container">
                <a
                    href="{{$merchant['bank_account_update_link']}}"
                    target="_blank"
                >
                    <div>
                        Update Bank Account Details
                    </div>
                </a>
            </div>
        </div>
        <div class="footer">
            <p>
                If you have any issue with the service from {{$org_name}}, please raise
                your request
                <a class="link" href="https://dashboard.razorpay.com/#/app/dashboard#request"><strong>here</strong></a>
            </p>
        </div>

        @if ($custom_branding)
            <div class="bottom-image">
                <img src="{{ $email_logo }}"/>
            </div>
        @endif
    </div>
</div>
</body>
</html>
