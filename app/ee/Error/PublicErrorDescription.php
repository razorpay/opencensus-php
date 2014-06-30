<?php

namespace EE\Error;

class PublicErrorDescription
{
    const API_KEY_NOT_PROVIDED =
        'The API key is missing. Please supply your API Key along with API Secret';

    const API_SECRET_NOT_PROVIDED =
        'The API secret is missing. Please supply your API secret along with the API key';

    const INVALID_TXN_ID =
        'The transaction id provided does not exist';

    const INVALID_LGR_ID =
        'The ledger id provided is not valid';

    const CARD_NUMBER_HAS_INVALID_CHARS =
        'The card number provided has invalid characters. Only numbers are allowed';

    const CARD_NUMBER_LENGTH_EXCEEDS_MAX_DIGITS =
        'The card number provided exceeds 19 (maximum) number of digits';

    const CARD_NUMBER_FAILS_LUHN_CHECK =
        'The card number provided fails Luhn\'s check and hence is invalid';

    const CARD_INVALID_EXPIRY_MONTH =
        'The card expiry month provided is not valid. It should be between 1 - 12.';

    const CARD_INVALID_EXPIRY_YEAR =
        'The card expiry year provided is not valid.';

    const CARD_ERROR_INVALID_EXPIRY_DATE =
        'The expiry date is not valid';

    const GATEWAY_REQUEST_TIMEOUT =
        'The gateway request to submit payment information timed out. Please submit your details again';

    const GATEWAY_ERROR =
        'There is a problem with the gateway causing the transaction to fail';

    const SERVER_ERROR =
        'Looks like nemo is again playing with our server. Please try your request again!';

    const CARD_ERROR_INVALID_BRAND =
        'Currently the given card\'s brand is not supported by us';

    const CARD_ERROR_INVALID_NUMBER =
        'The card number is invalid';

    const CARD_ERROR_INVALID_NAME =
        'The cardholder name given is invalid';

    const CARD_ERROR_CARD_DECLINED =
        'The card was declined';

    const CARD_ERROR_INSUFFICIENT_BALANCE =
        'The card has insufficient balance';

    const GATEWAY_ERROR_INVALID_AMOUNT =
        'The amount provided is invalid';

    const BAD_REQUEST_TRANSACTION_ALREADY_CAPTURED =
        'This transaction has already been captured';

    const BAD_REQUEST_TRANSACTION_ALREADY_REFUNDED =
        'This transaction has already been refunded';
}