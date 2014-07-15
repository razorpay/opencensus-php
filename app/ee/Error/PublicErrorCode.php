<?php

namespace EE\Error;

class PublicErrorCode
{
    const GATEWAY_ERROR = 'gateway_error';

    const BAD_REQUEST_ERROR = 'bad_request_error';

    const SERVER_ERROR = 'server_error';

    /**
     * public card errors
     */
    const CARD_ERROR_INVALID_NAME                                   = 'CARD_ERROR_INVALID_NAME';
    const CARD_ERROR_INVALID_EXPIRY_MONTH                           = 'CARD_ERROR_INVALID_EXPIRY_MONTH';
    const CARD_ERROR_INVALID_EXPIRY_YEAR                            = 'CARD_ERROR_INVALID_EXPIRY_YEAR';
    const CARD_ERROR_INVALID_CVV                                    = 'CARD_ERROR_INVALID_CVV';
    const CARD_ERROR_INVALID_BRAND                                  = 'CARD_ERROR_INVALID_BRAND';
    const CARD_ERROR_INVALID_NUMBER                                 = 'CARD_ERROR_INVALID_NUMBER';
    const CARD_ERROR_INVALID_EXPIRY_DATE                            = 'CARD_ERROR_INVALID_EXPIRY_DATE';
    const CARD_ERROR_CARD_DECLINED                                  = 'CARD_ERROR_CARD_DECLINED';
    const CARD_ERROR_INSUFFICIENT_BALANCE                           = 'CARD_ERROR_INSUFFICIENT_BALANCE';
    const CARD_ERROR_NOT_SUPPORTED                                  = 'CARD_ERROR_NOT_SUPPORTED';

    const FIELD_ERROR_INVALID_EMAIL                                 = 'FIELD_ERROR_INVALID_EMAIL';
    const FIELD_ERROR_INVALID_CONTACT                               = 'FIELD_ERROR_INVALID_CONTACT';
}