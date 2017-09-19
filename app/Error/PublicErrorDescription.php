<?php

namespace RZP\Error;

class PublicErrorDescription
{
    //
    // Please note before declaring strings that have characters that require escaping.
    // Serialization is a problem when using these characters where signing is involved.
    // Because the other side may read the backslashes as escape characters and ignore
    // them while generating the signature.
    // As per JSON spec these need escpaing -
    //        %x22 /          ; "    quotation mark  U+0022
    //        %x5C /          ; \    reverse solidus U+005C
    //        %x2F /          ; /    solidus         U+002F
    //        %x62 /          ; b    backspace       U+0008
    //        %x66 /          ; f    form feed       U+000C
    //        %x6E /          ; n    line feed       U+000A
    //        %x72 /          ; r    carriage return U+000D
    //        %x74 /          ; t    tab             U+0009
    //


    const GATEWAY_ERROR                                                         = 'Payment processing failed due to error at bank or wallet gateway';
    const SERVER_ERROR                                                          = 'The server encountered an error. The incident has been reported to admins.';
    const GATEWAY_ERROR_REQUEST_TIMEOUT                                         = 'The gateway request to submit payment information timed out. Please submit your details again';
    const GATEWAY_ERROR_PROCESSING_DECLINED                                     = 'Payment failed due to processing error on gateway';
    const GATEWAY_ERROR_SYSTEM_BUSY                                             = 'Gateway system is busy, please retry.';
    const GATEWAY_ERROR_COMMUNICATION_ERROR                                     = 'Gateway experienced a communication error.';
    const GATEWAY_ERROR_USER_INACTIVE                                           = 'User is inactive.';
    const GATEWAY_ERROR_PAYMENT_BIN_CHECK_FAILED                                = 'Card rejected by bank.';
    const GATEWAY_ERROR_PAYMENT_AUTHENTICATION_ERROR                            = 'Payment failed because card holder couldn\'t be authenticated';
    const GATEWAY_ERROR_FALSE_AUTHORIZE                                         = 'The payment was wrongly authorized';
    const GATEWAY_ERROR_REFUND_DUPLICATE_REQUEST                                = 'Duplicate Refund Request';

