<?php

namespace RZP\Models\Gateway\File\Processor\Emi;

use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Trace\TraceCode;

class Citi extends Base
{
    const BANK_CODE             = IFSC::CITI;
    const FILE_TYPE             = FileStore\Type::CITI_EMI_FILE;
    const FILE_NAME             = 'Citi_Emi_File';
    const DATE_FORMAT           = 'd/m/Y';
    const COMPRESSION_REQUIRED  = true;

    protected function formatDataForFile($data)
    {
        $formattedData = [];

        foreach ($data['items'] as $emiPayment)
        {
            $emiTenure = $emiPayment->emiPlan['duration'];

            $rate = $emiPayment->emiPlan['rate'];

            $rate = number_format($rate, 2, '.', '');

            try
            {
                $cardNumber = str_repeat('*', 12) . $emiPayment->card->getLast4();
            }
            catch (\Exception $e)
            {
                // Ignore those payments for which card numbers are lost
                continue;
            }

            $date = date(self::DATE_FORMAT);

            $emiAmount = $this->getEmiAmount($emiPayment->getAmount(), $emiPayment->emiPlan['rate'], $emiTenure);

            $emiAmount = amount_format_IN($emiAmount);

            $merchantName = $emiPayment->merchant->getDbaName();

            $formattedData[] = [
                'LOYALTY_TRANSACTIONID'        => $emiPayment->getId(),
                'ISSUER'                       => 'CITI BANK',
                'ACQUIRER'                     => '',
                'MANUFACTURER_NAME'            => '',
                'MERCHANT_NAME'                => $merchantName,
                'STORE_NAME'                   => '',
                'STORE_CITY'                   => '',
                'STORE_STATE'                  => '',
                'BANK_MID'                     => '',
                'BANK_TID'                     => '',
                'EMI_OFFER'                    => $emiTenure,
                'CARD_PAN'                     => $cardNumber,
                'FIRST_NAME'                   => '',
                'BANKAPPROVALCODE'             => $this->getAuthCode($emiPayment),
                'BANKDATETIME'                 => $date,
                'SETTLEMENTTIME'               => $date,
                'TRANSACTIONAMOUNT'            => amount_format_IN($emiPayment->getAmount()),
                'MERCHANTSUBVENTION'           => '0.00%',
                'MERCHANTSUBVENTIONAMOUNT'     => '0',
                'BANK_SUBVENTION'              => '0.00%',
                'BANK_SUBVENTIONAMOUNT'        => '0',
                'CUSTOMERINTERESTRATE'         => amount_format_IN($rate) . '%',
                'PROCESSING_FEE'               => '0.00%',
                'ADVANCEDEMI'                  => '0',
                'MERCHANTRATE'                 => '0.00%',
                'MERCHANTAMOUNT'               => '0',
                'TXSTATUS'                     => 'SETTLED',
                'TYPE'                         => 'CENTRAL',
                'FLAG'                         => 'TRUE',
                'PRODUCT_CATEGORY'             => '',
                'SUB_CAT1'                     => '',
                'SUB_CAT2'                     => '',
                'SUB_CAT3'                     => '',
                'MANUFACTURERSUBVENTION'       => '0.00%',
                'MANUFACTURERSUBVENTIONAMOUNT' => '0',
                'PRODUCT_SR__NO_'              => '',
                'DBA_NAME'                     => '',
                'DMS_CODE'                     => '',
                'DEALER_TYPE'                  => '',
                'BATCH_NO_'                    => '',
                'CARD_HASH'                    => '',
                'RATE_OF_INTEREST____P_A_'     => amount_format_IN($rate) . '%',
                'EMI_AMOUNT'                   => $emiAmount,
                'LOAN_AMOUNT'                  => amount_format_IN($emiPayment->getAmount()),
                'DISCOUNT_CASHBACK__'          => '',
                'DISCOUNT_CASHBACK_AMOUNT'     => '',
                'ADDITIONAL_CASHBACK'          => '',
                'BONUS_REWARD_POINTS'          => '',
                'EMI_MODEL'                    => 'Y',
            ];

            $this->trace->info(TraceCode::EMI_PAYMENT_SHARED_IN_FILE,
                [
                    'payment_id' => $emiPayment->getId(),
                    'bank'       => static::BANK_CODE,
                ]
            );
        }

        return $formattedData;
    }
}
