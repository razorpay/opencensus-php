<!DOCTYPE html>
<html
  xmlns="http://www.w3.org/1999/xhtml"
  xmlns:v="urn:schemas-microsoft-com:vml"
  xmlns:o="urn:schemas-microsoft-com:office:office"
>
  <head>
    <title>{{ $merchant['billing_label'] }}</title>
    <!--[if !mso]><!-- -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <!--<![endif]-->
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style type="text/css">
      #outlook a {
        padding: 0;
      }
      .ReadMsgBody {
        width: 100%;
      }
      .ExternalClass {
        width: 100%;
      }
      .ExternalClass * {
        line-height: 100%;
      }
      body {
        margin: 0;
        padding: 0;
        -webkit-text-size-adjust: 100%;
        -ms-text-size-adjust: 100%;
      }
      table,
      td {
        border-collapse: collapse;
        mso-table-lspace: 0pt;
        mso-table-rspace: 0pt;
      }
      img {
        border: 0;
        height: auto;
        line-height: 100%;
        outline: none;
        text-decoration: none;
        -ms-interpolation-mode: bicubic;
      }
      p {
        display: block;
        margin: 13px 0;
      }
    </style>
    <!--[if !mso]><!-->
    <style type="text/css">
      @media only screen and (max-width: 480px) {
        @-ms-viewport {
          width: 320px;
        }
        @viewport {
          width: 320px;
        }
      }
    </style>
    <!--<![endif]-->
    <!--[if mso]>
      <xml>
        <o:OfficeDocumentSettings>
          <o:AllowPNG />
          <o:PixelsPerInch>96</o:PixelsPerInch>
        </o:OfficeDocumentSettings>
      </xml>
    <![endif]-->
    <!--[if lte mso 11]>
      <style type="text/css">
        .outlook-group-fix {
          width: 100% !important;
        }
      </style>
    <![endif]-->

    <style type="text/css">
      @media only screen and (min-width: 480px) {
        .mj-column-per-100 {
          width: 100% !important;
          max-width: 100%;
        }
      }
    </style>

    <style type="text/css"></style>
  </head>
  <body style="background-color: #f0f0f0">
    <div style="background-color: #f0f0f0">
      <!--[if mso | IE]>
    <table
            align="center" border="0" cellpadding="0" cellspacing="0" class="max-width-override-outlook" style="width:600px;" width="600"
    >
        <tr>
            <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
    <![endif]-->

      <div
        class="max-width-override"
        style="background: {{ $merchant['brand_color'] }}; background-color: {{ $merchant['brand_color'] }}; Margin: 0px auto; max-width: unset;"
      >
        <table
          align="center"
          border="0"
          cellpadding="0"
          cellspacing="0"
          role="presentation"
          style="background:{{ $merchant['brand_color'] }};background-color:{{ $merchant['brand_color'] }};width:100%;"
        >
          <tbody>
            <tr>
              <td
                style="
                  direction: ltr;
                  font-size: 0px;
                  padding: 0px;
                  text-align: center;
                  vertical-align: top;
                "
              >
                <!--[if mso | IE]>
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">

                        <tr>

                            <td
                                    class="" style="vertical-align:top;width:600px;"
                            >
                    <![endif]-->

                <div
                  class="mj-column-per-100 outlook-group-fix"
                  style="
                    font-size: 13px;
                    text-align: left;
                    direction: ltr;
                    display: inline-block;
                    vertical-align: top;
                    width: 100%;
                  "
                >
                  <table
                    border="0"
                    cellpadding="0"
                    cellspacing="0"
                    role="presentation"
                    width="100%"
                  >
                    <tbody>
                      <tr>
                        <td style="vertical-align: top; padding: 0px">
                          <table
                            border="0"
                            cellpadding="0"
                            cellspacing="0"
                            role="presentation"
                            style=""
                            width="100%"
                          >
                            <tr>
                              <td
                                align="left"
                                style="
                                  font-size: 0px;
                                  padding: 0px;
                                  word-break: break-word;
                                "
                              >
                                <div
                                  style="
                                    font-family: Trebuchet MS;
                                    font-size: 13px;
                                    line-height: 1;
                                    text-align: left;
                                    color: #000000;
                                  "
                                >
                                  <div
                                    class="header"
                                    style="
                                      box-sizing: border-box;
                                      padding-top: 16px;
                                      max-width: 100%;
                                    "
                                  >
                                    <div
                                      class="content branding merchant"
                                      style="
                                        width: 85%;
                                        width: calc(46000% - 211600px);
                                        max-width: 460px;
                                        min-width: 308px;
                                        margin-left: auto;
                                        margin-right: auto;
                                        box-sizing: border-box;
                                        padding-left: 16px;
                                        padding-right: 16px;
                                        padding-bottom: 16px;
                                      "
                                    >
                                      <div
                                        class="branding-content"
                                        style="
                                          text-align: center;
                                          width: fit-content;
                                          margin: 0 auto;
                                          font-size: 16px;
                                          line-height: 1.5;
                                          color: #0d2366;
                                        "
                                      >
                                        <div
                                          class="content-element logo"
                                          style="
                                            display: inline-block;
                                            vertical-align: middle;
                                            background-color: #ffffff;
                                            box-sizing: border-box;
                                            line-height: 0;
                                          "
                                        >
                                          @isset($merchant['branding-logo'])
                                          <img
                                            src="{{ $merchant['branding-logo'] }}"
                                            style="
                                              height: 38px;
                                              width: 38px;
                                              margin: 5px;
                                            "
                                            width="38"
                                            height="38"
                                          />
                                          @endisset
                                        </div>
                                        <div
                                          class="content-element"
                                          style="display: inline-block; vertical-align: middle; margin-left: 10px; color: {{ $merchant['brand_color'] }};"
                                        >
                                          {{ $merchant['billing_label'] }}
                                        </div>
                                      </div>
                                    </div>
                                    <div
                                      class="content title"
                                      style="
                                        width: 85%;
                                        width: calc(46000% - 211600px);
                                        max-width: 460px;
                                        min-width: 308px;
                                        margin-left: auto;
                                        margin-right: auto;
                                        box-sizing: border-box;
                                        padding-left: 16px;
                                        padding-right: 16px;
                                        background-color: #ffffff;
                                        padding-top: 24px;
                                        padding-bottom: 46px;
                                      "
                                    >
                                      <div
                                        class="title-content"
                                        style="
                                          text-align: center;
                                          width: fit-content;
                                          margin: 0 auto;
                                        "
                                      >
                                        <div
                                          class="font-color-otp font-size-large"
                                          style="
                                            font-weight: bold;
                                            font-size: 16px;
                                            line-height: 24px;
                                            text-align: center;
                                            color: #7b8199;
                                          "
                                        >
                                          {{ $merchant['name'] }} has
                                          invited you to send invoices via
                                          forwarding emails
                                          <img
                                            class="image small"
                                            src="https://cdn.razorpay.com/x/mailbox-icon.png"
                                            style="
                                              background: transparent;
                                              height: 12px;
                                              width: 12px;
                                            "
                                          />
                                        </div>
                                        <div
                                          class="center-align"
                                          style="text-align: center"
                                        >
                                          <div
                                            class="puck para"
                                            style="
                                              margin: 0;
                                              padding-top: 8px;
                                              padding-bottom: 16px;
                                            "
                                          >
                                            <div
                                              class="bar"
                                              style="
                                                margin: 0px auto;
                                                width: 24px;
                                                height: 4px;
                                                background-color: #08ca73;
                                              "
                                            ></div>
                                          </div>
                                        </div>
                                      </div>
                                      <div
                                        class="left"
                                        style="
                                          text-align: left;
                                          font-size: 14px;
                                          line-height: 20px;
                                          color: #7b8199;
                                        "
                                      >
                                        Hi {{ $vendor_name }},<br />
                                        Send invoices to {{ $merchant['billing_label'] }} by
                                        sending to the following email:
                                      </div>
                                      <div
                                        class="para-dark center"
                                        style="
                                          background: rgba(98, 170, 255, 0.05);
                                          padding-top: 8px;
                                          padding-bottom: 8px;
                                          padding-left: 16px;
                                          padding-right: 16px;
                                          border-radius: 4px;
                                          border: 1px solid
                                            rgba(98, 170, 255, 0.24);
                                          border-radius: 4px;
                                          text-align: center;
                                          margin-top: 16px;
                                          margin-bottom: 16px;
                                        "
                                      >
                                        <div
                                          class="information-row"
                                          style="
                                            width: 100%;
                                            box-sizing: border-box;
                                            margin-bottom: 10px;
                                          "
                                        >
                                          <div
                                            class="label half-width"
                                            style="
                                              color: #bdbfc9;
                                              display: inline-block;
                                              width: 5%;
                                              vertical-align: middle;
                                            "
                                          >
                                            <img
                                              class="image small"
                                              src="https://cdn.razorpay.com/x/email-icon-dark.png"
                                              style="
                                                background: transparent;
                                                height: 10px;
                                                width: 12px;
                                                vertical-align: center;
                                              "
                                              height="10"
                                              width="12"
                                            />
                                          </div>
                                          <div
                                            class="
                                              value
                                              left-align
                                              merchant-invoice-forwarding-email
                                            "
                                            style="
                                              font-size: 14px;
                                              line-height: 20px;
                                              color: #5a99e8;
                                              display: inline-block;
                                              width: 90%;
                                              text-align: left;
                                            "
                                          >
                                            {{$email_address}}
                                          </div>
                                        </div>
                                        <div
                                          class="information-row"
                                          style="
                                            width: 100%;
                                            box-sizing: border-box;
                                            margin-top: 12px;
                                          "
                                        >
                                          <div
                                            class="btn-container"
                                            style="
                                              color: #bdbfc9;
                                              display: inline-block;
                                            "
                                          >
                                            <a
                                              class="link btn primary font-bold"
                                              href="mailto:{{$email_address}}"
                                              target="_blank"
                                              style="
                                                text-decoration: none;
                                                font-size: 12px;
                                                line-height: 18px;
                                                padding: 5px 12px;
                                                letter-spacing: 1px;
                                                border-radius: 2px;
                                                overflow: hidden;
                                                min-width: 145px;
                                                display: inline-block;
                                                font-family: Trebuchet MS;
                                                text-align: center;
                                                color: #fff;
                                                background-color: #6297ff;
                                                border-color: #6297ff;
                                              "
                                              >+ Add to Contacts</a
                                            >
                                          </div>
                                        </div>
                                      </div>
                                      <div
                                        class="left"
                                        style="
                                          text-align: left;
                                          font-size: 12px;
                                          line-height: 18px;
                                          color: #7b8199;
                                        "
                                      >
                                        <span style="font-weight: bold"
                                          >Keep in mind:</span
                                        >
                                        You can send multiple attachments (upto
                                        20 attachments) in a single email in the
                                        format .pdf , .jpeg and .jpg , lesser
                                        than 25MB.
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              </td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!--[if mso | IE]>
                    </td>

                    </tr>

                    </table>
                    <![endif]-->
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!--[if mso | IE]>
    </td>
    </tr>
    </table>

    <table
            align="center" border="0" cellpadding="0" cellspacing="0" class="max-width-override-outlook" style="width:600px;" width="600"
    >
        <tr>
            <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
    <![endif]-->

      <!--[if mso | IE]>
    </td>
    </tr>
    </table>

    <table
            align="center" border="0" cellpadding="0" cellspacing="0" class="max-width-override-outlook" style="width:600px;" width="600"
    >
        <tr>
            <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
    <![endif]-->

      <!--[if mso | IE]>
    </td>
    </tr>
    </table>

    <table
            align="center" border="0" cellpadding="0" cellspacing="0" class="max-width-override-outlook" style="width:600px;" width="600"
    >
        <tr>
            <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
    <![endif]-->

      <div
        class="max-width-override"
        style="margin: 0px auto; max-width: unset"
      >
        <table
          align="center"
          border="0"
          cellpadding="0"
          cellspacing="0"
          role="presentation"
          style="width: 100%"
        >
          <tbody>
            <tr>
              <td
                style="
                  direction: ltr;
                  font-size: 0px;
                  padding: 0px;
                  text-align: center;
                  vertical-align: top;
                "
              >
                <!--[if mso | IE]>
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">

                        <tr>

                            <td
                                    class="" style="vertical-align:top;width:600px;"
                            >
                    <![endif]-->

                <div
                  class="mj-column-per-100 outlook-group-fix"
                  style="
                    font-size: 13px;
                    text-align: left;
                    direction: ltr;
                    display: inline-block;
                    vertical-align: top;
                    width: 100%;
                  "
                >
                  <table
                    border="0"
                    cellpadding="0"
                    cellspacing="0"
                    role="presentation"
                    width="100%"
                  >
                    <tbody>
                      <tr>
                        <td style="vertical-align: top; padding: 0px">
                          <table
                            border="0"
                            cellpadding="0"
                            cellspacing="0"
                            role="presentation"
                            style=""
                            width="100%"
                          >
                            <tr>
                              <td
                                align="left"
                                style="
                                  font-size: 0px;
                                  padding: 0px;
                                  word-break: break-word;
                                "
                              >
                                <div
                                  style="
                                    font-family: Trebuchet MS;
                                    font-size: 13px;
                                    line-height: 1;
                                    text-align: left;
                                    color: #000000;
                                  "
                                >
                                  <div
                                    class="card footer-card"
                                    style="
                                      width: 85%;
                                      width: calc(46000% - 211600px);
                                      max-width: 460px;
                                      min-width: 308px;
                                      margin-left: auto;
                                      margin-right: auto;
                                      box-sizing: border-box;
                                      padding-left: 16px;
                                      padding-right: 16px;
                                      background-color: #ffffff;
                                      padding: 0px;
                                      margin-top: 8px;
                                    "
                                  >
                                    <div
                                      class="information-row"
                                      style="
                                        width: 100%;
                                        box-sizing: border-box;
                                        margin-bottom: 10px;
                                        padding-left: 44px;
                                        padding-right: 44px;
                                        padding-top: 16px;
                                        padding-bottom: 18px;
                                      "
                                    >
                                      <div
                                        class="label half-width"
                                        style="
                                          color: #bdbfc9;
                                          display: inline-block;
                                          width: 65%;
                                          vertical-align: top;
                                          margin-top: 12px;
                                        "
                                      >
                                        <div
                                          style="
                                            font-size: 12px;
                                            line-height: 14px;
                                            color: #7b8199;
                                            vertical-align: middle;
                                          "
                                        >
                                          Collect Vendor Invoices via Email
                                        </div>
                                      </div>
                                      <div
                                        class="value"
                                        style="
                                          display: inline-block;
                                          width: 20%;
                                          text-align: left;
                                          padding-left: 24px;
                                          border-left: 2px solid
                                            rgba(0, 0, 0, 0.1); ;
                                        "
                                      >
                                        <div
                                          style="
                                            font-weight: bold;
                                            font-size: 10px;
                                            line-height: 12px;
                                            color: #7b8199;
                                            margin-bottom: 6px;
                                            padding-left: 2px;
                                          "
                                        >
                                          Sign up on
                                        </div>
                                        <div
                                          class="content-element logo"
                                          style="height: 18px; width: 85px"
                                        >
                                          <img
                                            src="https://cdn.razorpay.com/static/assets/razorpayx/logos/rx-dark-logo.png"
                                            style="height: 100%; width: 100%"
                                          />
                                        </div>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              </td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!--[if mso | IE]>
                    </td>

                    </tr>

                    </table>
                    <![endif]-->
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!--[if mso | IE]>
    </td>
    </tr>
    </table>

    <table
            align="center" border="0" cellpadding="0" cellspacing="0" class="footer-outlook" style="width:600px;" width="600"
    >
        <tr>
            <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
    <![endif]-->

      <div
        class="footer"
        style="
          width: 100%;
          margin: 0px auto;
          max-width: 600px;
          margin-top: 8px;
          margin-bottom: 8px;
        "
      >
        <table
          align="center"
          border="0"
          cellpadding="0"
          cellspacing="0"
          role="presentation"
          style="width: 100%"
        >
          <tbody>
            <tr>
              <td
                style="
                  direction: ltr;
                  font-size: 0px;
                  padding: 0px;
                  text-align: center;
                  vertical-align: top;
                "
              >
                <!--[if mso | IE]>
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">

                        <tr>

                            <td
                                    class="" style="vertical-align:top;width:600px;"
                            >
                    <![endif]-->

                <div
                  class="mj-column-per-100 outlook-group-fix"
                  style="
                    font-size: 13px;
                    text-align: left;
                    direction: ltr;
                    display: inline-block;
                    vertical-align: top;
                    width: 100%;
                  "
                >
                  <table
                    border="0"
                    cellpadding="0"
                    cellspacing="0"
                    role="presentation"
                    width="100%"
                  >
                    <tbody>
                      <tr>
                        <td style="vertical-align: top; padding: 0px">
                          <table
                            border="0"
                            cellpadding="0"
                            cellspacing="0"
                            role="presentation"
                            style=""
                            width="100%"
                          >
                            <tr>
                              <td
                                align="left"
                                style="
                                  font-size: 0px;
                                  padding: 0px;
                                  word-break: break-word;
                                "
                              >
                                <div
                                  style="
                                    font-family: Trebuchet MS;
                                    font-size: 13px;
                                    line-height: 1;
                                    text-align: left;
                                    color: #000000;
                                  "
                                >
                                  <div
                                    class="footer-text"
                                    style="
                                      font-size: 12px;
                                      line-height: 1.5;
                                      color: #7b8199;
                                      text-align: center;
                                      padding: 8px 0;
                                    "
                                  >
                                    <div
                                      class="test"
                                      style="
                                        width: 100%;
                                        box-sizing: border-box;
                                      "
                                    >
                                      @isset($merchant['support_url'])
                                      <div
                                        class="value"
                                        style="
                                          display: inline-block;
                                          width: 25%;
                                        "
                                      >
                                        {{ $merchant['support_url'] }}
                                      </div>
                                      <div
                                        class="line"
                                        style="display: inline-block; width: 2%"
                                      >
                                        |
                                      </div>
                                      @endisset
                                      @isset($merchant['support_contact'])
                                      <div
                                        class="value"
                                        style="
                                          display: inline-block;
                                          width: 25%;
                                        "
                                      >
                                        {{ $merchant['support_contact'] }}
                                      </div>
                                      <div
                                        class="line"
                                        style="display: inline-block; width: 2%"
                                      >
                                        |
                                      </div>
                                      @endisset
                                      @isset($merchant['support_email'])
                                      <div
                                        class="value"
                                        style="
                                          display: inline-block;
                                          width: 25%;
                                        "
                                      >
                                        {{ $merchant['support_email'] }}
                                      </div>
                                      @endisset
                                    </div>
                                  </div>
                                </div>
                              </td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!--[if mso | IE]>
                    </td>

                    </tr>

                    </table>
                    <![endif]-->
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!--[if mso | IE]>
    </td>
    </tr>
    </table>
    <![endif]-->
    </div>
  </body>
</html>
