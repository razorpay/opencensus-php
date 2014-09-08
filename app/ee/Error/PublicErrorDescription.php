<?php

namespace EE\Error;

class PublicErrorDescription
{
    const GATEWAY_ERROR =
        'There is a problem with the gateway causing the transaction to fail';

    const SERVER_ERROR =
        'The server encountered an error. The incident has been reported to admins';

    const GATEWAY_ERROR_REQUEST_TIMEOUT =
        'The gateway request to submit payment information timed out. Please submit your details again';

    const CARD_ERROR_INVALID_EXPIRY_DATE =
        'The expiry date is not valid';

    const CARD_ERROR_INVALID_BRAND =
        'Currently the given card\'s brand is not supported by us';

    const CARD_ERROR_CARD_DECLINED =
        'The card was declined';

    const CARD_ERROR_INSUFFICIENT_BALANCE =
        'The card has insufficient balance';

    const CARD_ERROR_NOT_SUPPORTED =
        'Card network not currently supported';

    const BAD_REQUEST_TRANSACTION_ALREADY_PROCCESSED =
        'The transaction has already been processed. Did you press the back button in browser?';

    const BAD_REQUEST_TRANSACTION_ALREADY_CAPTURED =
        'This transaction has already been captured';

    const BAD_REQUEST_TRANSACTION_STATUS_NOT_CAPTURED =
        'The transaction status should be captured for refund action to be taken';

    const BAD_REQUEST_TRANSACTION_FULLY_REFUNDED =
        'The transaction has been fully refunded already';

    const BAD_REQUEST_TRANSACTION_REFUND_AMOUNT_GREATER_THAN_CAPTURED =
        'The refund amount proivded is greater than amount captured';

    const BAD_REQUEST_TRANSACTION_REFUND_AMOUNT_GREATER_THAN_UNREFUNDED =
        'The refund amount provided is greater than the unrefunded amount';

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

    const BAD_REQUEST_UDF_SHOULD_BE_ARRAY =
        'udf should be provided as a dictionary';

    const BAD_REQUEST_CURRENCY_NOT_SUPPORTED =
        'Invalid currency. Currently only INR is supported.';

    const BAD_REQUEST_ONLY_HTTPS_ALLOWED =
        'Razorpay API is only available over HTTPS.';

    const BAD_REQUEST_UNAUTHORIZED_BASICAUTH_EXPECTED =
        'Please provide your api key for authentication purposes.';

    const BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY =
        'The api key provided is invalid';

    const BAD_REQUEST_UNAUTHORIZED_INVALID_API_SECRET =
        'The api secret provided is invalid';

    const BAD_REQUEST_UNAUTHORIZED_SECRET_NOT_PROVIDED =
        'Please provide api secret';

    const BAD_REQUEST_UNAUTHORIZED_SECRET_SENT_ON_PUBLIC_ROUTE =
        'Please do not provide your secret on public sided requests';

    const BAD_REQUEST_PRICING_ID_REQURED =
        'Pricing plan id is required';

    const BAD_REQUEST_PRICING_PLAN_ALREADY_EXISTS =
        'Pricing plan name already exists. Are you trying a pricing plan rule instead?';

    const BAD_REQUEST_PRICING_RATE_NOT_DEFINED =
        'One of percent_rate and fixed_rate must be present';

    const BAD_REQUEST_PRICING_GATEWAY_REQUIRED =
        'This plan has a gateway set. Please provide it in input';

    const BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED =
        'The new rule matches with an active existing rule';

    const BAD_REQUEST_PRICING_PLAN_WITH_SAME_NAME_EXISTS =
        'Pricing plan name already exists. Are you trying a pricing plan rule instead?';

    const BAD_REQUEST_PRICING_NOT_DEFINED_FOR_MERCHANT =
        'The merchant does not have pricing assigned to him';

    const BAD_REQUEST_MERCHANT_ALREADY_ACTIVATED =
        'The merchant has already been activated';

    const BAD_REQUEST_MERCHANT_NOT_ACTIVATED =
        'The merchant has not been activated. This action can only be taken for activated merchants';

    const BAD_REQUEST_MERCHANT_ALREADY_LIVE =
        'The merchant is already live';

    const BAD_REQUEST_MERCHANT_NOT_LIVE =
        'The merchant is not live currently';

    const BAD_REQUEST_MERCHANT_NOT_LIVE_ACTION_DENIED =
        'There is a temporary block placed on the account currently because of which new payment operations are on put on hold.
         If you are seeing this message unexpectedly, please drop a mail to contact@razorpay.com with your email-id and
         we will look into the issue immediately.';

    const BAD_REQUEST_MERCHANT_NO_TERMINAL_ASSIGNED =
        'The merchant has no pricing assigned';

    const BAD_REQUEST_MERCHANT_KEY_ALREADY_CREATED =
        'The merchant keys have already been created';

    const BAD_REQUEST_GATEWAY_TERMINAL_ID_EXISTS_FOR_MERCHANT =
        'A terminal id has already been assigned to this merchant';

    const BAD_REQUEST_GATEWAY_MERCHANT_ID_EXISTS =
        'A record with same gateway merchant id (mid) exists';
}