    const BAD_REQUEST_INVALID_PASSWORD_RESET_TOKEN                              = 'The reset link has expired or invalid';
    const BAD_REQUEST_CHANGE_PASSWORD_NOT_ALLWOED                               = 'Password Change is not allowed for this Org';
    const BAD_REQUEST_URL_NOT_FOUND                                             = 'The requested URL was not found on the server.';
    const BAD_REQUEST_ROUTE_DISABLED                                            = 'The requested route is disabled.';
    const BAD_REQUEST_ONLY_HTTPS_ALLOWED                                        = 'Razorpay API is only available over HTTPS.';
    const BAD_REQUEST_FORBIDDEN                                                 = 'Access forbidden for requested resource';
    const BAD_REQUEST_HTTP_METHOD_NOT_ALLOWED                                   = 'The current http method is not supported';
    const BAD_REQUEST_INVALID_ID                                                = 'The id provided does not exist';
    const BAD_REQUEST_INVALID_IDS                                               = 'One or more of the ids provided does not exist';
    const BAD_REQUEST_NO_RECORDS_FOUND                                          = 'No db records found.';
    const BAD_REQUEST_PAYMENT_FAILED                                            = 'Payment failed';
    const BAD_REQUEST_PAYMENT_CANCELLED_BY_USER                                 = 'Payment processing cancelled by user';
    const BAD_REQUEST_PAYMENT_CANCELLED_BY_PRESSING_BACK_ON_ANDROID             = 'Payment processing cancelled by pressing back button on android';
    const BAD_REQUEST_PAYMENT_CANCELLED_AT_LOGIN_SCREEN                         = 'Payment processing cancelled by customer at login screen';
    const BAD_REQUEST_PAYMENT_CANCELLED_AT_WALLET_PAYMENT_PAGE                  = 'Payment processing cancelled by customer at wallet payment page';
    const BAD_REQUEST_PAYMENT_CANCELLED_AT_NETBANKING_PAYMENT_PAGE              = 'Payment processing cancelled by customer at netbanking payment page';
    const BAD_REQUEST_RATE_LIMIT_EXCEEDED                                       = 'Request failed. Please try after sometime.';
    const BAD_REQUEST_PAYMENT_ALREADY_PROCESSED                                 = 'The payment has already been processed';
    const BAD_REQUEST_PAYMENT_ALREADY_CAPTURED                                  = 'This payment has already been captured';
    const BAD_REQUEST_PAYMENT_CURRENCY_NOT_SUPPORTED                            = 'Currency is not supported';
    const BAD_REQUEST_PAYMENT_METHOD_NOT_TRANSFER                               = 'The payment method should be transfer for action to be taken';
    const BAD_REQUEST_PAYMENT_STATUS_NOT_CAPTURED                               = 'The payment status should be captured for action to be taken';
    const BAD_REQUEST_PAYMENT_STATUS_CAPTURE_NOT_PROCESSED                      = 'Capture request is not processed yet';
    const BAD_REQUEST_PAYMENT_PAYOUT_BEFORE_SETTLEMENT                          = 'Payout cannot be created on a payment that has not been settled to your account';
    const BAD_REQUEST_PAYMENT_PAYOUT_AMOUNT_GREATER_THAN_CAPTURED               = 'The payout amount provided is greater than the payment amount captured';
    const BAD_REQUEST_PAYMENT_PAYOUT_AMOUNT_GREATER_THAN_PENDING                = 'The payout amount provided is greater than the payout amount pending for the payment';
    const BAD_REQUEST_PAYMENT_FULLY_PAIDOUT                                     = 'The payment has been fully paidout already';
    const BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS                     = 'Request failed because another payment operation is in progress';
    const BAD_REQUEST_PAYMENT_FULLY_REFUNDED                                    = 'The payment has been fully refunded already';
    const BAD_REQUEST_PAYMENT_REFUND_AMOUNT_GREATER_THAN_CAPTURED               = 'The refund amount provided is greater than amount captured';
    const BAD_REQUEST_PAYMENT_REFUND_AMOUNT_GREATER_THAN_UNREFUNDED             = 'The refund amount provided is greater than the unrefunded amount';
    const BAD_REQUEST_PAYMENT_UNDER_DISPUTE_CANNOT_BE_REFUNDED                  = 'The refund on this payment is blocked due to ongoing dispute investigation';
    const BAD_REQUEST_PAYMENT_AMOUNT_LESS_THAN_MIN_AMOUNT                       = 'Minimum transaction amount allowed is Re. 1';
    const BAD_REQUEST_PAYMENT_AMOUNT_LESS_THAN_10_MIN_AMOUNT                    = 'Minimum transaction amount allowed is Rs 10';
    const BAD_REQUEST_PAYMENT_AMOUNT_TOO_HIGH                                   = 'The amount may not be greater than 50000000.';
    const BAD_REQUEST_PAYMENT_ATOM_NET_BANKING_MIN_AMOUNT_FIFTY                 = 'Minimum amount allowed for net banking transaction for the merchant is INR 50';
    const BAD_REQUEST_PAYMENT_AMOUNT_LESS_THAN_MIN_AMOUNT_FOR_EMI               = 'Minimum transaction amount allowed is Rs 2000';
    const BAD_REQUEST_PAYMENT_CARD_IS_NOT_ARRAY                                 = 'Card provided is not a dictionary';
    const BAD_REQUEST_PAYMENT_CARD_NOT_PROVIDED                                 = 'Payment Exception: Card not provided';
    const BAD_REQUEST_PAYMENT_CARD_CVV_NOT_PROVIDED                             = 'Payment Exception: Card cvv not provided';
    const BAD_REQUEST_PAYMENT_CARD_INVALID_CVV                                  = 'Payment failed due to incorrect card CVV';
    const BAD_REQUEST_PAYMENT_CARD_INVALID_PIN                                  = 'Payment failed';
    const BAD_REQUEST_PAYMENT_CARD_HOLDER_AUTHENTICATION_FAILED                 = 'Payment failed because cardholder couldn\'t be authenticated';
    const BAD_REQUEST_PAYMENT_CARD_AUTHENTICATION_NOT_AVAILABLE                 = 'Payment failed because cardholder couldn\'t be authenticated';
    const BAD_REQUEST_PAYMENT_NET_BANKING_NOT_ENABLED                           = 'Net banking is not enabled for the merchant';
    const BAD_REQUEST_PAYMENT_WALLET_NOT_SUPPORTED                              = 'Wallet is not supported';
    const BAD_REQUEST_PAYMENT_WALLET_NOT_PROVIDED                               = 'Wallet is not provided';
    const BAD_REQUEST_PAYMENT_RECURRING_NOT_ENABLED                             = 'Recurring payment is not enabled for the merchant';
    const BAD_REQUEST_PAYMENT_RECURRING_AUTH_NOT_SUPPORTED                      = 'recurring payment is not supported on public auth';
    const BAD_REQUEST_PAYMENT_WALLET_NOT_ENABLED_FOR_MERCHANT                   = 'Wallet is not enabled for the merchant';
    const BAD_REQUEST_PAYMENT_CARD_NOT_ENABLED_FOR_MERCHANT                     = 'Card transactions are not enabled for the merchant';
    const BAD_REQUEST_PAYMENT_EMI_NOT_ENABLED_FOR_MERCHANT                      = 'Emi transactions are not enabled for the merchant';
    const BAD_REQUEST_PAYMENT_EMI_NOT_AVAILABLE_ON_CARD                         = 'Emi is not available for the card used in the transaction';
    const BAD_REQUEST_PAYMENT_AEPS_NOT_ENABLED_FOR_MERCHANT                     = 'Aeps transactions are not enabled for the merchant';
    const BAD_REQUEST_PAYMENT_UPI_NOT_ENABLED_FOR_MERCHANT                      = 'UPI transactions are not enabled for the merchant';
    const BAD_REQUEST_PAYMENT_BANK_TRANSFER_NOT_ENABLED_FOR_MERCHANT            = 'Bank transfers are not enabled for the merchant';
    const BAD_REQUEST_PAYMENT_BANK_NOT_PROVIDED                                 = 'Bank not provided for net banking payment';
    const BAD_REQUEST_PAYMENT_INVALID_BANK_CODE                                 = 'Bank code provided for net banking payment is invalid';
    const BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE                      = 'Account Balance is insufficient';
    const BAD_REQUEST_APP_TOKEN_ABSENT                                          = 'Customer not logged in';
    const BAD_REQUEST_APP_TOKEN_NOT_GLOBAL                                      = 'Global customer not logged in';
    const BAD_REQUEST_PAYMENT_WALLET_CUSTOMER_TOKEN_NOT_FOUND                   = 'Payment failed';
    const BAD_REQUEST_PAYMENT_CONTACT_INCORRECT_FORMAT                          = 'Contact number contains invalid characters, only digits and + symbol are allowed';
    const BAD_REQUEST_PAYMENT_CONTACT_INVALID_COUNTRY_CODE                      = 'Contact number contains invalid country code';
    const BAD_REQUEST_PAYMENT_CONTACT_TOO_SHORT                                 = 'Contact number should be at least 8 digits, including country code';
    const BAD_REQUEST_PAYMENT_CONTACT_TOO_LONG                                  = 'Contact number should not be greater than 15 digits, including country code';
    const BAD_REQUEST_PAYMENT_CONTACT_ONLY_INDIAN_ALLOWED                       = 'Contact number needs to be Indian.';
    const BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED                        = 'Card network not supported';
    const BAD_REQUEST_PAYMENT_CARD_INSUFFICIENT_BALANCE                         = 'Payment processing failed due to insufficient balance';
    const BAD_REQUEST_PAYMENT_CARD_DECLINED                                     = 'Card declined by bank';
    const BAD_REQUEST_PAYMENT_CARD_EXPIRED                                      = 'Card is expired';
    const BAD_REQUEST_PAYMENT_CARD_INVALID_EXPIRY_DATE                          = 'Expiry date is not valid';
    const BAD_REQUEST_PAYMENT_CARD_DETAILS_INVALID                              = 'Card details entered by the user are invalid.';
    const BAD_REQUEST_PAYMENT_CARD_ISSUING_BANK_PREVENTED_AUTHORIZATION         = 'Payment processing declined. The card issuing bank has prevented the payment from being authorized.';
    const BAD_REQUEST_PAYMENT_CARD_NUMBER_NOT_LEGITIMATE                        = 'The card number provided is not a legitimate one.';
    const BAD_REQUEST_PAYMENT_CARD_NUMBER_POSSIBLY_INVALID                      = 'The payment failed most probably due to an invalid card number';
    const BAD_REQUEST_PAYMENT_CARD_WITHDRAWAL_LIMITS_EXCEEDED                   = 'Payment processing failed because card\'s withdrawal amount limit has exceeded.';
    const BAD_REQUEST_PAYMENT_CARD_ISSUING_BANK_NOT_PERMITTING_PAYMENT          = 'The bank has declined the payment as this card cannot be used for this type of payment. Please use an alternate credit card for the purpose.';
    const BAD_REQUEST_PAYMENT_CARD_CVV_LENGTH_MUST_BE_THREE                     = 'The card cvv length should only be 3 digits';
    const BAD_REQUEST_PAYMENT_CARD_AMEX_CVV_LENGTH_MUST_BE_FOUR                 = 'The American Express card cvv length must be 4 digits';
    const BAD_REQUEST_PAYMENT_CARD_INTERNATIONAL_NOT_ALLOWED                    = 'International card is not allowed.';
    const BAD_REQUEST_PAYMENT_CARD_AUTHENTICATION_INVALID                       = 'Card authentication failed due to invalid response from gateway. Please retry or use another payment method';
    const BAD_REQUEST_PAYMENT_CARD_RECURRING_NOT_SUPPORTED                      = 'Recurring is not supported on this card';
    const BAD_REQUEST_PAYMENT_BLOCKED_DUE_TO_FRAUD                              = 'Payment was blocked because of fraud';
    const BAD_REQUEST_PAYMENT_FAILED_BECAUSE_SESSION_EXPIRED                    = 'Payment processing failed because session expired due to taking too much time. Please try the payment again.';
    const BAD_REQUEST_PAYMENT_TIMED_OUT                                         = 'Payment was not completed on time.';
    const BAD_REQUEST_PAYMENT_TIMED_OUT_AT_GATEWAY                              = 'Payment was not completed on time.';
    const BAD_REQUEST_PAYMENT_TIMED_OUT_AT_WALLET_PAYMENT_PAGE                  = 'Payment was not completed on time.';
    const BAD_REQUEST_PAYMENT_UPI_REQUEST_TIMED_OUT                             = 'Payment failed because upi request timed out.';
    const BAD_REQUEST_PAYMENT_REFUND_NOT_SUPPORTED                              = 'Refund is currently not supported for this payment method';
    const BAD_REQUEST_PAYMENT_PARTIAL_REFUND_NOT_SUPPORTED                      = 'Partial refund is currently not supported for this payment method';
    const BAD_REQUEST_PAYMENT_CAPTURE_AMOUNT_NOT_EQUAL_TO_AUTH                  = 'Capture amount must be equal to the amount authorized';
    const BAD_REQUEST_PAYMENT_CAPTURE_CURRENCY_MISMATCH                         = 'Capture request currency must be same as payment currency';
    const BAD_REQUEST_PAYMENT_BANK_NOT_ENABLED_FOR_MERCHANT                     = 'This bank is either not valid or is not enabled for the merchant';
    const BAD_REQUEST_PAYMENT_INVALID_MOBILE                                    = 'Payment failed because of invalid mobile number';
    const BAD_REQUEST_PAYMENT_INVALID_EMAIL                                     = 'Payment failed because of invalid email';
    const BAD_REQUEST_PAYMENT_WALLET_PER_DAY_LIMIT_EXCEEDED                     = 'Payment failed because daily limit of the wallet has exceeded';
    const BAD_REQUEST_PAYMENT_WALLET_PER_WEEK_LIMIT_EXCEEDED                    = 'Payment failed because weekly limit of the wallet has exceeded';
    const BAD_REQUEST_PAYMENT_WALLET_PER_MONTH_LIMIT_EXCEEDED                   = 'Payment failed because monthly limit of the wallet has been exceeded';
    const BAD_REQUEST_PAYMENT_WALLET_PER_PAYMENT_AMOUNT_CROSSED                 = 'Payment amount for wallet is above the limit';
    const BAD_REQUEST_PAYMENT_WALLET_BLOCKED_CARD                               = 'Card has been blocked by the wallet';
    const BAD_REQUEST_PAYMENT_WALLET_BLOCKED_MOBILE_NUMBER                      = 'Mobile number has been blocked by the wallet';
    const BAD_REQUEST_PAYMENT_WALLET_BLOCKED_IP_ADDRESS                         = 'Customer IP address has been blocked by the wallet';
    const BAD_REQUEST_PAYMENT_WALLET_BLOCKED_CUSTOMER                           = 'Customer has been blocked by wallet';
    const BAD_REQUEST_PAYMENT_WALLET_MAX_WRONG_ATTEMPT_LIMIT_CROSSED            = 'Maximum wrong attempt limit crossed for wallet';
    const BAD_REQUEST_PAYMENT_WALLET_MAX_AMOUNT_LIMIT_CROSSED_FOR_CUSTOMER      = 'Maximum wallet payment amount limit has been crossed for the customer';
    const BAD_REQUEST_PAYMENT_WALLET_NOT_ACTIVATED                              = 'Wallet has not been activated for the customer';
    const BAD_REQUEST_PAYMENT_WALLET_USER_DOES_NOT_EXIST                        = 'Wallet user does not exist';
    const BAD_REQUEST_PAYMENT_WALLET_ACCOUNT_INVALID_MOBILE                     = 'Wallet account seems to be registered with invalid mobile number';
    const BAD_REQUEST_PAYMENT_WALLET_ACCOUNT_INVALID_CREDENTIALS                = 'No Wallet Account is associated with the given email and mobile number combination';
    const BAD_REQUEST_PAYMENT_WALLET_NO_USER_WITH_CELL                          = 'No Wallet Account is associated with specified contact number';
    const BAD_REQUEST_PAYMENT_WALLET_ACCOUNT_ALREADY_EXIST_WITH_EMAIL           = 'Provided email is already associated with an existing wallet account';
    const BAD_REQUEST_PAYMENT_WALLET_ACCOUNT_ALREADY_EXIST_WITH_CONTACT         = 'Provided contact is already associated with an existing wallet account';
    const BAD_REQUEST_PAYMENT_WALLET_ACCOUNT_INACTIVE                           = 'Wallet account associated is inactive.';
    const BAD_REQUEST_PAYMENT_WALLET_AUTHENTICATION_FAILED                      = 'Payment failed due to wallet authentication failure';
    const BAD_REQUEST_PAYMENT_WALLET_INSUFFICIENT_BALANCE                       = 'Payment failed due to insufficient balance in wallet';
    const BAD_REQUEST_PAYMENT_WALLET_CONTACT_PAYUMONEY                          = 'Payment failed. Please contact care@payumoney.com using your registered email';
    const BAD_REQUEST_AIRTEL_MONEY_ACCOUNT_BLOCKED                              = 'Payment failed as airtel money account has been blocked. Please reset mPIN or call 400 for assistance';
    const BAD_REQUEST_AIRTEL_MONEY_RESET_MPIN                                   = 'Payment failed as airtel money mPIN has not been changed. Please call 121 to reset your mPIN.';
    const BAD_REQUEST_CONTACT_AIRTEL_MONEY_CUSTOMER_CARE_FOR_REFUND             = 'Refund failed. Please contact airtel money customer care.';
    const BAD_REQUEST_PAYMENT_TOPUP_INVALID_WALLET_TOKEN                        = 'Payment failed';
    const BAD_REQUEST_PAYMENT_UPI_INVALID_VPA                                   = 'Invalid VPA. Please enter a valid Virtual Payment Address';
    const BAD_REQUEST_UNMAPPED_VPA                                              = 'This VPA is not mapped to any bank account.';
    const BAD_REQUEST_INVALID_P2P                                               = 'P2p fields are invalid.';
    const BAD_REQUEST_VPA_DOESNT_EXIST                                          = 'VPA does not exist.';
    const BAD_REQUEST_PAYMENT_UPI_APP_NOT_SUPPORTED                             = 'Your UPI application is facing issues with handling collect requests. Please try again later';
    const BAD_REQUEST_PAYMENT_ORDER_AMOUNT_MISMATCH                             = 'Payment amount provided does not match with the amount in order';
    const BAD_REQUEST_PAYMENT_AMOUNT_MORE_THAN_ORDER_AMOUNT_DUE                 = 'Payment amount is greater than the amount due for order';
    const BAD_REQUEST_PAYMENT_ORDER_CURRENCY_MISMATCH                           = 'Payment currency provided does not match with the currency in order';
    const BAD_REQUEST_PAYMENT_ORDER_ALREADY_PAID                                = 'Payment already done for this order.';
    const BAD_REQUEST_REFUND_FAILED                                             = 'Refund failed';
    const BAD_REQUEST_PAYMENT_ALREADY_REFUNDED                                  = 'Refund failed';
    const BAD_REQUEST_REFUND_NOT_ENOUGH_BALANCE                                 = 'Your account does not have enough balance to carry out the refund operation. You can add funds to your account from your Razorpay dashboard or capture new payments.';
    const BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE                                 = 'Your account does not have enough balance to carry out the payout operation. You can add funds to your account from your Razorpay dashboard or capture new payments.';
    const BAD_REQUEST_MERCHANT_FUNDS_ON_HOLD                                    = 'This operation is not allowed. Please contact Razorpay support for details.';
    const BAD_REQUEST_MERCHANT_FUNDS_ALREADY_ON_HOLD                            = 'The merchant funds are already on hold';
    const BAD_REQUEST_MERCHANT_FUNDS_ALREADY_RELEASED                           = 'The merchant funds are already released';
    const BAD_REQUEST_RECEIPT_EMAILS_ALREADY_ENABLED                            = 'The merchant receipt emails are already enabled';
    const BAD_REQUEST_RECEIPT_EMAILS_ALREADY_DISABLED                           = 'The merchant receipt emails are already disabled';
    const BAD_REQUEST_INTERNATIONAL_ALREADY_ENABLED                             = 'Merchant international is already enabled';
    const BAD_REQUEST_INTERNATIONAL_ALREADY_DISABLED                            = 'Merchant international is already disabled';
    const BAD_REQUEST_MERCHANT_INVALID                                          = 'The payment has been rejected by the gateway.';
    const BAD_REQUEST_MERCHANT_RECURRING_PAYMENTS_NOT_SUPPORTED                 = 'Recurring payments are not supported for this merchant.';
    const BAD_REQUEST_UNABLE_TO_AUTHORIZE_PAYMENT                               = 'This payment could not be authorized by the processing bank.';
    const BAD_REQUEST_KEY_EXPIRED                                               = 'Key is expired';
    const BAD_REQUEST_KEY_EXPIRING_SOON                                         = 'Key is already set to expire soon';
    const BAD_REQUEST_KEY_OF_DEMO_ACCOUNT                                       = 'Operation failed for the key because it\'s of a demo account';
    const BAD_REQUEST_PAYMENT_CAPTURE_ONLY_AUTHORIZED                           = 'Only payments which have been authorized and not yet captured can be captured';
    const BAD_REQUEST_PAYMENT_CANCEL_ONLY_CREATED                               = 'Only payments which are just created can be cancelled';
    const BAD_REQUEST_NOTES_TOO_MANY_KEYS                                       = 'Number of fields in notes should be less than or equal to 15';
    const BAD_REQUEST_NOTES_VALUE_CANNOT_BE_ARRAY                               = 'Notes values themselves should not be an array';
    const BAD_REQUEST_NOTES_KEY_TOO_LARGE                                       = 'Notes key cannot be greater 255 characters';
    const BAD_REQUEST_NOTES_VALUE_TOO_LARGE                                     = 'Notes value cannot be greater 255 characters';
    const BAD_REQUEST_NOTES_SHOULD_BE_ARRAY                                     = 'Notes should be provided as a dictionary';
    const BAD_REQUEST_UNAUTHORIZED_BASICAUTH_EXPECTED                           = 'Please provide your api key for authentication purposes.';
    const BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY                              = 'The api key provided is invalid';
    const BAD_REQUEST_UNAUTHORIZED_INVALID_API_SECRET                           = 'The api secret provided is invalid';
    const BAD_REQUEST_UNAUTHORIZED_INVALID_ACCOUNT_ID                           = 'The Account ID provided is invalid';
    const BAD_REQUEST_UNAUTHORIZED_SECRET_NOT_PROVIDED                          = 'Please provide api secret';
    const BAD_REQUEST_UNAUTHORIZED_SECRET_SENT_ON_PUBLIC_ROUTE                  = 'Please do not provide your secret on public sided requests';
    const BAD_REQUEST_UNAUTHORIZED_API_KEY_NOT_PROVIDED                         = 'Please provide your Razorpay Api Key Id';
    const BAD_REQUEST_UNAUTHORIZED_API_KEY_EXPIRED                              = 'The api key provided by you has expired and cannot be used. Please use correct key and secret.';
    const BAD_REQUEST_UNAUTHORIZED                                              = 'Authentication failed';
    const BAD_REQUEST_UNAUTHORIZED_OAUTH_TOKEN_INVALID                          = 'The OAuth token used in the request was invalid or had expired';
    const BAD_REQUEST_UNAUTHORIZED_OAUTH_SCOPE_INVALID                          = 'The OAuth token used does not have sufficient permissions for this request';
    const BAD_REQUEST_PRICING_ID_REQURED                                        = 'Pricing plan id is required';
    const BAD_REQUEST_PRICING_PLAN_ALREADY_EXISTS                               = 'Pricing plan name already exists. Are you trying a pricing plan rule instead?';
    const BAD_REQUEST_PRICING_RATE_NOT_DEFINED                                  = 'One of percent_rate and fixed_rate must be present';
    const BAD_REQUEST_PRICING_GATEWAY_REQUIRED                                  = 'This plan has a gateway set. Please provide it in input';
    const BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED                              = 'The new rule matches with an active existing rule';
    const BAD_REQUEST_PRICING_PLAN_WITH_SAME_NAME_EXISTS                        = 'Pricing plan name already exists. Are you trying a pricing plan rule instead?';
    const BAD_REQUEST_PRICING_NOT_DEFINED_FOR_MERCHANT                          = 'The merchant does not have pricing assigned';
    const BAD_REQUEST_PRICING_FIELD_NOT_REQUIRED_FOR_NB                         = 'The field should be null for net-banking';
    const BAD_REQUEST_PRICING_RULE_FOR_AMEX_NOT_PRESENT                         = 'Amex pricing rule not present for merchant';
    const BAD_REQUEST_PRICING_RULE_FOR_AMOUNT_RANGE_OVERLAP                     = 'Pricing rule amount range collides with another existing rule\'s amount range.';
    const BAD_REQUEST_UNKNOWN_SCHEDULE                                          = 'Schedule not found in database.';
    const BAD_REQUEST_INVALID_SCHEDULE                                          = 'Schedule cannot be created, it is invalid.';
    const BAD_REQUEST_SCHEDULE_REQUIRED                                         = 'Mandatory param: Schedule not given.';
    const BAD_REQUEST_SCHEDULE_INVALID_PERIOD                                   = 'Invalid period, must be among hourly, daily, weekly, monthly-date, and monthly-week';
    const BAD_REQUEST_SCHEDULE_ANCHOR_NOT_PERMITTED                             = 'Setting anchor is not permitted for the schedule.';
    const BAD_REQUEST_SCHEDULE_HOURLY_HOUR_NOT_PERMITTED                        = 'Setting hour is not permitted for hourly schedules';
    const BAD_REQUEST_SCHEDULE_HOURLY_WITHOUT_INTERVAL                          = 'Hourly schedules require an interval to be set.';
    const BAD_REQUEST_SCHEDULE_IN_USE                                           = 'Cannot delete a schedule that is currently in use by one or more merchants.';
    const BAD_REQUEST_VIRTUAL_ACCOUNT_INVALID_RECEIVER_TYPES                    = 'One or more of the given receiver types is invalid.';
    const BAD_REQUEST_VIRTUAL_ACCOUNT_IDENTICAL_DESCRIPTOR                      = 'An active virtual account with the same descriptor already exists for your account.';
    const BAD_REQUEST_VIRTUAL_ACCOUNT_UNAVAILABLE                               = 'A virtual account with this descriptor is unavailable at this time.';
    const BAD_REQUEST_VIRTUAL_ACCOUNT_DESCRIPTOR_SANS_HANDLE                    = 'Descriptor field cannot be used as merchant handle is not set for your account.';
    const BAD_REQUEST_MERCHANT_ALREADY_ACTIVATED                                = 'The merchant has already been activated';
    const BAD_REQUEST_MERCHANT_NOT_ACTIVATED                                    = 'The merchant has not been activated. This action can only be taken for activated merchants';
    const BAD_REQUEST_MERCHANT_UNARCHIVE_BEFORE_ACTIVATION                      = 'The merchant must be unarchived before being activated.';
    const BAD_REQUEST_MERCHANT_CANNOT_BE_ARCHIVED                               = 'The merchant cannot be archived';
    const BAD_REQUEST_MERCHANT_ALREADY_ARCHIVED                                 = 'The merchant has already been archived.';
    const BAD_REQUEST_MERCHANT_NOT_ARCHIVED                                     = 'The merchant has not been archived. This action can only be taken for archived merchants';
    const BAD_REQUEST_MERCHANT_ALREADY_SUSPENDED                                = 'The merchant has already been suspended';
    const BAD_REQUEST_MERCHANT_NOT_SUSPENDED                                    = 'The merchant has not been suspended. This action can only be taken for suspended merchants';
    const BAD_REQUEST_MERCHANT_ACTION_NOT_SUPPORTED                             = 'The input action is not supported for the merchant';
    const BAD_REQUEST_MERCHANT_DETAIL_DOES_NOT_EXISTS                           = 'Merchant details does not exists';
    const BAD_REQUEST_MERCHANT_ALREADY_LIVE                                     = 'The merchant is already live';
    const BAD_REQUEST_MERCHANT_NOT_LIVE                                         = 'The merchant is not live currently';
    const BAD_REQUEST_MERCHANT_NOT_LIVE_ACTION_DENIED                           = 'There is a temporary block placed on the account currently because of which new payment operations are put on hold. If you are seeing this message unexpectedly, please contact the site admin regarding the issue.';
    const BAD_REQUEST_MERCHANT_NO_TERMINAL_ASSIGNED                             = 'The merchant has no pricing assigned';
    const BAD_REQUEST_MERCHANT_KEY_ALREADY_CREATED                              = 'The merchant keys have already been created';
    const BAD_REQUEST_MERCHANT_NOT_ACTIVATED_KEY_CREATE_FAILED                  = 'The merchant keys cannot be created since account is not activated yet.';
    const BAD_REQUEST_MERCHANT_NO_BANK_ACCOUNT_FOUND                            = 'The merchant has not yet provided his bank account details';
    const BAD_REQUEST_MERCHANT_BANK_ACCOUNT_ALREADY_PROVIDED                    = 'The merchant already has provided a bank account';
    const BAD_REQUEST_GATEWAY_TERMINAL_MAX_LIMIT_REACHED                        = 'Max terminal count limit reached for this merchant';
    const BAD_REQUEST_GATEWAY_MERCHANT_ID_EXISTS                                = 'A record with same gateway merchant id (mid) exists';
    const BAD_REQUEST_MERCHANT_TERMINAL_EXISTS_FOR_GATEWAY                      = 'A terminal for this gateway for this merchant already exists';

