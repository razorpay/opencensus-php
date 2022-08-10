<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width" />
    <style>
        @media (max-width: 480px) {
            .mail-content {
                margin-left: 12px !important;
                margin-right: 12px !important;
            }
        }

        @media (min-width: 1024px) {
            .mail-content {
                margin-left: 24px !important;
                margin-right: 24px !important;
            }
        }
    </style>
</head>

<body>
<table style="margin:0 auto">
    <tr>
        <td
            style="background-color: #f8f9f9; font-family: Trebuchet MS;max-width:500px"
        >
            <table style="width: 100%;">
                <tr>
                    <td
                        style="background-color: #082654; border-radius: 2px 2px 0px 0px; padding-top: 20px; height: 120px; text-align: center; vertical-align: top;"
                    >
                        <!-- Razorpay logo -->
                        <img
                            style="height: 30px;"
                            src="https://cdn.razorpay.com/logo_invert.png"
                        />
                    </td>
                </tr>
            </table>
            <table
                class="mail-content"
                style="margin: -75px 18px 24px; border-collapse: collapse;"
            >
                <tr>
                    <td style="background-color: #ffffff; padding: 24px;">
                        <table style="border-collapse: collapse;">
                            <tr>
                                <td style="margin-bottom: 8px;">
                                    <h4
                                        style="color: #0d2366; font-weight: 700; font-size: 18px; text-align: center; margin-bottom: 16px; margin-top: 0; line-height: 27px;"
                                    >
                                        Content for your website/app <br />
                                        <span>‘{{{$section_name}}}’</span> page
                                    </h4>
                                </td>
                            </tr>
                            <tr>
                                <td>
                      <span
                          style="display: block; height: 4px; width: 24px; background-color: #2dd589; margin: 0 auto;"
                      >
                      </span>
                                </td>
                            </tr>

                            <tr>
                                <td
                                    style="font-family: 'Trebuchet MS'; font-weight: 400; font-size: 14px; line-height: 20px; color: #23496d; background-color: #ffffff;"
                                >
                                    <p>
                                        Hi {{{$merchant['name']}}},
                                        <br />
                                        <br />
                                        Please find attached the content created for your
                                        ‘{{{$section_name}}}’ page using Razorpay.
                                        <br />
                                        <br />
                                        As the next step, make sure to update the content on
                                        your website/app on priority before {{{$date}}}.
                                        Settlements for your business will be put on-hold if the
                                        content is not updated before the given date.
                                        <br />
                                    </p>

                                    <p style="margin-bottom: 32px;">
                                        Thank you,
                                        <br />
                                        Team Razorpay
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <p
                class="mail-content"
                style="font-family: 'Trebuchet MS'; font-style: normal; font-weight: 700; font-size: 12px; line-height: 18px; text-align: center; color: #7b8199;padding: 0 18px; text-align: left;"
            >
                Disclaimer: This email and any files transmitted with it are
                confidential and intended solely for the use of the individual or
                entity to whom they are addressed. You agree to use / publish the
                attached content at your sole discretion and risk. You understand
                that the provision of these terms / content by Razorpay is not a
                substitute for independent legal advice, and you will use your
                independent discretion to determine the suitability of these for
                your business purposes. You absolve Razorpay of all liability in
                this regard. Razorpay expressly disclaims all liability in respect
                of any actions taken or not taken based on any or all of the content
                made available. Razorpay does not necessarily endorse and is not
                responsible for any third-party content that may be accessed through
                this information.
            </p>
        </td>
    </tr>
</table>
</body>
</html>
