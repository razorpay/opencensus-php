<?php

namespace EE\Error;

class PublicErrorDescription
{
    const GATEWAY_ERROR =
        'There is a problem with the gateway causing the transaction to fail';

    const SERVER_ERROR =
        'Looks like nemo is again playing with our server. Please try your request again!';

    const CARD_ERROR_INVALID_EXPIRY_DATE =
        'The expiry date is not valid';

    const GATEWAY_REQUEST_TIMEOUT =
        'The gateway request to submit payment information timed out. Please submit your details again';

    const CARD_ERROR_INVALID_BRAND =
        'Currently the given card\'s brand is not supported by us';

    const CARD_ERROR_CARD_DECLINED =
        'The card was declined';

    const CARD_ERROR_INSUFFICIENT_BALANCE =
        'The card has insufficient balance';

    const CARD_ERROR_NOT_SUPPORTED =
        'Card network not currently supported';

    const BAD_REQUEST_TRANSACTION_ALREADY_CAPTURED =
        'This transaction has already been captured';

    const BAD_REQUEST_TRANSACTION_ALREADY_REFUNDED =
        'This transaction has already been refunded';

    const BAD_REQUEST_TRANSACTION_CARD_IS_NOT_ARRAY =
        'Card provided is not a dictionary';

    const BAD_REQUEST_TRANSACTION_CARD_NOT_PROVIDED =
        'Transaction Exception: Card not provided';

    const BAD_REQUEST_INVALID_ID =
        'The id provided does not exist';

    const BAD_REQUEST_CAPTURE_AMOUNT_GREATER_THAN_AUTH =
        'Capture amount cannot be greater than authorized amount';

    const BAD_REQUEST_KEY_EXPIRED =
        'Key is expired';

    const BAD_REQUEST_KEY_EXPIRING_SOON =
        'Key is already set to expire soon';

    const BAD_REQUEST_URL_NOT_FOUND =
        'The requested URL was not found on the server.';

    const BAD_REQUEST_TRANSACTION_CAPTURE_ONLY_AUTHORIZED =
        'Only transactions which have been authorized and not yet captured can be captured';

    const BAD_REQUEST_UDF_TOO_MANY_KEYS =
        'Number of fields in udf should be less than or equal to 15';

    const BAD_REQUEST_UDF_VALUE_CANNOT_BE_ARRAY =
        'Udf values themselves should not be an array';

    const BAD_REQUEST_UDF_KEY_TOO_LARGE =
        'Udf key cannot be greater 255 characters';

    const BAD_REQUEST_UDF_VALUE_TOO_LARGE =
        'Udf value cannot be greater 255 characters';

    const BAD_REQUEST_DESCRIPTION_SHOULD_BE_STRING =
        'Description provides should be string';

    const BAD_REQUEST_DESCRIPTION_TOO_LARGE =
        'Description provides should be max 1000 characters';
}