    const BAD_REQUEST_PAYMENT_VERIFICATION_FAILED                               = 'Payment verification with gateway failed';
    const BAD_REQUEST_PAYMENT_DECLINED_BY_CLICKING_CANCEL                       = 'Payment declined by gateway. Most probably due to customer clicking the cancel button on 3dSecure page';
    const BAD_REQUEST_PAYMENT_DECLINED_BY_GATEWAY                               = 'Payment declined';
    const BAD_REQUEST_PAYMENT_DECLINED_BY_BANK                                  = 'Payment declined by bank';
    const BAD_REQUEST_PAYMENT_NO_RESPONSE_RECEIVED_FROM_BANK                    = 'Payment declined due to not receiving timely response from bank';
    const BAD_REQUEST_PAYMENT_BANK_SYSTEM_ERROR                                 = 'Payment failed due to error in the bank system';
    const BAD_REQUEST_PAYMENT_FAILED_MAYBE_DUE_TO_INVALID_INPUT                 = 'Payment processing failed most probably due to invalid card input';
    const BAD_REQUEST_PAYMENT_CANCELLED                                         = 'Payment processing cancelled';
    const BAD_REQUEST_PAYMENT_DECLINED_CONTACT_ISSUING_BANK                     = 'Payment processing declined by card issuing bank. Please contact issuing bank to determine reason.';
    const BAD_REQUEST_PAYMENT_NETBANKING_CANCELLED_BY_USER                      = 'Payment processing via netbanking cancelled by user by clicking cancel on bank transfer page';
    const BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED                     = 'Payment processing failed due to 3dsecure or OTP authentication failure';
    const BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK                      = 'Payment processing failed by bank due to risk';
    const BAD_REQUEST_PAYMENT_DECLINED_BY_GATEWAY_DUE_TO_RISK                   = 'Payment processing failed by gateway due to risk';
    const BAD_REQUEST_PAYMENT_CARD_NOT_ENROLLED_FOR_3DSECURE                    = 'Payment processing failed because card is not enrolled for the required 3dsecure authentication';
    const BAD_REQUEST_PAYMENT_OTP_VALIDATION_ATTEMPT_LIMIT_EXCEEDED             = 'Payment processing failed because OTP validation attempts limit exceeded';
    const BAD_REQUEST_PAYMENT_OTP_INCORRECT                                     = 'Payment processing failed because of incorrect OTP';
    const BAD_REQUEST_PAYMENT_OTP_EXPIRED                                       = 'Payment processing failed because of expired OTP';
    const BAD_REQUEST_PAYMENT_XML_SIGNATURE_ERROR                               = 'Payment processing failed due to invalid response returned by gateway.';
    const BAD_REQUEST_PAYMENT_ABORTED                                           = 'Payment processing aborted';
    const BAD_REQUEST_PAYMENT_MISSING_DATA                                      = 'One or more required fields are missing';
    const BAD_REQUEST_PAYMENT_AMOUNT_GREATER_THAN_REFUNDED                      = 'Total amount passed is more than the Return/Void amount.';
    const BAD_REQUEST_PAYMENT_CARD_HOLDER_NOT_PERMITTED_TXN                     = 'Transaction not permitted to cardholder';
    const BAD_REQUEST_PAYMENT_CARD_TYPE_INVALID                                 = 'Invalid card type.';
    const BAD_REQUEST_PAYMENT_INVALID_AMOUNT_OR_CURRENCY                        = 'Invalid amount or currency.';
    const BAD_REQUEST_PAYMENT_INVALID_CAPTURE                                   = 'No approved preauth transaction was found.';
    const BAD_REQUEST_PAYMENT_INVALID_FORMAT                                    = 'Format error.';
    const BAD_REQUEST_PAYMENT_INVALID_STATUS                                    = 'Payment status is not valid for the operation';
    const BAD_REQUEST_PAYMENT_INVALID_TRANSACTION_DATE                          = 'Invalid transaction date.';
    const BAD_REQUEST_PAYMENT_MAX_TRANSACTIONS_PER_ORDER_EXCEEDED               = 'The maximum number of transactions per order has been exceeded';
    const BAD_REQUEST_PAYMENT_PIN_ATTEMPTS_EXCEEDED                             = 'Max number of PIN retries exceeded.';
    const BAD_REQUEST_PAYMENT_PIN_INCORRECT                                     = 'Incorrect Pin';
    const BAD_REQUEST_PAYMENT_TERMINAL_STATE_CODE_EXCEEDED_LENGTH               = 'This transaction is not permitted by gateway.';
    const BAD_REQUEST_PAYMENT_TXN_NOT_PUSHED_TO_NET_BANKING                     = 'Transaction was not posted to Net Banking.';
    const BAD_REQUEST_PAYMENT_TXN_REJECTED_FROM_NET_BANKING                     = 'Transaction was not posted to Net Banking.';
    const BAD_REQUEST_PAYMENT_VOID_NOT_SUPPORTED                                = 'Void is not supported for refund transaction on this endpoint.';
    const BAD_REQUEST_INVALID_PARAMETERS                                        = 'One or more fields have invalid data';
    const BAD_REQUEST_PAYMENT_PARTIAL_AMOUNT_APPROVED                           = 'Payment declined because partial amount was approved';
    const BAD_REQUEST_ORDER_DOES_NOT_EXIST                                      = 'Order does not exist.';
    const BAD_REQUEST_ORDER_EXISTS                                              = 'This order already exists in the gateway database.';
    const BAD_REQUEST_ORDER_INVALID_OFFER                                       = 'Offer applied not valid for order';
    const BAD_REQUEST_UNSUPPORTED_CHARACTER_SET                                 = 'Error occurred because of invalid data';
    const BAD_REQUEST_UNAUTHORIZED_INVALID_HASH                                 = 'Hash Data is invalid.';

