<?php

namespace RZP\Models\Emi\Banks\Scbl;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\FileStore;
use RZP\Models\Emi\Banks\Base;
use RZP\Trace\TraceCode;

class EmiFile extends Base\EmiFile
{
    protected static $fileToWriteName = 'Scbl_Emi_File';

    protected $emailIdsToSendTo = ['scbl.emi@razorpay.com'];

    protected $bankName  = 'Scbl';

    protected $type = FileStore\Type::SCBL_EMI_FILE;

    protected function getEmiData($input)
    {
        $data = [];

        foreach ($input as $emiPayment)
        {
            $emiTenure = $emiPayment->emiPlan['duration'];

            $merchant = $emiPayment->merchant;

            $txn = $emiPayment->transaction;

            $emiRate = $emiPayment->emiPlan['rate'];

            $data[] = [
                'CARD_NUMBER'                  => $this->getCardNumber($emiPayment->card,$emiPayment->getGateway()),
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

            $this->trace->info(TraceCode::EMI_PAYMENT_SHARED_IN_FILE,
                [
                    'payment_id' => $emiPayment->getId(),
                    'bank'       => $this->bankName,
                ]
            );
        }

        return $data;
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount/100, 2);
    }

    protected function getFormattedDateFromTimestamp($timestamp)
    {
        return Carbon::createFromTimestamp($timestamp, Timezone::IST)->format('d/m/Y');
    }

    protected function getFormattedEmiRate($emiRate)
    {
        return rtrim(number_format($emiRate/10000,4), '0');
    }
}
