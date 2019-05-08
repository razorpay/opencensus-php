<?php

namespace RZP\Models\Gateway\File\Processor\Emi;

use Carbon\Carbon;
use RZP\Models\Emi;
use RZP\Models\Payment;
use RZP\Constants\Timezone;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;

class Bob extends Base
{
    const BANK_CODE   = IFSC::BARB;
    const FILE_TYPE   = FileStore\Type::BOB_EMI_FILE;
    const FILE_NAME   = 'Bob_Emi_File';
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

            $emiPercent = $emiRate/100;

            $emiPlan = $emiPayment->emiPlan;

            $issuerPlanId = $emiPlan->getIssuerPlanId();

            $acquirer = '';

            if (empty($emiPayment->terminal->getGatewayAcquirer()) === false)
            {
                $acquirer = Payment\Gateway::getAcquirerName($emiPayment->terminal->getGatewayAcquirer());
            }

            $principalAmount = $emiPayment->getAmount()/100;

            $emiAmount = $this->getEmiAmount($principalAmount, $emiPercent, $emiTenure);

            $subventionAmount = '0.00';

            if ($emiPlan->getSubvention() === Emi\Subvention::MERCHANT)
            {
                $merchantPayback = $emiPlan->getMerchantPayback()/100;

                $amount = ($principalAmount * $merchantPayback)/100;

                $subventionAmount = $this->getFormattedAmount($amount);
            }

            $formattedData[] = [
                'LOYALTY_TRANSACTIONID'            => $emiPayment->getId(),
                'ISSUER'                           => 'Bank of Baroda',
                'ACQUIRER'                         => $acquirer,
                'MANUFACTURER_NAME'                => '',
                'MERCHANT_NAME'                    => 'Razorpay Payments',
                'STORE_NAME'                       => '',
                'STORE_CITY'                       => '',
                'STORE_STATE'                      => '',
                'BANK_MID'                         => '',
                'BANK_TID'                         => '',
                'EMI_OFFER'                        => $emiTenure.' Months',
                'CARD_PAN'                         => $this->getCardNumber($emiPayment->card),
                'FIRST_NAME'                       => '',
                'MOBILE_NUMBER'                    => '',
                'EMAIL'                            => '',
                'BANKRRN'                          => '',
                'BANKAPPROVALCODE'                 => $this->getAuthCode($emiPayment),
                'BANKDATETIME'                     => $this->getFormattedDate($emiPayment->getCaptureTimestamp()),
                'SETTLEMENTTIME'                   => $this->getFormattedDate($txn->getSettledAt()),
                'TRANSACTIONAMOUNT'                => $this->getFormattedAmount($emiPayment->getAmount()),
                'MERCHANTSUBVENTION'               => '',
                'MERCHANTSUBVENTIONAMOUNT'         => $subventionAmount,
                'BANK_SUBVENTION'                  => '',
                'BANK_SUBVENTIONAMOUNT'            => '',
                'CUSTOMERINTERESTRATE'             => $emiPercent.'%',
                'PROCESSING_FEE'                   => '',
                'ADVANCEDEMI'                      => '',
                'MERCHANTRATE'                     => '',
                'MERCHANTAMOUNT'                   => '',
                'TXSTATUS'                         => '',
                'TYPE'                             => '',
                'FLAG'                             => '',
                'PRODUCT_CATEGORY'                 => $merchant->getCategory(),
                'SUB_CAT1'                         => '',
                'SUB_CAT2'                         => '',
                'SUB_CAT3'                         => '',
                'MANUFACTURERSUBVENTION'           => '',
                'MANUFACTURERSUBVENTIONAMOUNT'     => '',
                'PRODUCT_SR__NO_ DBA_NAME'         => '',
                'DMS_CODE'                         => '',
                'DEALER_TYPE'                      => '',
                'BATCH_NO_'                        => '',
                'CARD_HASH'                        => '',
                'RATE_OF_INTEREST____P_A_'         => $emiPercent.'%',
                'EMI_AMOUNT'                       => $emiAmount,
                'LOAN_AMOUNT'                      => $principalAmount,
                'DISCOUNT_CASHBACK__'              => '',
                'DISCOUNT_CASHBACK_AMOUNT'         => '',
                'BONUS_REWARD_POINTS'              => '',
                'EMI_MODEL'                        => $issuerPlanId,
                'Payback Rate'                     => '',
                'Payback Amount'                   => '',
                'Additional cashback Rate'         => '',
                'Additional cashback Amount'       => '',
                'Total'                            => '',
            ];
        }

        return $formattedData;
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount/100, 2);
    }

}