    const BAD_REQUEST_GATEWAY_REFUND_ABSENT                                     = 'Refund not done on the gateway side.';
    const BAD_REQUEST_INVALID_GATEWAY                                           = 'Invalid gateway provided for the request';
    const BAD_REQUEST_PAYMENT_SUBSCRIPTION_NOT_RECURRING                        = 'Recurring is not set for the subscription payment';
    const BAD_REQUEST_SUBSCRIPTION_TOKEN_ALREADY_ASSOCIATED                     = 'The subscription already has a token associated with it';
    const BAD_REQUEST_SUBSCRIPTION_TOKEN_NOT_ASSOCIATED                         = 'Payment cannot be authorized since subscription does not have any token associated';
    const BAD_REQUEST_SUBSCRIPTION_TOTAL_COUNT_EXCEEDED                         = 'Subscription has already reached its total count of charges';
    const BAD_REQUEST_SUBSCRIPTION_EXPIRED_OR_CANCELLED                         = 'The subscription has been expired or cancelled.';
    const BAD_REQUEST_SUBSCRIPTION_NOT_IN_ACTIVE_OR_HALTED_STATE                = 'The subscription should be in either active or halted state to charge an on_hold invoice';
    const BAD_REQUEST_SUBSCRIPTION_INVOICE_CANNOT_BE_CHARGED                    = 'This invoice of the subscription cannot be charged.';
    const BAD_REQUEST_SUBSCRIPTION_2FA_NOT_ALLOWED                              = 'Customer payment not allowed for the subscription at this stage.';
    const BAD_REQUEST_SUBSCRIPTION_CHANGE_CARD_NOT_ALLOWED                      = 'Cannot change card for the subscription at this state';
    const BAD_REQUEST_SUBSCRIPTION_CUSTOMER_NOT_FOUND                           = 'Could not find the customer for the subscription';
    const BAD_REQUEST_SUBSCRIPTION_CUSTOMER_ID_SENT_IN_INPUT                    = 'customer_id should not be sent in the input for subscription payment';
    const BAD_REQUEST_SUBSCRIPTION_SAVE_CARD_DISABLED                           = 'Subscription payment cannot be made with Flash Checkout disabled';
    const BAD_REQUEST_SUBSCRIPTION_PAYMENT_WITHOUT_SAVING                       = 'Subscription payment cannot be made without saving the card';
    const BAD_REQUEST_SUBSCRIPTION_ANOTHER_OPERATION_IN_PROGRESS                = 'Request failed because another subscription operation is in progress';
    const BAD_REQUEST_SUBSCRIPTION_INVALID_STATUS                               = 'Invalid status passed in the query params';

