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
                    Unauthorized transaction Alert
                </h4>
                <h5 style="margin-top: 0; color: #0d2366;">
                    {{$merchant_name}} {{$mid}} | {{$date_time_stamp}}
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
                <p>Hi Team,</p>
                <div style="font-size: 14px; color: #7b8199">
                    <p>We have received an Unauthorized Transaction alert on the below-captioned payment(s).</p>
                    <table>
                        <tr>
                            <th colspan="5">
                                <p style="font-size: 14px; margin: 0">Fraud Notification(s) received against payment(s)</p>
                                <p style="font-size: 11px; font-weight: light; margin: 0;">Please respond by the dates mentioned
                                <p>
                            </th>
                        </tr>
                        <tr>
                            <th>Payment ID</th>
                            <th>Transaction Date</th>
                            <th>Amount</th>
                            <th>Source of Notification</th>
                            <th>Respond By</th>
                        </tr>
                        <tr>
                            <td>{{$payment_id}}</td>
                            <td>{{$date_time_stamp}}</td>
                            <td>{{$amount}}</td>
                            <td> CyberCell</td>
                            <td>xxxx</td>
                        </tr>
                    </table>
                    <p>We request you to kindly stop the above mentioned transactions and issue a refund for these.</p>
                    <p>Also, kindly share the below details for further investigation/action.</p>
                    <ol>
                        <li>Nature of Transaction. (Mobile recharge, product purchase, etc.)</li>
                        <li>Details of the person who did the transaction. (Name, Contact no., address, e-mail, etc. Please mention how these details were gathered)</li>
                        <li>In case of mobile recharge please furnish beneficiary mobile no.</li>
                        <li>In case of a product purchase, please furnish the invoice copy, Beneficiary Name, Contact no., address, e-mail, etc.</li>
                        <li>IP address of the transaction with the time slot.</li>
                        <li>In case of mobile recharge please direct the service provider to revert back the un-utilized disputed topped up amounts to the card holder's account</li>
                        <li>In case of product purchase, please take necessary action to stop these fraudulent disputed transactions and revert back the disputed transactions amounts to the card holder's account.</li>
                        <li>Customer KYC</li>
                    </ol>
                    <p>We request you to revert to us within the above mentioned timelines with the details.</p>
                </div>
                <p>Regards, Team Razorpay</p>
            </div>
        </div>
    </div>
</div>
</body>
</html>
