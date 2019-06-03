<?php

namespace RZP\Error\P2p;

class PublicErrorDescription extends \RZP\Error\PublicErrorDescription
{
    // @codingStandardsIgnoreStart

    const SERVER_ERROR_CONTEXT_MERCHANT_REQUIRED                = 'Merchant is required in context for the action';
    const SERVER_ERROR_CONTEXT_DEVICE_REQUIRED                  = 'Device is required in context for the action';
    const SERVER_ERROR_CONTEXT_HANDLE_REQUIRED                  = 'Handle is required in context for the action';

    const BAD_REQUEST_INVALID_HANDLE                            = 'Invalid handle is passed in the request';
    const BAD_REQUEST_DEVICE_NOT_ATTACHED_TO_HANDLE             = 'Device is not registered for the given handle';
    const BAD_REQUEST_MERCHANT_NOT_ALLOWED_ON_HANDLE            = 'Merchant is not allowed to use the handle';
    const BAD_REQUEST_DEVICE_DOES_NOT_BELONG_TO_MERCHANT        = 'Device is not registered for the given merchant';
    const BAD_REQUEST_INVALID_MERCHANT_IN_CONTEXT               = 'Invalid merchant set in context';

    const BAD_REQUEST_DUPLICATE_VPA                             = 'Duplicate VPA address, try a different username';

    const GATEWAY_ERROR                                         = 'Action could not be completed at bank';
    const GATEWAY_ERROR_DEVICE_INVALID_TOKEN                    = 'Token is invalid or expired';
    const GATEWAY_ERROR_CONNECTION_ERROR                        = 'Unable to connect to bank';

    const BAD_REQUEST_TRANSACTION_INVALID_STATE                 = 'Transaction is not in valid state for update';
    const BAD_REQUEST_DUPLICATE_TRANSACTION                     = 'Duplicate transaction request received';
    // @codingStandardsIgnoreEnd
}
