<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; line-height: 1.6em; font-size: 100%; padding: 10px;">
<div>

      <div>
        Hey {{{$merchant['name']}}}, 
      </div>

      <br />
      
      <div>
      We need additional information to verify KYC and activate your client's account, (MID: {{{$merchant['id']}}}) 

      <br />
      Assist in resolving the issues mentioned below by providing clarifications yourself or guide your client through the process to ensure activation.
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

        Note: Clients cannot collect payments or receive settlements until the details are updated.


        If you're assisting your clients to respond to clarifications, here's a quick laydown of the steps
        <ul>
          <li>Log into your Partner Dashbord</li>
          <li>Click on the 'Affiliate Accounts' section located in the left nav bar</li>
          <li>Find the corresponding account using account ID search, click on resubmit KYC details under actions column</li>
          <li>Submit the clarifications & you're good to go</li>
        </ul>
      </div>

      <div>
        
      <br />
      Regards, 
      <br />
      Team Razorpay
      </div>
    </div>
</body>
</html>
