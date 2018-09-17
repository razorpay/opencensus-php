<?php
    $invoice_data         = $data['invoice'];
    $customer_details     = $data['invoice']['customer_details'];
    $has_customer_details = !!($customer_details['customer_name'] or $customer_details['customer_email'] or $customer_details['customer_contact']);
?>

@if ($has_customer_details or $invoice_data['status'] === 'issued')
    @include('hostedpage.partials.robot', ['no_track' => true])
@else
    @include('hostedpage.partials.robot')
@endif
