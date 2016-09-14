<?php

namespace RZP\Gateway\FirstData\Mock;

use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Gateway\FirstData;
use RZP\Gateway\Base;
use RZP\Models\Card;
use RZP\Models\Payment;

class Server extends Base\Mock\Server
{
    public function __construct()
    {
        parent::__construct();

        $this->repo = new FirstData\Repository;
    }

    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $timestamp = Carbon::now('Asia/Kolkata');

        $tdate = $timestamp->format('YmdHis').random_integer(5);
        $txndate_processed = $timestamp->format(FirstData\Codes::DATE_TIME_FORMAT);

        $approvalCode = 'Y'.':'.random_integer(6).':'.random_integer(10).':PPX :'.random_integer(12);
        $txnDateTime = $input['txndatetime'];
        $chargeTotal = $input['chargetotal'];
        $currencyCode = $input['currency'];
        $storeId = $input['storename'];

        $response_hash = $this->getHash($approvalCode, $chargeTotal, $currencyCode, $txnDateTime, $storeId);

        $oid = $this->generateId('ORD0000');
        if (isset($input['oid']) == true)
            $oid = $input['oid'];

        $content = array(
            FirstData\Entity::APPROVAL_CODE             => $approvalCode,
            FirstData\Entity::CCBIN                     => '',
            FirstData\Entity::CCBRAND                   => '',
            FirstData\Entity::CCCOUNTRY                 => '',
            FirstData\Entity::FAIL_RC                   => '',
            FirstData\Entity::FAIL_REASON               => '',
            FirstData\Entity::OID                       => $oid,
            FirstData\Entity::PROCESSOR_RESPONSE_CODE   => 00,
            FirstData\Entity::REFNUMBER                 => $this->generateId('REF0000'),
            FirstData\Entity::RESPONSE_HASH             => $response_hash,
            FirstData\Entity::STATUS                    => FirstData\Codes::STATUS_AUTHORIZED,
            FirstData\Entity::TDATE                     => $tdate,
            FirstData\Entity::TXNDATE_PROCESSED         => $txndate_processed,
        );

        $content = array_merge($content,$input);

        $this->content($content);

        $url = $input['responseSuccessURL'];
        $url .= '?' . http_build_query($content);

        return $url;
    }

    public function capture($input)
    {
        parent::capture($input);
    }

    protected function getHash($approvalCode, $chargeTotal, $currencyCode, $txnDateTime, $storeId)
    {
        $sharedSecret = $this->getGatewayInstance()->getSecret();

        $stringToHash = $sharedSecret . $approvalCode . $chargeTotal . $currencyCode . $txnDateTime . $storeId;
        $hash_algorithm = strtolower(FirstData\Codes::FIRST_DATA_HASH_ALGORITHM);

        $hash = hash($hash_algorithm, bin2hex($stringToHash));

        return $hash;
    }

    protected function generateId($prefix = '')
    {
        return '' . random_integer(5);
    }
}
