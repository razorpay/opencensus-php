<?php

namespace RZP\Error;

class CustomerErrorDescription
{
    const GATEWAY_ERROR                                                         = 'Payment failed. Please try again with a different payment method';

    const BAD_REQUEST_RATE_LIMIT_EXCEEDED                                       = 'Request failed. Please try after sometime.';

    const BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED                     = '3dsecure or OTP authentication failed';
    const BAD_REQUEST_PAYMENT_CARD_NOT_ENROLLED_FOR_3DSECURE                    = 'The card holder is not enrolled for 3dsecure authentication';
    const BAD_REQUEST_PAYMENT_BLOCKED_DUE_TO_FRAUD                              = 'Payment declined';
    const BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD                                    = 'Payment declined';
    const BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_BLOCKED_CARD              = 'Payment declined';
    const BAD_REQUEST_CARD_STOLEN_OR_LOST                                       = 'Payment declined';
    const BAD_REQUEST_PAYMENT_CARD_INVALID_CVV                                  = 'The CVV provided is incorrect';
    const BAD_REQUEST_PAYMENT_CONTACT_INCORRECT_FORMAT                          = 'Contact number can only contain digits and + symbol';
    const BAD_REQUEST_PAYMENT_CONTACT_INVALID_COUNTRY_CODE                      = 'Country code provided in contact is invalid';
    const BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED                        = 'This card network is not supported';
    const BAD_REQUEST_PAYMENT_CARD_EXPIRED                                      = 'This card has expired';
    const BAD_REQUEST_PAYMENT_CARD_NUMBER_NOT_LEGITIMATE                        = 'You\'ve entered an incorrect card number. Try again.';
    const BAD_REQUEST_PAYMENT_CARD_WITHDRAWAL_LIMITS_EXCEEDED                   = 'Payment declined by bank because card limit has exceeded';
    const BAD_REQUEST_PAYMENT_CARD_ISSUING_BANK_PREVENTED_AUTHORIZATION         = 'The payment has been declined by the card issuing bank';
    const BAD_REQUEST_PAYMENT_CARD_ISSUING_BANK_NOT_PERMITTING_PAYMENT          = 'This type of payment is not allowed on this card';
    const BAD_REQUEST_PAYMENT_CARD_INTERNATIONAL_NOT_ALLOWED                    = 'International cards are not allowed';
    const BAD_REQUEST_PAYMENT_FAILED_BECAUSE_SESSION_EXPIRED                    = 'Your payment could not be completed on time. Try again later.';
    const BAD_REQUEST_PAYMENT_TIMED_OUT                                         = 'Payment was not completed on time. Please retry';
    const BAD_REQUEST_PAYMENT_WALLET_PER_DAY_LIMIT_EXCEEDED                     = 'Payment failed because daily limit of the wallet has exceeded. Please retry with another payment mode.';
    const BAD_REQUEST_PAYMENT_WALLET_PER_WEEK_LIMIT_EXCEEDED                    = 'Payment failed because weekly limit of the wallet has exceeded. Please retry with another payment mode.';
    const BAD_REQUEST_PAYMENT_WALLET_PER_MONTH_LIMIT_EXCEEDED                   = 'Payment failed because monthly limit of the wallet has been exceeded. Please retry with another payment mode.';
    const BAD_REQUEST_PAYMENT_WALLET_PER_PAYMENT_AMOUNT_CROSSED                 = 'This wallet cannot be used because the amount limit per payment has been crossed. Please retry with another payment mode.';
    const BAD_REQUEST_PAYMENT_WALLET_BLOCKED_CARD                               = 'This card has been blocked by the wallet provider. Please retry with another payment mode.';
    const BAD_REQUEST_PAYMENT_WALLET_BLOCKED_MOBILE_NUMBER                      = 'This mobile number has been blocked by the wallet provider. Please retry with another payment mode.';
    const BAD_REQUEST_PAYMENT_WALLET_BLOCKED_IP_ADDRESS                         = 'This IP address has been blocked by the wallet provider. Please retry with another payment mode.';
    const BAD_REQUEST_PAYMENT_WALLET_BLOCKED_CUSTOMER                           = 'The wallet provider has blocked your user account. Please try a different payment mode';
    const BAD_REQUEST_PAYMENT_WALLET_MAX_WRONG_ATTEMPT_LIMIT_CROSSED            = 'Failed transaction limit crossed for this wallet. Please try a different payment mode';
    const BAD_REQUEST_PAYMENT_WALLET_MAX_AMOUNT_LIMIT_CROSSED_FOR_CUSTOMER      = 'Payment failed because maximum wallet payment amount limit has been crossed for you';
    const BAD_REQUEST_PAYMENT_WALLET_NOT_ACTIVATED                              = 'This wallet account has not been activated. Please retry with another payment mode';
    const BAD_REQUEST_PAYMENT_WALLET_USER_DOES_NOT_EXIST                        = 'This wallet account does not exist';
    const BAD_REQUEST_PAYMENT_WALLET_ACCOUNT_INVALID_CREDENTIALS                = 'No wallet account found for this mobile and email ID';
    const BAD_REQUEST_PAYMENT_WALLET_NO_USER_WITH_CELL                          = 'No wallet account found for this mobile number';
    const BAD_REQUEST_PAYMENT_WALLET_AUTHENTICATION_FAILED                      = 'Authentication failed for the wallet account. Please retry.';
    const BAD_REQUEST_PAYMENT_FEES_GREATER_THAN_AMOUNT                          = 'Payment failed. Please contact site admin';

    const BAD_REQUEST_PAYMENT_DECLINED_BY_CLICKING_CANCEL                       = 'Payment cancelled due to clicking the cancel button on 3dSecure page';

    const BAD_REQUEST_SUBSCRIPTION_CUSTOMER_ID_SENT_IN_INPUT                    = 'Payment failed';
    const BAD_REQUEST_SUBSCRIPTION_SAVE_CARD_DISABLED                           = 'Please contact the merchant for further assistance.';
    const BAD_REQUEST_GLOBAL_CUSTOMER_MISMATCH                                  = 'Payment failed. Please login.';
    const BAD_REQUEST_APP_TOKEN_ABSENT                                          = 'Payment failed. Please login.';
    const BAD_REQUEST_APP_TOKEN_NOT_GLOBAL                                      = 'Payment failed. Please login.';
    const BAD_REQUEST_PAYMENT_PENDING_AUTHORIZATION                             = 'Payment is pending authorization. Request for authorization from approver.';
}
