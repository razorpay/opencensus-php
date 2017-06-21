<?php

namespace RZP\Gateway\Netbanking\Pnb;

use RZP\Gateway\Base;

class RefundFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'PNB_Netbanking_Refunds';

    const NEW_LINE = "\r\n";

    const ROW_LENGTH = 45

    public function generate($input)
    {

    }

    protected function sendRefundEmail($fileData = [], $email = null)
    {

    }

    protected function getRefundData($input)
    {

    }

    protected function formatDataToString(array $refunds)
    {
        $fileDataString = null;

        foreach ($refunds as $refund)
        {
            $refundString = $this->justify($refund);

            $fileDataString = $refundString . self::NEW_LINE;
        }

        return $fileDataString;
    }

    protected function justify(array $refund)
    {
        $refundString = null;

        $leftIndexMap = RefundFields::INDEX_MAP[RefundFields::LEFT];

        foreach ($leftIndexMap as $key => $index)
        {
            $value = $refund[$key];
        }
    }
}
