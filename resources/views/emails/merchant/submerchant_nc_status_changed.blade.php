<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; line-height: 1.6em; font-size: 100%; padding: 10px;">
<div>

      <div>
        Dear Partner, 
      </div>

      <br />
      
      <div>
      We have received the KYC details of your affiliate {{{$merchant['name']}}} (MID: {{{$merchant['id']}}}) but we need some clarifications regarding the submitted information. 
Please notify your merchant to fix these issues and resubmit the KYC. If you have the relevant information handy, you can re-submit merchant's KYC from the 
<a class="link" href="https://dashboard.razorpay.com/app/partners/submerchants" style="text-decoration: none; color: #528FF0;">partner dashboard</a>
itself.
      </div>

      <br />

      <table style="width:100%;border:1px solid black;border-collapse:collapse;" cellpadding="10">
        <thead>
          <tr>
            <th
              class="issue"
              style="border:1px solid black;border-collapse:collapse;padding:10px;width:40%;text-align: left;" width="30%"
            >
              Document or Issue
            </th>
            <th
              class="fix"
              style="border:1px solid black;border-collapse:collapse;padding:10px;width:60%; text-align: left;" width="70%"
            >
              How to fix it?
              <br/>
              (Below mentioned comments are directed to your affiliate)
            </th>
          </tr>
        </thead>
        <tbody>
        @if(array_key_exists('fields', $clarification_reason))
            @foreach($clarification_reason['fields'] as $fields)
                @foreach($fields as $meta_data)
                    <tr>
                        <td style="border:1px solid black;border-collapse:collapse;padding:15px;" width="30%" valign="top">
                            {{{$meta_data['display_name']}}}
                        </td>
                        <td style="border:1px solid black;border-collapse:collapse;pading:10px;" width="70%">
                            {{{$meta_data['reason_description']}}}
                        </td>
                    </tr>
                @endforeach
            @endforeach
        @endif
        @if(array_key_exists('documents', $clarification_reason))
            @foreach($clarification_reason['documents'] as $documents)
                @foreach($documents as $meta_data)
                    <tr>
                        <td style="border:1px solid black;border-collapse:collapse;padding:15px;" width="30%" valign="top">
                            {{{$meta_data['display_name']}}}
                        </td>
                        <td style="border:1px solid black;border-collapse:collapse;padding:10px;" width="70%">
                            {{{$meta_data['reason_description']}}}
                        </td>
                    </tr>
                @endforeach
            @endforeach
        @endif
        </tbody>
      </table>

      <br />
      <div>
        If you have any queries, please reach out to us at
        <a class="link" href="mailto:partners@razorpay.com" style="text-decoration: none;
        color: #528FF0;">partners@razorpay.com</a>.
      </div>

      <div>
        
      <br />
      Cheers, 
      <br />
      Razorpay Partnership Team
      </div>
    </div>
</body>
</html>
