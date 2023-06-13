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
          <p style="margin: 0; margin-top: 15px">Razorpay Response to LEA</p>
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
        {{$fd_ticket_id}}_{{$current_date_time}}
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
        <p style="margin: 0">Thank You for reaching out to Razorpay.</p>
        <p style="margin: 10px 0">
          We have escalated the issue to the merchant meanwhile please find
          below the beneficiary details as requested against the transaction
          details submitted by you:
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
              <th style="padding: 5px; border: 1px solid #cccccc" colspan="10">
                <p style="font-size: 14px; margin: 0">
                  A) Transaction(s) traced against the input received from the
                  LEA
                </p>
              </th>
            </tr>
            <tr style="padding: 5px; border: 1px solid #cccccc">
              <th style="padding: 5px; border: 1px solid #cccccc">
                Date (DD/MM/YY)
              </th>
              <th style="padding: 5px; border: 1px solid #cccccc">
                Card/ method
              </th>
              <th style="padding: 5px; border: 1px solid #cccccc">
                Payment ID
              </th>
              <th style="padding: 5px; border: 1px solid #cccccc">Status</th>
              <th style="padding: 5px; border: 1px solid #cccccc">Amount (INR)</th>
              <th style="padding: 5px; border: 1px solid #cccccc">
                Buyer's Email address
              </th>
              <th style="padding: 5px; border: 1px solid #cccccc">
                Buyer's Phone #
              </th>
              <th style="padding: 5px; border: 1px solid #cccccc">
                Buyer's IP address
              </th>
              <th style="padding: 5px; border: 1px solid #cccccc">
                Merchant Website
              </th>
              <th style="padding: 5px; border: 1px solid #cccccc">
                Merchant name
              </th>
            </tr>
            @foreach($data as $query)
                <tr style="padding: 5px; border: 1px solid #cccccc">
                  <td style="padding: 5px; border: 1px solid #cccccc">
                    {{date("Y-m-d h:i:sa", $query['details']['payment']['created_at'] + $ist_diff)}}
                  </td>
                  <td style="padding: 5px; border: 1px solid #cccccc">
                    {{$query['details']['payment']['method']}}
                  </td>
                  <td style="padding: 5px; border: 1px solid #cccccc">
                    {{$query['details']['payment']['id']}}
                  </td>
                  <td style="padding: 5px; border: 1px solid #cccccc">
                    {{$query['details']['payment']['status']}}
                  </td>
                  <td style="padding: 5px; border: 1px solid #cccccc">
                      ₹ {{number_format((float)$query['details']['payment']['base_amount']/100, 2,'.', '')}}
                  </td>
                  <td style="padding: 5px; border: 1px solid #cccccc">
                    {{$query['details']['payment']['email']}}
                  </td>
                  <td style="padding: 5px; border: 1px solid #cccccc">
                    {{$query['details']['payment']['contact']}}
                  </td>
                  <td style="padding: 5px; border: 1px solid #cccccc">
                    {{$query['details']['payment_analytics']['ip']}}
                  </td>
                  <td style="padding: 5px; border: 1px solid #cccccc">
                    {{$query['details']['merchant_details']['business_website']}}
                  </td>
                  <td style="padding: 5px; border: 1px solid #cccccc">
                    {{$query['details']['merchant_details']['merchant_name']}}
                  </td>
                </tr>
                  @endforeach
          </table>
        </div>
        <p style="margin: 0">(B) Beneficiary Details</p>
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
              <th style="padding: 5px; border: 1px solid #cccccc" colspan="3">
                <p style="font-size: 14px; margin: 0">
                      Beneficiary Contact Details
                </p>
              </th>
                @if($share_beneficiary_account_details === 1)
                    <th style="padding: 5px; border: 1px solid #cccccc" colspan="3">
                        <p style="font-size: 14px; margin: 0">
                            Beneficiary Account Details
                        </p>
                    </th>
                @endif
            </tr>
            <tr style="padding: 5px; border: 1px solid #cccccc">
              <th style="padding: 5px; border: 1px solid #cccccc">
                Merchant contact name
              </th>
              <th style="padding: 5px; border: 1px solid #cccccc">
                Merchant contact number
              </th>
              <th style="padding: 5px; border: 1px solid #cccccc">
                Merchant contact email
              </th>
              @if($share_beneficiary_account_details === 1)
                <th style="padding: 5px; border: 1px solid #cccccc">
                  Beneficiary Name
                </th>
                <th style="padding: 5px; border: 1px solid #cccccc">
                  Bank Account No.
                </th>
                <th style="padding: 5px; border: 1px solid #cccccc">IFSC Code</th>
              @endif
            </tr>
            <tr style="padding: 5px; border: 1px solid #cccccc">
              <td style="padding: 5px; border: 1px solid #cccccc">
                {{$merchant_details['contact_name']}}
              </td>
              <td style="padding: 5px; border: 1px solid #cccccc">
                {{$merchant_details['contact_mobile']}}
              </td>
              <td style="padding: 5px; border: 1px solid #cccccc">
                {{$merchant_details['contact_email']}}
              </td>
              @if($share_beneficiary_account_details === 1)
                <td style="padding: 5px; border: 1px solid #cccccc">
                  {{$bank_account['beneficiary_name']}}
                </td>
                <td style="padding: 5px; border: 1px solid #cccccc">
                  {{$bank_account['account_number']}}
                </td>
                <td style="padding: 5px; border: 1px solid #cccccc">
                  {{$bank_account['ifsc_code']}}
                </td>
              @endif
            </tr>
          </table>
        </div>

        <p style="margin: 0">
          Should you need any further details for your investigation in this
          regard, we request you to write back to us at
          "fraud.alerts@razorpay.com" along with the complaint / FIR copy and
          the ticket number in the subject line. Thank you for your cooperation.
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
