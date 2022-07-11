<!DOCTYPE html>
<html lang="en-US">

<head>
    <meta charset="utf-8">
</head>

<body
    style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; line-height: 1.6em; font-size: 85%; padding: 10px;">
<div>
    Dear Partner,
    <br/>
    <br/>
    Find below last week's updates for your affiliate's Razorpay account activation.
    <br/>
    You can also find the detailed summary and take action on the affiliate's KYC from your <a
        href="https://dashboard.razorpay.com/app/partners/submerchants" style="text-decoration: none; color: #528FF0;">Partner
        Dashboard</a>
    <br/>
    <br/>

    <table style="border: 1px solid black;text-align: left; width: 100%; border-collapse:collapse;" cellpadding="10">
        <thead>
        <tr>
            <th style="text-align: left; border: 1px solid black; width: 15%;">
                Merchant Id
            </th>
            <th style="text-align: left; border: 1px solid black; width: 20%;">
                Merchant Name
            </th>
            <th style="text-align: left; border: 1px solid black; width: 20%;">
                Activation Status
            </th>
            <th style="text-align: left; border: 1px solid black; width: 45%;">
                Next steps
            </th>
        </tr>
        </thead>
        <tbody>
        @foreach($activationStatusRows as $row)
            <tr style="border: 1px solid black;">
                <td style="border: 1px solid black;"> {{{$row['merchant_id']}}} </td>
                <td style="border: 1px solid black;"> {{{$row['merchant_name']}}} </td>
                <td style="border: 1px solid black;"> {{{$row['activation_status_label']}}} </td>
                <td style="border: 1px solid black;">
                    @switch($row['activation_status'])
                        @case('activated')
                        Merchant can start accepting payments and they will receive settlements in their registered bank
                        account.
                        @break
                        @case('activated_mcc_pending')
                        Merchant can start accepting payments. Razorpay team will review the business model and website
                        details of the merchant and reach out for further clarifications.
                        @break
                        @case('activated_kyc_pending')
                        Merchant can start accepting payments and get settlements upto a limit of Rs. 50,000 (if Non-GST holder) or Rs. 5,00,000 (if GST holder). 
                        Complete KYC form needs to be submitted and approved to remove the limit.
                        @break
                        @case('rejected')
                        @break
                        @case('under_review')
                        Our team is reviewing the KYC details and would reach out for further clarifications.
                        @break
                        @case('instantly_activated')
                        Merchant can start accepting payments up to INR 15,000 now. Complete KYC form needs to be
                        submitted and approved to enable settlements to merchant's account. Complete merchant's KYC from
                        Partner Dashboard.
                        @break

                        @case('needs_clarification')
                        Find below the list of documents/ fields requiring actions and next steps (Please note the below
                        comments are directed to your affiliates):
                        <br/>
                        <ul>
                            @if(array_key_exists('fields', $row['clarification_reasons']))
                                @foreach($row['clarification_reasons']['fields'] as $fields)
                                    @foreach($fields as $meta_data)
                                        <li>
                                            {{{$meta_data['display_name']}}} : {{{$meta_data['reason_description']}}}
                                        </li>
                                    @endforeach
                                @endforeach
                            @endif
                            @if(array_key_exists('documents', $row['clarification_reasons']))
                                @foreach($row['clarification_reasons']['documents'] as $documents)
                                    @foreach($documents as $meta_data)
                                        <li>
                                            {{{$meta_data['display_name']}}} : {{{$meta_data['reason_description']}}}
                                        </li>
                                    @endforeach
                                @endforeach
                            @endif
                        </ul>
                        @break
                    @endswitch
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <br/>
    @if($countKYCNotInitiatedInTwoMonths >= 1)
        Please note: {{{$countKYCNotInitiatedInTwoMonths}}} affiliates have not yet started their KYC process. Notify
        your affiliates to complete KYC in order to start accepting payments. Once your affiliates start receiving
        payments, you'll earn commissions on each transaction.
        <br/>
    @endif


    @if($isMerchantCountCapped === true)
        View updates for other affiliates on your <a
        href="https://dashboard.razorpay.com/app/partners/submerchants" style="text-decoration: none; color: #528FF0;">Partner
        Dashboard</a>
        <br/>
    @endif

    <br/>
    Refer to
    <a href="https://razorpay.com/docs/payments/kyc/" style="text-decoration: none; color: #528FF0;">this</a>
    link for more information on document requirements for Razorpay KYC.

    <div>
        <br/>
        Cheers,
        <br/>
        Razorpay Partnership Team
    </div>
</div>
</body>

</html>
