<?php
    $invoice_data         = $data['invoice'];
    $customer_details     = $data['invoice']['customer_details'];
    $has_customer_details = !!(isset($customer_details['customer_name']) or isset($customer_details['customer_email']) or isset($customer_details['customer_contact']));
?>

@if ($has_customer_details)
    @include('hostedpage.partials.robot', ['no_track' => true])
@else
    @include('hostedpage.partials.robot')
@endif
