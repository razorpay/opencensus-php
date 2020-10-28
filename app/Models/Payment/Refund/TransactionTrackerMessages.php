<?php

namespace RZP\Models\Payment\Refund;

class TransactionTrackerMessages
{
    const PRIMARY   = 'primary';
    const TERTIARY  = 'tertiary';
    const SECONDARY = 'secondary';

    const REFUND_SLA_DAYS = 7;

    const MESSAGE_AMOUNT        = '{{amount}}';
    const MESSAGE_EXPECTED_DATE = '{{expected_date}}';
    const MESSAGE_MERCHANT_NAME = '{{merchant_name}}';
    const MESSAGE_AUTO_REFUND_DELAY_DATE = '{{auto_refund_delay_date}}';
    const MESSAGE_AUTO_REFUND_DELAY_DAYS = '{{auto_refund_delay_days}}';

    const PRIMARY_REFUND_PROCESSED_SLA_DONE     = 'Your Refund has been Processed by Razorpay';

    const PRIMARY_REFUND_PROCESSED_SLA_NOT_DONE = 'Your Refund has been Processed by Razorpay';

    const PRIMARY_REFUND_INITIATED_SLA_DONE     = 'Your Refund has been Delayed';

    const PRIMARY_REFUND_INITIATED_SLA_NOT_DONE = 'Your Refund has been Delayed';

    const PRIMARY_REFUND_FAILED_AGED_SLA_NOT_DONE           = 'Your Refund has Failed';

    const PRIMARY_REFUND_PROCESSED_SLA_DONE_VOID_REFUND     = 'Your Refund has been Processed';

    const PRIMARY_REFUND_PROCESSED_SLA_NOT_DONE_VOID_REFUND = 'Your Refund has been Processed';

    const PRIMARY_REFUND_INITIATED_SLA_DONE_VOID_REFUND     = 'Your Refund has been Delayed';

    const PRIMARY_REFUND_INITIATED_SLA_NOT_DONE_VOID_REFUND = 'Your Refund has been Delayed';

    const SECONDARY_REFUND_PROCESSED_SLA_DONE = 'The refund for your payment done on ' . self::MESSAGE_MERCHANT_NAME .
    ' for '.self::MESSAGE_AMOUNT.' has been processed by Razorpay. ' .
    'Please contact your issuing bank for further details.';

    const SECONDARY_REFUND_FAILED_AGED_SLA_NOT_DONE = 'The refund for the transaction of ' . self::MESSAGE_AMOUNT .
    ' has failed. Our banking partner does not support refund for this payment because it is more than 6 months old. ' .
    'The funds have been settled to ' . self::MESSAGE_MERCHANT_NAME . ', please contact '. self::MESSAGE_MERCHANT_NAME .
    ' to get it processed.';

    const SECONDARY_REFUND_PROCESSED_SLA_NOT_DONE = 'Your refund for ' . self::MESSAGE_AMOUNT .
    ' has been initiated by ' . self::MESSAGE_MERCHANT_NAME . '. The amount will be deposited in your bank account by '
    . self::MESSAGE_EXPECTED_DATE;

    const SECONDARY_REFUND_INITIATED_SLA_NOT_DONE = 'The refund for ' . self::MESSAGE_AMOUNT .
    ' done on ' . self::MESSAGE_MERCHANT_NAME .
    ' is being processed and is taking longer than usual due to a technical issue at the bank\'s side.';

    const SECONDARY_REFUND_INITIATED_SLA_DONE = 'The refund for the transaction of ' .
    self::MESSAGE_AMOUNT . ' has been initiated';

    const SECONDARY_REFUND_PROCESSED_SLA_DONE_VOID_REFUND = 'Your refund for ' . self::MESSAGE_AMOUNT .
    ' has been processed. If you have not received the refund credit yet, please contact our team by raising a request';

    const SECONDARY_REFUND_PROCESSED_SLA_NOT_DONE_VOID_REFUND = 'Your refund for ' . self::MESSAGE_AMOUNT .
    ' has been processed by '.self::MESSAGE_MERCHANT_NAME.'. The amount will be deposited in your bank account by '
    . self::MESSAGE_EXPECTED_DATE;

