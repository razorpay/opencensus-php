<?php

namespace RZP\Models\Transfer;

class ErrorCodeMapping
{
    const CODE              = 'code';
    const DESCRIPTION       = 'description';
    const FIELD             = 'field';
    const SOURCE            = 'source';
    const STEP              = 'step';
    const REASON            = 'reason';
    const METADATA          = 'metadata';
    const ID                = 'id';
    const ORDER_ID          = 'order_id';

    // These cannot be different from the ones defined in ErrorCode.php file.
    // Sort these in PhpStorm by selecting the lines, then Edit menu -> Sort Lines.
    const BAD_REQUEST_ERROR                                                             = 'BAD_REQUEST_ERROR';
    const BAD_REQUEST_LINKED_ACCOUNT_NOTES_KEY_MISSING                                  = 'BAD_REQUEST_LINKED_ACCOUNT_NOTES_KEY_MISSING';
    const BAD_REQUEST_NEGATIVE_BALANCE_BREACHED                                         = 'BAD_REQUEST_NEGATIVE_BALANCE_BREACHED';
    const BAD_REQUEST_PAYMENT_FEES_GREATER_THAN_AMOUNT                                  = 'BAD_REQUEST_PAYMENT_FEES_GREATER_THAN_AMOUNT';
    const BAD_REQUEST_TRANSFER_INSUFFICIENT_BALANCE                                     = 'BAD_REQUEST_TRANSFER_INSUFFICIENT_BALANCE';
    const INTERNAL_SERVER_ERROR                                                         = 'INTERNAL_SERVER_ERROR';

    protected static $publicErrorDescriptions = [
        // Sort these in PhpStorm by selecting the lines, then Edit menu -> Sort Lines.
        self::BAD_REQUEST_ERROR                                                         => 'Bad request error',
        self::BAD_REQUEST_LINKED_ACCOUNT_NOTES_KEY_MISSING                              => 'Keys sent in linked_account_notes must exist in notes',
        self::BAD_REQUEST_NEGATIVE_BALANCE_BREACHED                                     => 'Maximum negative balance limit was breached',
        self::BAD_REQUEST_PAYMENT_FEES_GREATER_THAN_AMOUNT                              => 'Fees calculated for transfer is greater than transfer amount',
        self::BAD_REQUEST_TRANSFER_INSUFFICIENT_BALANCE                                 => 'Account does not have sufficient balance to carry out transfer operation',
        self::INTERNAL_SERVER_ERROR                                                     => 'Internal server error',
    ];

    private static $reasons = [
        // Sort these in PhpStorm by selecting the lines, then Edit menu -> Sort Lines .
        self::BAD_REQUEST_LINKED_ACCOUNT_NOTES_KEY_MISSING                              => 'invalid_notes_keys',
        self::BAD_REQUEST_NEGATIVE_BALANCE_BREACHED                                     => 'maximum_negative_balance_limit_breached',
        self::BAD_REQUEST_PAYMENT_FEES_GREATER_THAN_AMOUNT                              => 'amount_less_than_minimum_amount',
        self::BAD_REQUEST_TRANSFER_INSUFFICIENT_BALANCE                                 => 'insufficient_account_balance',
        self::INTERNAL_SERVER_ERROR                                                     => 'server_error',

    ];

    private static $fields = [
        // Sort these in PhpStorm by selecting the lines, then Edit menu -> Sort Lines.
        self::BAD_REQUEST_LINKED_ACCOUNT_NOTES_KEY_MISSING                              => Entity::LINKED_ACCOUNT_NOTES,
        self::BAD_REQUEST_NEGATIVE_BALANCE_BREACHED                                     => Entity::AMOUNT,
        self::BAD_REQUEST_PAYMENT_FEES_GREATER_THAN_AMOUNT                              => Entity::AMOUNT,
        self::BAD_REQUEST_TRANSFER_INSUFFICIENT_BALANCE                                 => Entity::AMOUNT,
    ];

    private static $steps = [
        // Sort these in PhpStorm by selecting the lines, then Edit menu -> Sort Lines.
        self::BAD_REQUEST_LINKED_ACCOUNT_NOTES_KEY_MISSING                              => 'transfer_processing',
        self::BAD_REQUEST_NEGATIVE_BALANCE_BREACHED                                     => 'transfer_processing',
        self::BAD_REQUEST_PAYMENT_FEES_GREATER_THAN_AMOUNT                              => 'transfer_processing',
        self::BAD_REQUEST_TRANSFER_INSUFFICIENT_BALANCE                                 => 'transfer_processing',
    ];

    public static function isErrorCodePublic($errorCode)
    {
        return (defined(__CLASS__ . '::' . strtoupper($errorCode)));
    }

    public static function getPublicErrorAttribute(Entity $transfer)
    {
        $error = [
            self::CODE              => $transfer->getErrorCode(),
            self::DESCRIPTION       => self::$publicErrorDescriptions[$transfer->getErrorCode()] ?? null,
            self::REASON            => self::$reasons[$transfer->getErrorCode()] ?? null,
            self::FIELD             => self::$fields[$transfer->getErrorCode()] ?? null,
            self::STEP              => self::$steps[$transfer->getErrorCode()] ?? null,
            self::ID                => $transfer->getPublicId(),
            self::SOURCE            => null,
            self::METADATA          => null,
        ];

        return $error;
    }

    /**
     * Check getErrorCodeFromDescription() for more details.
     *
     * @var string[]
     */
//    protected static $errorCode = [
//        'Keys sent in linked_account_notes must exist in notes'                                                                                                                     => self::BAD_REQUEST_LINKED_ACCOUNT_NOTES_KEY_MISSING,
//        'Your Negative Balance Limit has reached its maximum. Please Add funds to your account.'                                                                                    => self::BAD_REQUEST_NEGATIVE_BALANCE_BREACHED,
//        'The fees calculated for payment is greater than the payment amount. Please provide a higher amount'                                                                        => self::BAD_REQUEST_PAYMENT_FEES_GREATER_THAN_AMOUNT,
//        'Your account does not have enough balance to carry out the transfer operation. You can add funds to your account from your Razorpay dashboard or capture new payments.'    => self::BAD_REQUEST_TRANSFER_INSUFFICIENT_BALANCE,
//    ];

    /**
     * Was used for data backfill activity.
     * Check updateErrorCodeIfApplicable() in Models\Transfer\Service.php for more.
     *
     * @param $description
     * @return string|null
     */
//    public static function getErrorCodeFromDescription($description)
//    {
//        return self::$errorCode[$description] ?? null;
//    }
}
