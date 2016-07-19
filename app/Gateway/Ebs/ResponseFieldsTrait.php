<?php

namespace RZP\Gateway\Ebs;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Ebs;
use Requests;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

trait ResponseFieldsTrait
{
    protected static $authorizeRequestFields = array(
        'channel',
        'account_id',
        'reference_no',
        'amount',
        'return_url',
        'name',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'phone',
        'email',
        'ship_name',
        'ship_address',
        'ship_state',
        'ship_city',
        'ship_postal_code',
        'ship_country',
        'ship_phone',
        'description',
        'currency',
        'mode',
        'secure_hash',
        'channel',
        'name_on_card',
        'card_number',
        'card_expiry',
        'payment_mode',
        'card_brand',
        'card_cvv',
    );

    protected static $callbackResponseFields = array(
        "ResponseCode",
        "PaymentID",
        "RequestID",
        "TransactionID",
        "MerchantRefNo",
        "ResponseMessage",
        "Amount",
        "Mode",
        "BillingName",
        "BillingAddress",
        "BillingCity",
        "BillingState",
        "BillingPostalCode",
        "BillingCountry",
        "BillingPhone",
        "BillingEmail",
        "DeliveryName",
        "DeliveryAddress",
        "DeliveryCity",
        "DeliveryState",
        "DeliveryPostalCode",
        "DeliveryCountry",
        "DeliveryPhone",
        "Description",
        "IsFlagged",
        "PaymentMethod",
        "SecureHash",
    );

    protected static $refundRequestFields = array(
    );

    protected static $refundResponseFields = array(
    );

    protected static $verifyRequestFields = array(
    );

    protected static $verifyResponseFields = array(
    );

    public function getFieldsForAction($action)
    {
        $var = $action . 'ResponseFields';

        return self::$$var;
    }

    public function getFields($action, $type = 'response')
    {
        if ($type === 'response')
        {
            return $this->getFieldsForAction($action);
        }
        else
        {
            $var = $action . 'RequestFields';

            return self::$$var;
        }
    }
}
