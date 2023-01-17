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
            src="https://cdn.razorpay.com/static/assets/email/attention.png"
          />
          <p style="margin: 0; margin-top: 15px">
            Unauthorized Transaction Alert
          </p>
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
        {{$merchant_name}} {{$merchant_id}} | {{$current_date_time}}
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
        <p style="font-size: 15px; margin: 5px 0">Hi Team,</p>
        <p style="margin: 0">
          We have received an Unauthorized Transaction alert on the
          below-captioned payment(s). We request you to kindly stop the
            services/delivery for the reported transactions and initiate a refund incase of an amount not utilized.
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
              <th style="padding: 5px; border: 1px solid #cccccc" colspan="5">
                <p style="font-size: 14px; margin: 0">
                  Fraud Notification(s) received against payment(s)
                </p>
                <p style="font-size: 11px">
                  Please respond by the dates mentioned
                </p>
              </th>
            </tr>
            <tr style="padding: 5px; border: 1px solid #cccccc">
              <th style="padding: 5px; border: 1px solid #cccccc">
                Payment ID
              </th>
              <th style="padding: 5px; border: 1px solid #cccccc">
                Transaction Date
              </th>
              <th style="padding: 5px; border: 1px solid #cccccc">Amount</th>
              <th style="padding: 5px; border: 1px solid #cccccc">
                Source of Notification
              </th>
              <th style="padding: 5px; border: 1px solid #cccccc">
                Respond By
              </th>
            </tr>
              @foreach($data as $query)
                <tr style="padding: 5px; border: 1px solid #cccccc">
                  <td style="padding: 5px; border: 1px solid #cccccc">
                      {{$query['details']['payment']['id']}}
                  </td>
                  <td style="padding: 5px; border: 1px solid #cccccc">
                    {{date("Y-m-d h:i:sa", $query['details']['payment']['created_at'] + $ist_diff)}}
                  </td>
                  <td style="padding: 5px; border: 1px solid #cccccc">
                      ₹ {{number_format((float)$query['details']['payment']['base_amount']/100, 2,'.', '')}}
                  </td>
                  <td style="padding: 5px; border: 1px solid #cccccc">CyberCell</td>
                  <td style="padding: 5px; border: 1px solid #cccccc">
                    {{$respond_by}}
                  </td>
                </tr>
              @endforeach
          </table>
        </div>

        <p style="margin: 0"></p>
        <p>
          Also, kindly share the below details for further investigation/action.
        </p>
        <ol>
          <li>
            Nature of Transaction. (Mobile recharge, product purchase, etc.)
          </li>
          <li>
            Details of the person who did the transaction. (Name, Contact no.,
            address, e-mail, etc. Please mention how these details were
            gathered)
          </li>
          <li>
            In case of mobile recharge please furnish beneficiary mobile no.
          </li>
          <li>
            In case of a product purchase, please furnish the invoice copy,
            Beneficiary Name, Contact no., address, e-mail, etc.
          </li>
          <li>IP address of the transaction with the time slot.</li>
          <li>
            In case of mobile recharge please direct the service provider to
            revert back the un-utilized disputed topped up amounts to the card
            holder's account
          </li>
          <li>
            In case of product purchase, please take necessary action to stop
            these fraudulent disputed transactions and revert back the disputed
            transactions amounts to the card holder's account.
          </li>
          <li>Customer KYC</li>
        </ol>
        <p style="margin: 0">
          We request you to revert to us within the above mentioned timelines
          with the details.
        </p>
        <p style="font-size: 15px; margin: 15px 0 0 0">Regards</p>
        <p style="font-size: 15px">Team Razorpay</p>
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
        For any further queries or clarifications, feel free to reach out to us
        by visiting
        <a
          style="margin: 0; text-decoration: none; color: #528ff0"
          href="https://razorpay.com/support"
          >here.</a
        >
      </div>
    </div>
  </body>
</html>
