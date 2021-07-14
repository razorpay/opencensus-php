<?php

namespace RZP\Models\Payment\Refund;

// For any helper functions of refunds
class Helpers
{
    // Dynamic error messages for refund creation blocking
    // type 0 for neither instant nor gateway refund supported
    // type 1 for only instant refund supported
    public static function getBlockRefundsMessage($type = 0, $days = 180)
    {
        $months = intdiv($days, 30);

        switch ($type)
        {
            case 0 :
                return 'Refund is not supported by the bank because the payment is more than ' . $months . ' months old';

            case 1 :
                return 'Payment is more than ' . $months . ' months old, only instant refund is supported';
        }

        return '';
    }

    // get standard response for refund transaction create api based on arguments
    public static function getScroogeRefundTransactionCreateResponse(\Exception $ex = null, bool $compensatePayment = false, string $transactionId = null)
    {
        $response = [
            Constants::DATA => [
                Constants::TRANSACTION_ID     => $transactionId,
                Constants::COMPENSATE_PAYMENT => $compensatePayment,
            ],
            Constants::ERROR => NULL,
        ];

        if (is_null($ex) === false)
        {
            $response[Constants::ERROR][Constants::CODE] = $ex->getCode();
            $response[Constants::ERROR][Constants::MESSAGE] = $ex->getMessage();
        }

        return $response;
    }
}
