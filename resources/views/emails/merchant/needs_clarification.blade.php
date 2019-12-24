<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<div>
      <div>
        Hey there,
      </div>

      <br />

      <div>
        We have received your KYC details but there are some errors associated
        with your document details.
      </div>

      <br />

      <div>
        <strong
          >Please fix these issues as soon as possible to complete your KYC
          process:</strong
        >
      </div>

      <br />

      <table style="width:100%;border:1px solid black;border-collapse:collapse;">
        <thead>
          <tr>
            <th
              class="issue"
              style="border:1px solid black;border-collapse:collapse;padding:10px;width:40%;"
            >
              Document or Issue
            </th>
            <th
              class="fix"
              style="border:1px solid black;border-collapse:collapse;padding:10px;width:60%;"
            >
              How can you fix it?
            </th>
          </tr>
        </thead>
        <tbody>
        @if(array_key_exists('fields', $clarification_reason))
            @foreach($clarification_reason['fields'] as $fields)
                @foreach($fields as $meta_data)
                    <tr>
                        <td style="border:1px solid black;border-collapse:collapse;padding:10px;">
                            {{{$meta_data['display_name']}}}
                        </td>
                        <td style="border:1px solid black;border-collapse:collapse;pading:10px;">
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
                        <td style="border:1px solid black;border-collapse:collapse;padding:10px;">
                            {{{$meta_data['display_name']}}}
                        </td>
                        <td style="border:1px solid black;border-collapse:collapse;padding:10px;">
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
        Best regards,
        <br />
        Team Razorpay
      </div>
    </div>
</body>
</html>
