      <tr class="top">
        <td colspan="2">
          <table>
            <tr>
              <td class="title">
                <img class="logo" src="https://cdn.razorpay.com/logo-small.png">
              </td>

              <td class="text-right">
                Invoice #: {{{$invoice_number}}}<br>
                Created: {{{$invoice_date}}}<br>
              </td>
            </tr>
          </table>
        </td>
      </tr>

      <tr class="information">
        <td colspan="2">
          <table>
            <tr>
              <td>
                <b>Issued To:</b><br/>
                {{{$merchant['name']}}} [{{{$merchant_id}}}]<br>
                @if ($merchant_details['business_registered_address'])
                {{{$merchant_details['business_registered_address']}}}<br/>
                @endif
                @if ($merchant_details['business_registered_city'])
                {{{$merchant_details['business_registered_city']}}}
                @if ($merchant_details['business_registered_pin'])
                -
                @else
                <br/>
                @endif
                @endif
                @if ($merchant_details['business_registered_pin'])
                {{{$merchant_details['business_registered_pin']}}}<br/>
                @endif
                @if ($merchant_details['business_registered_state'])
                {{{$merchant_details['business_registered_state']}}}<br/>
                @endif
                @if (!empty($gstin))
                <span class="code">GSTIN - {{{$gstin}}}</span>
                @endif
              </td>
              <td class="text-right">
                <b>From:</b><br/>
                Razorpay Software Pvt. Ltd.<br/>
                #22, 1st Floor, SJR Cyber,<br/>
                Laskar Hosur Road, Adugodi,<br/>
                Bangalore, Karnataka - 560 030.<br/>
                GSTIN - 29AAGCR4375J1ZU<br/>
                Pan No. - AAGCR4375J<br/>
                CIN No. - U72200KA2013PTC097389
              </td>
            </tr>
          </table>
        </td>
      </tr>

