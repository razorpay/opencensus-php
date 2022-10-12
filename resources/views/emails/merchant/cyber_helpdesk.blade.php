<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width" />
</head>
<style>
    table {
        border-collapse: collapse;
        width: 100%;
    }
    td, tr, th {
        padding: 4px;
        border: 1px solid #cccccc;
    }
</style>
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
        <img
            style="margin-top: 30px; height: 30px"
            src="https://cdn.razorpay.com/logo_invert.png"
        />
    </div>
    <div style="max-width: 588px; margin: auto">
        <!-- header -->
        <div style="max-width: 588px; margin: auto">
            <div
                style="
                  background-color: #ffffff;
                  margin-bottom: 20px;
                  padding: 15px;
                  max-width: 550px;
                  text-align: center;
                  margin-left: auto;
                  margin-right: auto;
                  "
            >
                <h4
                    style="
                     color: #0d2366;
                     font-family: Trebuchet MS;
                     font-style: normal;
                     font-weight: bold;
                     font-size: 25px;
                     line-height: 24px;
                     margin-bottom: 5px
                     "
                >
                    Razorpay Response to LEA
                </h4>
                <h5 style="margin-top: 0; color: #0d2366;">
                    {{$currentDateTime}}
                </h5>
            </div>
            <!-- body -->
            <div
                style="
                  background-color: #ffffff;
                  margin-bottom: 6px;
                  padding: 20px;
                  max-width: 550px;
                  margin-left: auto;
                  margin-right: auto;
                  "
            >
                <p>Respected Sir/Madam,</p>
                <div style="font-size: 14px; color: #7b8199">
                    <p>Thank you for reaching out to Razorpay.</p>
                    <p>This is an acknowledgement of your request for information on the following transactions:</p>
                    <table>
                        <tr>
                            <th>Payment Method</th>
                            <th>Reference Number</th>
                            <th>Amount (in Rs.)</th>
                            <th>Date</th>
                            <th>VPA</th>
                        </tr>
                        @foreach($payment_requests as $paymentRequest)
                            <tr>
                            @if(empty($paymentRequest['method']) === false)
                                <td>{{$paymentRequest['method']}}</td>
                            @else
                                <td></td>
                            @endif

                            @if(empty($paymentRequest['reference16']) === false)
                                <td>{{$paymentRequest['reference16']}}</td>
                            @elseif(empty($paymentRequest['refrence1']) === false)
                                <td>{{$paymentRequest['refrence1']}}</td>
                            @else
                                <td></td>
                            @endif

                            @if(empty($paymentRequest['base_amount']) === false)
                                <td>{{$paymentRequest['base_amount']}}</td>
                            @else
                                <td></td>
                            @endif

                            @if(empty($paymentRequest['from']) === false and empty($paymentRequest['to']) === false)
                                <td>{{date("Y-m-d H:i:s", $paymentRequest['from']) }} - {{date("Y-m-d H:i:s",
$paymentRequest['to']) }}</td>
                            @else
                                <td></td>
                            @endif

                            @if(empty($paymentRequest['vpa']) === false)
                                <td>{{$paymentRequest['vpa']}}</td>
                            @else
                                <td></td>
                            @endif

                            </tr>
                        @endforeach
                    </table>
                    <p>We will get back to you with the requested information within 24 hours.</p>
                    <p>We are available on call. You may reach out to us on +91 84476 40209. You may use the extension mentioned in the signature to connect with the agent handling the case.</p>
                    <p>हम कॉल्स पर उपलब्ध हैं। आप हमसे +91 8447640209 पर संपर्क कर सकते हैं। आप मामले को संभालने वाले एजेंट से जुड़ने के लिए निम्नलिखित एक्सटेंशन का उपयोग कर सकते हैं।
                    </p>
                </div>
            </div>
        </div>
    </div>
    <!-- footer -->
    <div style="max-width: 588px; margin: auto; border: 1px solid #CCCCCC; border-left: none; border-right: none">
        <div style="display: flex;">
            <div style="padding: 0 20px; border-right: 1px solid #CCCCCC">
                <img
                    style="margin-top: 10px; height: 20px"
                    src="https://cdn.razorpay.com/logo.png"
                />
                <h5 style="margin-top: 0; color: #0d2366;">
                    The Future of Payments is Here
                </h5>
            </div>
            <div style="padding: 0 20px; font-weight: bold; font-size: 11px; color: #0d2366;">
                <p style="margin-bottom: 0;">Risk Management Team</p>
                <p style="margin: 0;">(Law Enforcement Liaison) </p>
                <p style="margin-top: 5px">Contact : +91 8447640209</p>
            </div>
        </div>
    </div>
    <!-- footer -->
</div>
</body>
</html>
