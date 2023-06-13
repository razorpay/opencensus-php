<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width" />
  </head>
  <body style="margin: 0; padding: 0">
    <div
      style="
        background-color: #eaebed;
        font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
        font-size: 100%;
        line-height: 1.3em;
        margin: 0;
        padding: 0;
      "
    >
      <div
        style="
          background-image: linear-gradient(
            to bottom,
            #1b3fa6 0%,
            #1b3fa6 200px,
            #f8f9f9 200px,
            #f8f9f9 90%
          );
        "
      >
        <div
          style="
            padding: 20px;
            max-width: 600px;
            margin: 0 auto;
            text-align: center;
          "
        >
          <img
            style="height: 30px"
            src="https://cdn.razorpay.com/logo_invert.png"
          />
        </div>

        <div
          style="
            background-color: #ffffff;
            padding: 20px 20px 0px 20px;
            max-width: 600px;
            margin: 0 auto;
            text-align: center;
            color: #0d2366;
            font-size: 24px;
          "
        >
          <img
            style="height: 40px"
            src="https://cdn.razorpay.com/static/assets/email/notification.png"
          />
          <p style="margin: 0; margin-top: 15px">Razorpay Acknowledgement to LEA</p>
        </div>
      </div>
      <div
        style="
          background-color: #ffffff;
          padding: 10px 20px 20px 20px;
          max-width: 600px;
          margin: 0 auto;
          text-align: center;
          color: #0d2366;
          font-size: 14px;
        "
      >
        {{$currentDateTime}}
      </div>

      <div
        style="
          background-color: #ffffff;
          padding: 20px;
          max-width: 600px;
          margin: 0 auto;
          margin-top: 10px;
          border-top: 2px solid #528ff0;
          color: #7b8199;
          font-size: 13px;
        "
      >
        <p style="font-size: 15px; margin: 5px 0">Respected Sir/Madam,</p>
        <p style="margin: 0">Thank you for reaching out to Razorpay.</p>
        <p style="margin: 10px 0">
          This is an acknowledgement of your request for information on the
          following transactions:
        </p>

        <div style="overflow: scroll; margin: 15px 0">
          <table
            style="
              border-collapse: collapse;
              width: 100%;
              padding: 5px;
              border: 1px solid #cccccc;
            "
          >
            <tr style="padding: 5px; border: 1px solid #cccccc">
              <th style="padding: 5px; border: 1px solid #cccccc">
                Payment Method
              </th>
                <th style="padding: 5px; border: 1px solid #cccccc">Card Last 4 digits/VPA</th>
              <th style="padding: 5px; border: 1px solid #cccccc">
                Reference Number/Auth Code
              </th>
              <th style="padding: 5px; border: 1px solid #cccccc">
                Amount (INR)
              </th>
              <th style="padding: 5px; border: 1px solid #cccccc">Date Range</th>
            </tr>
            @foreach($payment_requests as $paymentRequest)
              <tr style="padding: 5px; border: 1px solid #cccccc">
                @if(empty($paymentRequest['method']) === false)
                  <td style="padding: 5px; border: 1px solid #cccccc">
                    {{$paymentRequest['method']}}
                  </td>
                @else
                  <td style="padding: 5px; border: 1px solid #cccccc"></td>
                @endif

                @if(empty($paymentRequest['vpa']) === false)
                    <td style="padding: 5px; border: 1px solid #cccccc">
                        {{$paymentRequest['vpa']}}
                    </td>
                @elseif(empty($paymentRequest['last4']) === false)
                    <td style="padding: 5px; border: 1px solid #cccccc">
                        {{$paymentRequest['last4']}}
                    </td>
                @else
                    <td style="padding: 5px; border: 1px solid #cccccc"></td>
                @endif

                @if(empty($paymentRequest['reference16']) === false)
                  <td style="padding: 5px; border: 1px solid #cccccc">
                    {{$paymentRequest['reference16']}}
                  </td>
                @elseif(empty($paymentRequest['reference1']) === false)
                  <td style="padding: 5px; border: 1px solid #cccccc">
                    {{$paymentRequest['reference1']}}
                  </td>
                @elseif(empty($paymentRequest['reference2']) === false)
                    <td style="padding: 5px; border: 1px solid #cccccc">
                        {{$paymentRequest['reference2']}}
                    </td>
                @else
                  <td style="padding: 5px; border: 1px solid #cccccc"></td>
                @endif

                @if(empty($paymentRequest['base_amount']) === false)
                  <td style="padding: 5px; border: 1px solid #cccccc">
                      ₹ {{number_format((float)$paymentRequest['base_amount']/100, 2,'.', '')}}
                  </td>
                @else
                  <td style="padding: 5px; border: 1px solid #cccccc"></td>
                @endif

                @if(empty($paymentRequest['from']) === false and empty($paymentRequest['to']) === false)
                  <td style="padding: 5px; border: 1px solid #cccccc">
                    {{date("Y-m-d", $paymentRequest['from']) }} to
                    {{date("Y-m-d", $paymentRequest['to']) }}
                  </td>
                @else
                  <td style="padding: 5px; border: 1px solid #cccccc"></td>
                @endif
              </tr>
            @endforeach
          </table>
        </div>

        <p style="margin: 0">
          We will get back to you with the requested information within 24
          hours.
        </p>
        <p style="margin: 10px 0">
          We are available on call. You may reach out to us on +91 84476 40209.
          You may use the extension mentioned in the signature to connect with
          the agent handling the case.
        </p>
        <p style="margin: 0">
          हम कॉल्स पर उपलब्ध हैं। आप हमसे +91 8447640209 पर संपर्क कर सकते हैं।
          आप मामले को संभालने वाले एजेंट से जुड़ने के लिए निम्नलिखित एक्सटेंशन
          का उपयोग कर सकते हैं।
        </p>
        <p style="font-size: 15px; margin: 15px 0 0 0">Best</p>
      </div>

      <div
        style="
          background-color: #eaebed;
          padding: 20px;
          max-width: 600px;
          margin: 10px auto;
          color: #7b8199;
          font-size: 13px;
          text-align: center;
        "
      >
        <img style="height: 20px" src="https://cdn.razorpay.com/logo.png" />
        <p style="margin: 0">The Future of Payments is Here</p>
        <p style="margin: 0">Risk Management Team (Law Enforcement Liaison)</p>
        <p style="margin: 0">Contact : +91 8447640209</p>
      </div>
    </div>
  </body>
</html>
