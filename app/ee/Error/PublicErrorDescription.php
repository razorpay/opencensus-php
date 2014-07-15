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
}