    const BAD_REQUEST_INVOICE_STATUS_UNAVAILABLE                                = 'Invoice status cannot be retrieved now';
    const BAD_REQUEST_PAYMENT_NOT_AUTHORIZED                                    = 'Payment is not in authorized state';
    const BAD_REQUEST_NOT_CARD_PAYMENT                                          = 'Payment was not done using card';
    const BAD_REQUEST_INVALID_MESSAGE_KEYWORD                                   = 'Invalid keyword sent in the request';
    const BAD_REQUEST_MISSING_FIELDS_MESSAGE                                    = 'Some fields are missing in the request';
    const BAD_REQUEST_CUSTOMER_ID_MISSING                                       = 'Sending customer ID is mandatory';
    const BAD_REQUEST_BANK_ACCOUNT_ID_MISSING                                   = 'Sending bank account id is mandatory';
    const BAD_REQUEST_DUPLICATE_VPA                                             = 'Duplicate VPA address, try a different username.';
    const BAD_REQUEST_END_AT_AND_TOTAL_COUNT_SENT                               = 'Either end_at or total_count should be sent and not both.';
    const BAD_REQUEST_INVALID_AUTH_TRANSACTION_AMOUNT                           = 'The amount does not match with the expected amount for the first transaction. It might have been tampered.';
    const BAD_REQUEST_SUBSCRIPTION_CURRENT_TIME_PAST_START_TIME                 = 'Subscription\'s start time is past the current time. Cannot do an auth transaction now.';

