<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width" />
</head>
<body style="font-family: Trebuchet MS">
<div
        style="
        background-image: linear-gradient(
          to bottom,
          #1b3fa6 0%,
          #1b3fa6 200px,
          #f8f9f9 200px,
          #f8f9f9 90%
        );
        height: 100%;
      "
>
    <!-- Razorpay logo -->
    <div style="text-align: center; margin-bottom: 30px">
        @if ($custom_branding === false)
            <img
                style="margin-top: 30px; height: 30px"
                src="https://cdn.razorpay.com/logo_invert.png"
            />
        @endif
    </div>
    <div style="max-width: 588px; margin: auto">
        <div style="max-width: 588px; margin: auto">
            <div
                    style="
              background-color: #ffffff;
              margin-bottom: 20px;
              padding: 20px;
              max-width: 550px;
              text-align: center;
              margin-left: auto;
              margin-right: auto;
            "
            >
                <div>
                    <img
                            style="margin-top: 20px; height: 40px"
                            src="https://cdn.razorpay.com/static/assets/email/attention.png"
                    />
                    <h4
                            style="
                  color: #0d2366;
                  font-family: Trebuchet MS;
                  font-style: normal;
                  font-weight: bold;
                  font-size: 25px;
                  line-height: 25px;
                "
                    >
                        Attention Required!
                    </h4>
                </div>
                <div>
                    <p
                            style="
                  font-family: Trebuchet MS;
                  font-style: normal;
                  font-size: 18px;
                  line-height: 25px;
                  text-align: center;
                  color: #515978;
                "
                    >
                        This is to bring to your notice that the settlement to your bank
                        account account ending with {{$last4}} for the Merchant id:
                        {{$merchant_id}} failed with the error : {{$remarks}}
                    </p>
                </div>
                <div
                        style="
                background-color: #2dd589;
                width: 30px;
                height: 5px;
                margin: auto;
                margin-top: 45px;
              "
                ></div>
                <div>
                    <p
                            style="
                  font-family: Trebuchet MS;
                  font-style: normal;
                  font-size: 18px;
                  line-height: 25px;
                  text-align: center;
                  color: #646d8b;
                "
                    >
                        We would request you to check if the bank account details
                        mentioned in your {{$org_name}} account are correct and also verify
                        with your bank if the account is active.
                    </p>
                </div>
                <div
                        style="
                text-align: center;
                max-width: 175px;
                margin: auto;
                padding: 10px 0px 10px 0px;
              "
                >
                    <a
                            href="{{$profile_link}}"
                            target="_blank"
                            style="color: white; text-decoration: unset"
                    >
                        <div
                                style="
                    padding: 15px 0px 15px 0px;
                    background: #528ff0;
                    border-radius: 3px;
                    margin: 10px 0px;
                    color: white;
                  "
                        >
                            Go to dashboard
                        </div>
                    </a>
                </div>
            </div>
            <div
                    style="
              background-color: #ffffff;
              margin-bottom: 6px;
              padding: 20px;
              max-width: 550px;
              text-align: center;
              margin-left: auto;
              margin-right: auto;
            "
            >
                <p
                        style="
                font-family: Trebuchet MS;
                font-style: normal;
                font-size: 18px;
                line-height: 25px;
                text-align: center;
                color: #515978;
              "
                >
                    In case of any discrepancy in the bank account details or if you
                    would like us to update the bank account details, kindly respond
                    to this email with the bank account number, IFSC code and the bank
                    account statement for the past 3 months.
                </p>
                <p
                        style="
                font-family: Trebuchet MS;
                font-style: normal;
                font-size: 18px;
                line-height: 25px;
                text-align: center;
                color: #515978;
                padding-top: 20px;
                border-top-style: solid;
                border-top-color: #ebedf2;
                border-top-width: 2px;
              "
                >
                    <strong>Note:</strong> To avoid any further settlement failures,
                    your funds will be on hold. We will release the funds once we have
                    updated the details.
                </p>
            </div>

            @if ($custom_branding)
                <div style="text-align: center; margin-top: 20px; margin-bottom: 5px">
                    <img src="{{ $email_logo }}" style="height: 32px;" />
                </div>
            @endif
        </div>
    </div>
</div>
</body>
</html>