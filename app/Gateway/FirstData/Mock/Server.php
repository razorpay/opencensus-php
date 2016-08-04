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

        $oid = $this->generateId('ORD0000');
        if (isset($input['oid']) == true)
            $oid = $input['oid'];

        $content = array(
            FirstData\Entity::APPROVAL_CODE             => '',
            FirstData\Entity::OID                       => $oid,
            FirstData\Entity::REFNUMBER                 => $this->generateId('REF0000'),
            FirstData\Entity::STATUS                    => '',
            FirstData\Entity::TXNDATE_PROCESSED         => $txndate_processed,
            FirstData\Entity::TDATE                     => $tdate,
            FirstData\Entity::RESPONSE_HASH             => '',
            FirstData\Entity::FAIL_REASON               => '',
            FirstData\Entity::PROCESSOR_RESPONSE_CODE   => '',
            FirstData\Entity::FAIL_RC                   => '',
            FirstData\Entity::CCBIN                     => '',
            FirstData\Entity::CCCOUNTRY                 => '',
            FirstData\Entity::CCBRAND                   => '',
        );

        $this->content($content);

        $url = $input['responseSuccessURL'];
        $url .= '?' . http_build_query($content);

        return $url;
    }

    protected function generateId($prefix = '')
    {
        return '' . random_integer(5);
    }
}