    const BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD                                    = 'Payment declined because it didn\'t pass all risk checks';
    const BAD_REQUEST_CARD_AVS_FAILED                                           = 'Payment processing failed because address validation failed';
    const BAD_REQUEST_CARD_STOLEN_OR_LOST                                       = 'Payment failed because stolen or lost card is used';
    const BAD_REQUEST_CARD_ISSUING_BANK_UNAVAILABLE                             = 'Payment failed because issuing bank is unavailable';
    const BAD_REQUEST_CARD_INACTIVE                                             = 'Payment failed because given card is inactive';
    const BAD_REQUEST_CARD_CREDIT_LIMIT_REACHED                                 = 'Payment failed because card has reached it\'s limit';
    const BAD_REQUEST_CARD_FROZEN                                               = 'Payment failed because given card is inactive';
    const BAD_REQUEST_CARD_DAILY_LIMIT_REACHED                                  = 'Payment failed because card has reached it\'s limit';
    const BAD_REQUEST_CARD_BILL_SHIP_MISMATCH                                   = 'Payment failed';

    const BAD_REQUEST_PAYMENT_ORDER_ID_REQUIRED                                 = 'Order id is mandatory for payment';
    const BAD_REQUEST_ORDER_METHOD_REQUIRED_FOR_MERCHANT                        = 'Order payment method is mandatory for this merchant';
    const BAD_REQUEST_ORDER_ACCOUNT_NUMBER_REQUIRED_FOR_MERCHANT                = 'Account number is mandatory for this merchant';
    const BAD_REQUEST_ORDER_ACCOUNT_NUMBER_INCORRECT_LENGTH                     = 'Account number is of incorrect length for this bank.';
    const BAD_REQUEST_ORDER_BANK_INVALID                                        = 'Bank code provided is invalid.';

    const BAD_REQUEST_CUSTOMER_ALREADY_EXISTS                                   = 'Customer already exists for the merchant';
    const BAD_REQUEST_CUSTOMER_CONTACT_REQUIRED                                 = 'Customer contact number is not set';
    const BAD_REQUEST_CUSTOMER_CARD_ALREADY_EXISTS                              = 'Card already exists for the customer';
    const BAD_REQUEST_CUSTOMER_BANK_ALREADY_EXISTS                              = 'Bank already exists for the customer';
    const BAD_REQUEST_CUSTOMER_WALLET_ALREADY_EXISTS                            = 'Wallet already exists for the customer';
    // Local customer cannot be duplicated because the parent is not a global customer.
    const BAD_REQUEST_CUSTOMER_DUPLICATE_NOT_GLOBAL                             = 'Customer cannot be created';
    const BAD_REQUEST_GLOBAL_CUSTOMER_MISMATCH                                  = 'Global customer does not match with the customer found';

    const BAD_REQUEST_OTP_MAXIMUM_ATTEMPTS_REACHED                              = 'OTP verification failed because attempt threshold has been reached';
    const BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED                                 = 'SMS sending failed because threshold has been reached. Please try again later.';
    const BAD_REQUEST_INCORRECT_OTP                                             = 'Verification failed because of incorrect OTP.';
    const BAD_REQUEST_SMS_FAILED                                                = 'SMS sending failed.';

    const BAD_REQUEST_LOGO_NOT_PRESENT                                          = 'The input does not contain a file named logo';
    const BAD_REQUEST_MERCHANT_LOGO_TOO_BIG                                     = 'Size of the logo is too big. Upload a smaller file size.';
    const BAD_REQUEST_MERCHANT_LOGO_NOT_SQUARE                                  = 'The height and width of the logo are not the same. Upload a square image.';
    const BAD_REQUEST_MERCHANT_LOGO_NOT_IMAGE                                   = 'The image type is not jpg, jpeg or png.';
    const BAD_REQUEST_MERCHANT_LOGO_TOO_SMALL                                   = 'The dimensions of the image are too small. Minimum dimensions should be 256x256';

    const BAD_REQUEST_SHARED_TERMINAL_CANNOT_BE_COPIED                          = 'Shared terminal cannot be copied';
    const BAD_REQUEST_SHARED_TERMINAL_MERCHANT_CANNOT_BE_CHANGED                = 'Shared terminal merchant cannot be changed';
    const BAD_REQUEST_SUB_MERCHANT_ALREADY_ASSIGNED_TO_TERMINAL                 = 'Sub-Merchant already assigned to terminal';

    const BAD_REQUEST_RECONCILIATION                                            = 'Error occurred during reconciliation';
    const BAD_REQUEST_INVALID_COUNTRY                                           = 'Invalid country code/name passed';

    const BAD_REQUEST_GATEWAY_CANNOT_TOPUP                                      = 'Payment processing failed because topup cannot be done';
    const BAD_REQUEST_INVALID_XML_SCHEMA                                        = 'Payment failed because of invalid data';

    const BAD_REQUEST_INVALID_CHECKOUT_ID                                       = 'The payment request has invalid checkout_id';
    const BAD_REQUEST_INVALID_PLATFORM                                          = 'The payment request has invalid platform';
    const BAD_REQUEST_INVALID_LIBRARY                                           = 'The payment request has invalid library';
    const BAD_REQUEST_INVALID_BROWSER                                           = 'The payment request has invalid browser';
    const BAD_REQUEST_INVALID_OS                                                = 'The payment request has invalid operating system';
    const BAD_REQUEST_INVALID_DEVICE                                            = 'The payment request has invalid device';
    const BAD_REQUEST_INVALID_INTEGRATION                                       = 'The payment required has invalid integration';
    const BAD_REQUEST_PAYMENT_ALREADY_REFUND_INITIATED                          = 'Refund already initiated';
    const BAD_REQUEST_PAYMENT_PROBLEM_IN_UPDATING                               = 'Problem in updating payment';
    const BAD_REQUEST_PAYMENT_FEES_GREATER_THAN_AMOUNT                          = 'The fees calculated for payment is greater than the payment amount. Please provide a higher amount';
    const BAD_REQUEST_PAYMENT_CANNOT_BE_CANCELLED                               = 'Payment created long back and cannot be cancelled now';
    const BAD_REQUEST_PAYMENT_ALREADY_UNDER_DISPUTE                             = 'Payment already has an open dispute';
    const BAD_REQUEST_DISPUTE_AMOUNT_GREATER_THAN_PAYMENT_AMOUNT                = 'Disputed amount cannot be greater than payment amount';
    const BAD_REQUEST_CANNOT_UPDATE_CLOSED_DISPUTE                              = 'This dispute is already closed and cannot be updated';

