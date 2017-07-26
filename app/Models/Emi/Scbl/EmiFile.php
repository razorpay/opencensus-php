<?php

namespace RZP\Models\Emi\Banks\Scbl;

use Carbon\Carbon;
use RZP\Models\FileStore;
use RZP\Models\Emi\Banks\Base;

class EmiFile extends Base\EmiFile
{
    protected static $fileToWriteName = 'Scbl_Emi_File';

    protected $emailIdsToSendTo = ['scbl.emi@razorpay.com'];

    protected $bankName  = 'Scbl';

    const EXTENSION = FileStore\Format::XLSX;

    const TYPE = FileStore\Type::SCBL_EMI_FILE;

    protected static $headers = [
        'CARD_NUMBER',
        'MID',
        'MERCHANT_NAM',
        'TRXN_AMOUNT',
        'TRXN_DATE',
        'SETTLEMENT_DATE',
        'AUTH_CODE',
        'TENOR',
        'REDUCING_INTEREST_RATE_P_A',
        'PROCESSING_FEE',
        'FORCLOSURE_FEE',
        'MIN_AMT',
        'MAX_AMT',
        'REDUCING_INTEREST_RATE_P_A_1',
    ];

    protected function getEmiData($input)
    {
        $data = [];

        foreach ($input as $emiPayment)
        {
            $emiTenure = $emiPayment->emiPlan['duration'];

            $merchant = $this->repo->merchant->fetchMerchantFromEntity($emiPayment);

            $txn = $this->repo->transaction->fetchForPayment($emiPayment);

            $emiRate = $emiPayment->emiPlan['rate']/100;

            $data[] = [
                'CARD_NUMBER'                  => $this->getCardNumber($emiPayment->card),
                'MID'                          => $emiPayment->getId(),
                'MERCHANT_NAME'                => 'Razorpay Payments',
                'TRXN_AMOUNT'                  => $this->getFormattedAmount($emiPayment->getAmount()),
                'TRXN_DATE'                    => $this->getFormattedDateFromTimestamp($emiPayment->getCaptureTimestamp()),
                'SETTLEMENT_DATE'              => $this->getFormattedDateFromTimestamp($txn->getSettledAt()),
                'AUTH_CODE'                    => $this->getAuthCode($emiPayment),
                'TENOR'                        => $emiTenure,
                'REDUCING_INTEREST_RATE_P_A'   => $this->getFormattedEmiRate($emiRate),
                'PROCESSING_FEE'               => '',
                'FORCLOSURE_FEE'               => '',
                'MIN_AMT'                      => '',
                'MAX_AMT'                      => '',
                'REDUCING_INTEREST_RATE_P_A_1' => '',
            ];
        }

        return $data;
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount/100, 2);
    }

    protected function getFormattedDateFromTimestamp($timestamp)
    {
        return Carbon::createFromTimestamp($timestamp, 'Asia/Kolkata')->format('d/m/Y');
    }

    protected function getFormattedEmiRate($emiRate)
    {
        return '0.' . $emiRate;
    }
}
