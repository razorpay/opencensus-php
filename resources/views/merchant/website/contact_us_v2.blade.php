<div class='content-container'>
    <p class= 'content-head'>Contact us</p>
    <div class= 'content-seprater'></div>
    <p class= 'updated-date'>Last updated on {{{$data['updated_at']}}}</p>
    <p class= 'content-text'>You may contact us using the information below:</p>
    <p class= 'content-text'>
        Merchant Legal entity name: {{{$data['merchant_legal_entity_name']}}}<br />
        @isset($data['merchant_details']['business_registered_address'])
            Registered Address: {{{$data['merchant_details']['business_registered_address']}}}
            {{{$data['merchant_details']['business_registered_city']}}}
            {{{RZP\Constants\IndianStates::getStateName($data['merchant_details']['business_registered_state'])}}}
            {{{$data['merchant_details']['business_registered_pin']}}}<br />
        @endisset
        @isset($data['merchant_details']['business_operation_address'])
            Operational Address: {{{$data['merchant_details']['business_operation_address']}}}
            {{{$data['merchant_details']['business_operation_city']}}}
            {{{RZP\Constants\IndianStates::getStateName($data['merchant_details']['business_operation_state'])}}}
            {{{$data['merchant_details']['business_operation_pin']}}}
            <br />
        @endisset
        @isset($data['website_detail']['additional_data']['support_contact_number'])
            Telephone No: {{{$data['website_detail']['additional_data']['support_contact_number']}}}<br />
        @endisset
        @isset($data['website_detail']['additional_data']['support_email'])
            E-Mail ID: {{{$data['website_detail']['additional_data']['support_email']}}}
        @endisset
    </p>
</div>