    // batch processor related error codes
    const BAD_REQUEST_BATCH_FILE_INVALID_TYPE                                   = 'Incorrect type is used for the uploaded file';
    const BAD_REQUEST_BATCH_FILE_INVALID_PAYMENT_ID                             = 'Payment Id is not set in the uploaded file';
    const BAD_REQUEST_BATCH_FILE_INVALID_AMOUNT                                 = 'Amount is not set in the uploaded file';
    const BAD_REQUEST_BATCH_FILE_INVALID_HEADERS                                = 'The uploaded file has invalid headers';
    const BAD_REQUEST_BATCH_FILE_EMPTY                                          = 'The uploaded file does not have any entries';
    const BAD_REQUEST_BATCH_FILE_DUPLICATE_PAYMENT_ID                           = 'The file should not have multiple entries for the same Payment Id';
    const BAD_REQUEST_BATCH_PAYMENT_LINK_FILE_ERRORS                            = 'The uploaded batch payment link file does not contain proper values';
    const BAD_REQUEST_BATCH_FILE_ALREADY_PROCESSED                              = 'The uploaded file is already processed';
    const BAD_REQUEST_BATCH_ANOTHER_OPERATION_IN_PROGRESS                       = 'Request failed because another operation on the batch is in progress';
    const BAD_REQUEST_BATCH_FILE_EXCEED_LIMIT                                   = 'The uploaded file exceeds the number of entries allowed';

    const BAD_REQUEST_SETTLEMENT_ANOTHER_OPERATION_IN_PROGRESS                  = 'Request failed because another settlement operation in progress';
    const BAD_REQUEST_SETTLEMENT_RECONCILIATION_IN_PROGRESS                     = 'Request failed because another settlement reconciliation operation in progress';

    const BAD_REQUEST_INVALID_MERCHANT_INVOICE_NUMBER                           = 'Invalid Invoice Number.';

    const BAD_REQUEST_PAYOUT_ANOTHER_OPERATION_IN_PROGRESS                      = 'Request failed because another payout operation in progress';

    const BAD_REQUEST_PERMISSION_ERROR                                          = 'Permissions not found for this request/route';
    const BAD_REQUEST_INVALID_MAILGUN_WEBHOOK_TYPE                              = 'Invalid type specified for callback';
    const BAD_REQUEST_INVALID_MAILGUN_SIGNATURE                                 = 'Mailgun signature validation failed';
    const BAD_REQUEST_BANK_REQUIRED_WITH_ACCOUNT_NUMBER                         = 'Bank code should be provided in input if account number is sent';

    const BAD_REQUEST_PASSWORD_EXPIRED                                          = 'Account password has expired. Please contact administrator';
    const BAD_REQUEST_DUPLICATE_INVOICE_RECEIPT                                 = 'Duplicate value for receipt in invoice';
    const BAD_REQUEST_INVOICE_EXPIRE_FAILED                                     = 'Invoice expiry failed as payment exists or is in progress for this invoice.';
    const BAD_REQUEST_INVOICE_FEE_BEARER_CUSTOMER                               = 'Invoices disabled because fee bearer is customer';
    const BAD_REQUEST_PAYMENT_LINK_BATCH_ISSUED_ALREADY                         = 'Some/all payment links of given batch has been issued already';

    const BAD_REQUEST_API_KEY_NOT_PRESENT                                       = 'The operation cannot be performed without an API key being generated';
    const BAD_REQUEST_ITEM_OPERATION_NOT_ALLOWED                                = 'Cannot edit/delete an item with which invoices have been created already';
    const BAD_REQUEST_ITEM_INACTIVE                                             = 'Item cannot be used as it is inactive';
    const BAD_REQUEST_ITEM_EDIT_NOT_ALLOWED                                     = 'You can not edit/delete an item with which invoices have been created already';
    const BAD_REQUEST_INCOMPATIBLE_ITEM_TYPE                                    = 'Can only reuse an item of the same item type';
    const BAD_REQUEST_INVALID_ITEM_TAX_DETAILS                                  = 'Tax details provided for line item is invalid';
    const BAD_REQUEST_LINK_TYPE_HAS_NO_TAXATION                                 = 'Payment link does not support taxation';

    const BAD_REQUEST_MERCHANT_UNEDITABLE_FEATURE                               = 'You cannot change the value of this feature';
    const BAD_REQUEST_MERCHANT_FEATURE_UNEDITABLE_IN_LIVE                       = 'You cannot enable/disable features in live mode';
    const BAD_REQUEST_MERCHANT_FEATURE_ALREADY_ASSIGNED                         = 'The given feature is already assigned to the merchant';

    const BAD_REQUEST_INVALID_ADMIN_EMAIL                                       = 'Email provided is not a valid email';
    const BAD_REQUEST_INVALID_ADMIN_EMAIL_HOSTNAME                              = 'Email provided does not have the correct hostname';
    const BAD_REQUEST_AUTHENTICATION_FAILED                                     = 'Authentication failed';

    const BAD_REQUEST_PAYMENT_TRANSFER_AMOUNT_GREATER_THAN_CAPTURED             = 'The sum of amount requested for transfer is greater than the captured amount';
    const BAD_REQUEST_PAYMENT_TRANSFER_AMOUNT_GREATER_THAN_UNTRANSFERRED        = 'The total transfer amount provided is greater than the amount not transferred';
    const BAD_REQUEST_PAYMENT_TRANSFER_NOT_ENOUGH_BALANCE                       = 'Your account does not have enough balance to carry out the transfer operation. You can add funds to your account from your Razorpay dashboard or capture new payments.';
    const BAD_REQUEST_PAYMENT_TRANSFER_ENTITIES_NOT_SET                         = 'Payment transfer entities provided are invalid or not set';
    const BAD_REQUEST_PAYMENT_TRANSFER_MORE_THAN_ONE_CUSTOMER                   = 'Payment cannot be transferred to more than one customer';
    const BAD_REQUEST_PAYMENT_TRANSFER_MULTIPLE_ENTITY_TYPES_GIVEN              = 'Payment cannot be transferred to multiple types of entities';
    const BAD_REQUEST_PAYMENT_TRANSFER_CURRENCY_MISMATCH                        = 'Transfer request currency must be same as payment currency';

    const BAD_REQUEST_TRANSFER_INVALID_ACCOUNT_ID                               = 'Account ID provided for transfer is invalid';
    const BAD_REQUEST_TRANSFER_ACCOUNT_NOT_ACTIVATED                            = 'The account needs to be activated by Razorpay before you can make transfers in live mode';
    const BAD_REQUEST_TRANSFER_REVERSAL_AMOUNT_GREATER_THAN_TRANSFERRED         = 'The reversal amount provided is greater than amount transferred';
    const BAD_REQUEST_TRANSFER_REVERSAL_AMOUNT_GREATER_THAN_UNREVERSED          = 'The reversal amount provided is greater than the amount not reversed';
    const BAD_REQUEST_TRANSFER_REVERSAL_INSUFFICIENT_BALANCE                    = 'The linked account does not have sufficient balance to process a reversal.';

    const BAD_REQUEST_UPDATE_ON_HOLD_ALREADY_SETTLED                            = 'The hold attributes cannot be modified as the amount has already been settled to your account.';

    const BAD_REQUEST_USER_ACCOUNT_LOCKED                                       = 'Your account has been locked';
    const BAD_REQUEST_USER_ACCOUNT_DISABLED                                     = 'Your account has been disabled';
    const BAD_REQUEST_USER_NOT_AUTHENTICATED                                    = 'The user is not authenticated';
    const BAD_REQUEST_USER_NOT_FOUND                                            = 'User not found with the given input';
    const BAD_REQUEST_USER_ROLE_INVALID                                         = 'The given role is not supported';
    const BAD_REQUEST_INVITATION_USER_ALREADY_INVITED                           = 'Invitation is already sent to this email';
    const BAD_REQUEST_INVITATION_USER_ALREADY_MEMBER                            = 'User with given email is already a member of the team';
    const BAD_REQUEST_ADMIN_SELF_EDIT_PROHIBITED                                = 'SuperAdmin/Admin cannot edit their own preferences';
    const BAD_REQUEST_ADMIN_SELF_INVITE_PROHIBITED                              = 'Self-Invitation not allowed';
    const BAD_REQUEST_SUPERADMIN_ROLE_NOT_EDITABLE                              = 'SuperAdmin Role is not editable';

