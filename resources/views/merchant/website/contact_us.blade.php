<div class='content-container'>
    <p class= 'content-head'>Contact us</p>
    <div class= 'content-seprater'></div>
    <p class= 'updated-date'>Last updated on {{{$data['updated_at']}}}</p>
    <p class= 'content-text'>You may contact us using the information below:</p>
    <p class= 'content-text'>
        Merchant Legal entity name: {{{$data['merchant_legal_entity_name']}}}<br />
        Registered Address: {{{$data['merchant_details']['business_registered_address']}}}
        {{{$data['merchant_details']['business_registered_city']}}}
        {{{$data['merchant_details']['business_registered_state']}}}
        {{{$data['merchant_details']['business_registered_pin']}}}<br />
        Operational Address: {{{$data['merchant_details']['business_operation_address']}}}
        {{{$data['merchant_details']['business_operation_city']}}}
        {{{$data['merchant_details']['business_operation_state']}}}
        {{{$data['merchant_details']['business_operation_pin']}}}
        <br />
        Telephone No: {{{$data['website_detail']['additional_data']['support_contact_number']}}}<br />
        E-Mail ID: {{{$data['website_detail']['additional_data']['support_email']}}}
    </p>
</div>
