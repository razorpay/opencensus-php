<?php

namespace RZP\Models\FundTransfer\Rbl\Reconciliation;

use App;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\FundTransfer\Mode;
use RZP\Models\FundTransfer\Base\Reconciliation\ResponseProcessor as BaseResponseProcessor;

class ResponseProcessor extends BaseResponseProcessor
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function extractData(array $response)
    {
        $responseContent = $response['Single_Payment_Corp_Resp'];

        $bankStatus      = $responseContent['Header']['Status'];

        $utr             = $responseContent['Body']['UTRNo'] ?? null;

        $rrn             = $responseContent['Body']['RRN No'] ?? null;

        $referenceNo     = $responseContent['Body']['RefNo'];

        $transactionID   = $responseContent['Header']['TranID'];

        $paymentDate     = Carbon::createFromFormat(
            'Y-m-d H:i:s.u',
            $responseContent['Body']['Txn_Time'],
            Timezone::IST)->getTimestamp();

        $this->data = [
            self::UTR               => $this->getNullOnEmpty($utr),
            self::RRN               => $this->getNullOnEmpty($rrn),
            self::BANK_STATUS_CODE  => $this->getNullOnEmpty($bankStatus),
            self::PAYMENT_DATE      => $this->getNullOnEmpty($paymentDate),
            self::REFERENCE_NUMBER  => $this->getNullOnEmpty($referenceNo)
        ];

        $this->fetchReconEntity($transactionID);
    }

    protected function getTransactionReferenceNumber(): string
    {
        $key = (in_array($this->transferMode, [Mode::RTGS, Mode::NEFT], true) === true) ?
                self::UTR : self::RRN;

        return $this->data[$key];
    }

    protected function updateReconEntity()
    {
        $referenceNo = $this->getTransactionReferenceNumber();

        $this->reconEntity->setUtr($referenceNo);

        $this->reconEntity->setBankStatusCode($this->data[self::BANK_STATUS_CODE]);

        $this->reconEntity->setDateTime($this->data[self::PAYMENT_DATE]);

        $this->reconEntity->saveOrFail();
    }
}