    const BAD_REQUEST_MERCHANT_HANDLE_UPPERCASE_ONLY                            = 'Merchant handle must be in uppercase.';
    const BAD_REQUEST_MERCHANT_DETAIL_ALREADY_LOCKED                            = 'Activation form has been locked for editing by admin.';
    const BAD_REQUEST_MERCHANT_DETAIL_FILE_TYPE                                 = 'Invalid File format. Only pdf, png and jpg is allowed.';
    const BAD_REQUEST_CASHBACK_CRITERIA_MISSING                                 = 'Either of percent_rate, min_txn_amount, max_cashback, min_cashback is required';
    const BAD_REQUEST_INVALID_OFFER_DURATION                                    = 'Offer end date must be later than offer start date';
    const BAD_REQUEST_OFFER_ALREADY_EXISTS                                      = 'Offer already exists. Please check the values and try again';
    const BAD_REQUEST_FLAT_CASHBACK_WITH_PERCENT_RATE_OR_MAX_CASHBACK           = 'Flat cashback cannot be combined wih percent rate or max cashback in an offer';
    const BAD_REQUEST_IINS_EDITABLE_FOR_CARD_OFFER                              = 'Iins can only be editable for card offer';
    const BAD_REQUEST_OFFER_ALREADY_DEACTIVATED                                 = 'Offer has already been deactivated';
    const BAD_REQUEST_ORG_ID_REQUIRED                                           = 'Authentication Failed';
    const BAD_REQUEST_INVALID_PERMISSIONS_USAGE                                 = 'Combination of permissions used or assigned are invalid. Contact Razorpay Support';

    const BAD_REQUEST_FEE_BREAKUP_CREATION_FAILED                               = 'Error occured while saving fee breakup';

    const BAD_REQUEST_API_CAPTURE_FAILED                                        = 'Error while recording capture on API side';

    const BAD_REQUEST_INVALID_PAYMENT_METHOD                                    = 'Payment method invalid / not allowed';
    const BAD_REQUEST_INVALID_GATEWAY_FOR_METHOD                                = 'Gateway not valid for payment method';

    const BAD_REQUEST_ES_DEBUG_METHOD_NOT_VALID                                 = 'Es debug method is not valid';

    const BAD_REQUEST_PAYMENT_AUTH_DATA_MISSING                                 = 'Incomplete data for force authorization';

    const BAD_REQUEST_FILE_NOT_FOUND                                            = 'There was error while retrieving the file';

    const BAD_REQUEST_MERCHANT_USER_ACTION_NOT_SUPPORTED                        = 'The input action is not supported for the merchant user';
    const BAD_REQUEST_ACCESS_DENIED                                             = 'Access Denied';

    // Workflow Related Errors
    const BAD_REQUEST_WORKFLOW_ENTITY_NOT_FOUND                                 = 'Workflow entity not found';
    const BAD_REQUEST_WORKFLOW_ENTITY_ID_NOT_FOUND                              = 'Workflow entity ID not found';
    const BAD_REQUEST_CHECK_NOT_REQUIRED_IN_CURRENT_LEVEL                       = 'No check required from checker roles in the current workflow action level';
    const BAD_REQUEST_ACTION_INVALID_TYPE                                       = 'The given action type is not valid';
    const BAD_REQUEST_ACTION_INVALID_METHOD                                     = 'The given action method is not valid';
    const BAD_REQUEST_WORKFLOW_INVALID_ACTION_STATE                             = 'Action State is not valid';
    const BAD_REQUEST_WORKFLOW_INVALID_CHECKER                                  = 'The checker review is invalid for this action';
    const BAD_REQUEST_WORKFLOW_ACTION_NOT_FOUND                                 = 'The requested action is not found';
    const BAD_REQUEST_WORKFLOW_ACTION_CLOSE_NOT_AUTHORIZED                      = 'Workflow Action can only be closed by maker.';
    const BAD_REQUEST_WORKFLOW_ACTION_CLOSED                                    = 'The workflow action is closed.';
    const BAD_REQUEST_ACTION_NOT_APPROVED                                       = 'The requested action is not in approved state';
    const BAD_REQUEST_ACTION_ALREADY_EXECUTED                                   = 'The requested action has already been executed';
    const BAD_REQUEST_WORKFLOW_DELETE_NOT_ALLOWED                               = 'Deleting/Updating a workflow is not possible if an action is in still in progress';
    const BAD_REQUEST_WORKFLOW_STEP_LEVEL_SEQUENCE                              = 'The levels in the steps should be increment of one';
    const BAD_REQUEST_WORKFLOW_STEP_ROLE_LEVEL_UNIQUE                           = 'The role and level combination should be unique';
    const BAD_REQUEST_WORKFLOW_PERMISSIONS_CANNOT_BE_REMOVED                    = 'Permissions associated with a workflow cannot be removed';
    const BAD_REQUEST_WORKFLOW_PERMISSION_EXISTS                                = 'One of the permissions already has a workflow defined';
    const BAD_REQUEST_PERMISSION_DISABLED_FOR_WORKFLOW                          = 'Some of the permissions passed cannot be used to create a workflow.';
    const BAD_REQUEST_WORKFLOW_ANOTHER_ACTION_IN_PROGRESS                       = 'Other actions on the entity are in progress.';
    const BAD_REQUEST_WORKFLOW_STEP_OP_MISMATCH                                 = 'The op type does not match with other steps in the same level';
    const BAD_REQUEST_WORKFLOW_ACTION_CLOSE_UNAUTHORIZED                        = 'An action can only be closed by maker';
    const BAD_REQUEST_ACTION_NOT_IN_OPEN_STATES                                 = 'Workflow action is not in any open state';
    const BAD_REQUEST_WORKFLOW_STEPS_CANNOT_BE_EDITED                           = 'Workflow steps cannot be edited';
    const BAD_REQUEST_WORKFLOW_DUTY_TYPE_INVALID                                = 'Workflow requests listing duty/type params are invalid';

    const BAD_REQUEST_SUPERADMIN_ACCESS_REQUIRED                                = 'This service can only be accessed by superadmins';

    const BAD_REQUEST_TOTAL_LOAD_EXCEEDS_MAX_LOAD                               = 'Load across all gateway rules must be less than 100 percent';

    const BAD_REQUEST_INVALID_OAUTH_MAIL_TYPE                                   = 'Invalid type sent for oauth mail.';
    const BAD_REQUEST_AUTH_SERVICE_ERROR                                        = 'There was an error completing this request';

    const BAD_REQUEST_COUPON_LIMIT_REACHED                                      = 'Coupon code limit reached';
    const BAD_REQUEST_COUPON_ALREADY_USED                                       = 'Coupon code already used';
    const BAD_REQUEST_INVALID_COUPON_CODE                                       = 'Coupon code not found';
    const BAD_REQUEST_COUPON_NOT_VALID_FOR_MERCHANT                             = 'Coupon code not valid for this merchant';
    const BAD_REQUEST_COUPON_NOT_APPLICABLE                                     = 'Coupon code is not applicable right now';
    const BAD_REQUEST_COUPON_EXPIRED                                            = 'Coupon code is expired';

    const BAD_REQUEST_SNS_PUBLISH_FAILED                                        = 'Sns Publish failed';

    const BAD_REQUEST_ADMIN_TOKEN_MISMATCH                                      = 'Admin Token Mismatch';

    const BAD_REQUEST_GATEWAY_FILE_NON_RETRIABLE                                = 'This gateway file generation attempt is not retriable';

    const SERVER_ERROR_GATEWAY_FILE_NO_DATA_FOUND                               = 'No data present for gateway file processing in the given time period';
    const SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE                       = 'Error occurred trying to create file';
    const SERVER_ERROR_GATEWAY_FILE_ERROR_SENDING_FILE                          = 'Error occurred while sending file';
}
