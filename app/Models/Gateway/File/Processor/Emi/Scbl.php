<?php

namespace RZP\Models\Gateway\File\Processor\Emi;

use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;

class Scbl extends Base
{
    const BANK_CODE   = IFSC::SCBL;
    const FILE_TYPE   = FileStore\Type::SCBL_EMI_FILE;
    const FILE_NAME   = 'Scbl_Emi_File';
    const DATE_FORMAT = 'd/m/Y';

    protected function formatDataForFile($data)
    {
        $formattedData = [];

        foreach ($data['items'] as $emiPayment)
        {
            $emiTenure = $emiPayment->emiPlan['duration'];

            $merchant = $emiPayment->merchant;

            $txn = $emiPayment->transaction;

            $emiRate = $emiPayment->emiPlan['rate'];

            $formattedData[] = [
                'CARD_NUMBER'                  => $this->getCardNumber($emiPayment->card),
                'MID'                          => $emiPayment->getId(),
                'MERCHANT_NAME'                => 'Razorpay Payments',
                'TRXN_AMOUNT'                  => $this->getFormattedAmount($emiPayment->getAmount()),
                'TRXN_DATE'                    => $this->getFormattedDate($emiPayment->getCaptureTimestamp()),
                'SETTLEMENT_DATE'              => $this->getFormattedDate($txn->getSettledAt()),
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

        return $formattedData;
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2);
    }

    protected function getFormattedEmiRate($emiRate)
    {
        return rtrim(number_format($emiRate / 10000, 4), '0');
    }
}
