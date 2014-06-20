<?php

namespace EE\Error;

class PublicErrorMessage
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
        'The card number provided fails LUHN\' check and hence is invalid';

    const CARD_INVALID_EXPIRY_MONTH =
        'The card expiry month provided is not valid. It should be between 1 - 12.';

    const CARD_INVALID_EXPIRY_YEAR =
        'The card expiry year provided is not valid.';

    const GATEWAY_REQUEST_TIMED_OUT =
        'The gateway request to submit payment information timed out. Please submit your details again';
}