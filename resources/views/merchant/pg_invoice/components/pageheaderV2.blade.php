<tr class="top">
    <td colspan="2">
        <table>
            <tr>
                <td class="title">
                    <img class="logo" src="https://cdn.razorpay.com/logo-small.png">
                </td>

                <td class="text-right">
                    @if($pageName === 'Tax Credit Note')
                        Credit Note #: CRN{{{$invoice_number}}}<br>
                    @elseif($pageName === 'Tax Debit Note')
                        Debit Note #: DBN{{{$invoice_number}}}<br>
                    @else
                        Invoice #: {{{$invoice_number}}}<br>
                    @endif

                    @if(isset($einvoice_data['e_invoice_complete_generation_date']))
                        Invoice Date: {{{$einvoice_data['e_invoice_complete_generation_date']}}} <br>
                    @else
                        Invoice Date: {{{$invoice_date}}} <br>
                    @endif
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
                        State/UT Code: {{{substr($gstin, 0, 2)}}} (POS)<br/>
                        <span class="code">GSTIN - {{{$gstin}}}</span><br/>
                    @endif
                </td>
                <td class="text-right">
                    <b>From:</b><br/>
                    Razorpay Software Pvt. Ltd.<br/>
                    #22, 1st Floor, SJR Cyber,<br/>
                    Laskar Hosur Road, Adugodi,<br/>
                    Bangalore, Karnataka - 560 030.<br/>
                    State/UT Code: 29<br/>
                    GSTIN - 29AAGCR4375J1ZU<br/>
                    Pan No. - AAGCR4375J<br/>
                    CIN No. - U72200KA2013PTC097389
                </td>
            </tr>
        </table>
    </td>
</tr>

