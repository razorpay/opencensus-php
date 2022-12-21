<?php

namespace RZP\Models\Customer\Account\Metrics;

final class Metric
{
    // labels
    const LABEL_ERROR_MESSAGE                                   = 'error_message';

    const GLOBAL_CREATE_ADDRESS_COUNT                           = 'global_create_address_count';
    const GLOBAL_CREATE_ADDRESS_ERROR_COUNT                     = 'global_create_address_error_count';
    const GLOBAL_CREATE_ADDRESS_RESPONSE_TIME_MILLIS            = 'global_create_address_response_time_millis';

    const GLOBAL_EDIT_ADDRESS_COUNT                           = 'global_edit_address_count';
    const GLOBAL_EDIT_ADDRESS_ERROR_COUNT                     = 'global_edit_address_error_count';
    const GLOBAL_EDIT_ADDRESS_RESPONSE_TIME_MILLIS            = 'global_edit_address_response_time_millis';

    const GLOBAL_CUSTOMER_EDIT_COUNT                          = 'global_customer_edit_count';
    const GLOBAL_CUSTOMER_EDIT_ERROR                          = 'global_customer_edit_error';

}