    const SECONDARY_REFUND_INITIATED_SLA_NOT_DONE_VOID_REFUND = 'The refund for ' . self::MESSAGE_AMOUNT .
    ' done on ' . self::MESSAGE_MERCHANT_NAME .
    ' is being processed and is taking longer than usual due to a technical issue at the bank\'s side.';

    const SECONDARY_REFUND_INITIATED_SLA_DONE_VOID_REFUND = 'The refund for the transaction of ' .
    self::MESSAGE_AMOUNT . ' has been initiated';

    const PRIMARY_PAYMENT_CAPTURED = 'Payment was successfully settled to the Merchant';

    const PRIMARY_PAYMENT_CAPTURED_LATE_AUTH = 'Payment was successfully settled to the Merchant';

    const PRIMARY_PAYMENT_AUTHORIZED = 'Your Payment was Successful from Razorpay\'s End';

    const PRIMARY_PAYMENT_AUTHORIZED_LATE_AUTH = 'Your payment was not successful';

    const PRIMARY_PAYMENT_FAILED = 'Your payment was not successful';

    const PRIMARY_PAYMENT_CREATED = 'Payment request has been initiated by Razorpay';

    const PRIMARY_PAYMENT_PENDING = 'Payment request has been initiated by Razorpay';

    const SECONDARY_PAYMENT_CAPTURED = 'Your payment of '.self::MESSAGE_AMOUNT.' made towards ' .
    self::MESSAGE_MERCHANT_NAME.' has been successful. We request you to contact ' .
    self::MESSAGE_MERCHANT_NAME.
    ' for any update on the service or to initiate a refund in case the service/goods were not delivered';

    const SECONDARY_PAYMENT_CAPTURED_LATE_AUTH = 'Your payment of '.self::MESSAGE_AMOUNT.' made towards ' .
    self::MESSAGE_MERCHANT_NAME.' has been successful. We request you to contact ' .
    self::MESSAGE_MERCHANT_NAME.
    ' for any update on the service or to initiate a refund in case the service/goods were not delivered';

    const SECONDARY_PAYMENT_AUTHORIZED = 'Your payment was successfully recorded by Razorpay. ' .
    'If the services are not delivered by the merchant, an auto-refund would be initiated for the transaction by ' .
    self::MESSAGE_AUTO_REFUND_DELAY_DATE;

    const SECONDARY_PAYMENT_AUTHORIZED_LATE_AUTH = 'Your payment was successfully recorded by Razorpay. If ' .
    self::MESSAGE_MERCHANT_NAME . ' does not acknowledge the payment in ' .
    self::MESSAGE_AUTO_REFUND_DELAY_DAYS . ' days an auto-refund would be initiated for the transaction by ' .
    self::MESSAGE_AUTO_REFUND_DELAY_DATE.'. You may contact the merchant for further details';

    const SECONDARY_PAYMENT_FAILED = 'Your payment of ' . self::MESSAGE_AMOUNT .
    ' was not successful since we did not receive the successful callback from the issuing bank. The amount will be '.
    'refunded back to your account in 5-7 business days.';

    const SECONDARY_PAYMENT_CREATED = 'We are awaiting confirmation on the status of your payment from our Banking partners.';

    const SECONDARY_PAYMENT_PENDING = 'We are awaiting confirmation on the status of your payment from our Banking partners.';

    const TERTIARY_PAYMENT_FAILED = 'If there is a delay in the auto-refund, ' .
    'you will have to escalate the issue with your issuing bank and your bank should be able to assist you on the ' .
    'retrieval of the funds. You may submit your bank statement as proof for the debit, stating '.
    '"Money has been debited from my account and I have not received services/product and not got a refund".';

    public function getMessage($entity, $status, $messageType, $slaDone = null, $lateAuth = null, $voidRefund = null)
    {
        $constant = 'self::' . strtoupper($messageType) . '_' . strtoupper($entity) . '_' . strtoupper($status);

        if (is_bool($slaDone) === true)
        {
            $constant .= ($slaDone === true) ? '_SLA_DONE' : '_SLA_NOT_DONE';
        }

        if (is_bool($lateAuth) === true)
        {
            $constant .= ($lateAuth === true) ? '_LATE_AUTH' : '';
        }

        if (is_bool($voidRefund) === true)
        {
            $constant .= ($voidRefund === true) ? '_VOID_REFUND' : '';
        }

        if (defined($constant))
        {
            return constant($constant);
        }

        return '';
    }
}
