<?php

namespace RZP\Models\Gateway\File\Processor\Emi;

use App;
use Carbon\Carbon;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Emi;
use RZP\Models\Payment;
use RZP\Constants\Timezone;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Trace\TraceCode;

class Bob extends Base
{
    const BANK_CODE   = IFSC::BARB;
    const FILE_TYPE   = FileStore\Type::BOB_EMI_FILE;
    const FILE_NAME   = 'Bob_Emi_File';
    const DATE_FORMAT = 'd/m/Y h:i:s A';

    public function fetchEntities(): PublicCollection
    {
        $begin = $this->gatewayFile->getBegin();
        $end = $this->gatewayFile->getEnd();

        return $this->repo
            ->payment
            ->fetchEmiPaymentsOfCobrandingPartnerAndBankWithRelationsBetween(
                $begin,
                $end,
                null,
                static::BANK_CODE,
                [
                    'card.globalCard',
                    'emiPlan',
                    'merchant.merchantDetail',
                    'terminal'
                ]);
    }

    protected function formatDataForFile($data)
    {
        $formattedData = [];

        $rrn = $this->getRrnNumber($data['items']);

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

            $merchantDbaName =  $merchant->getDbaName() ?: 'Razorpay Payments';

            $formattedData[] = [
                'EMI ID'                    => $emiPayment->getId(),
                'cardno'                    => str_repeat('*', 12) . $emiPayment->card->getLast4(),
                'Issuer'                    => 'NA',
                'Acquirer'                  => 'NA',
                'Manufacturer'              => $merchantDbaName,
                'Merchant Name'             => $merchantDbaName,
                'RRN'                       => $rrn[$emiPayment->getId()]['rrn'] ?? '',
                'Auth Code'                 => $this->getAuthCode($emiPayment),
                'Transaction Amt'           => $this->getFormattedAmount($emiPayment->getAmount()),
                'EMI Offer'                 => $emiTenure . ' Months',
                'Email'                     => 'NA',
                'Store Name'                => 'NA',
                'Address1'                  => 'NA',
                'Store City'                => 'NA',
                'Store State'               => 'NA',
                'MID'                       => '',
                'TID'                       => '',
                'Transaction Time'          => $this->getFormattedDate($emiPayment->getCaptureTimestamp()),
                'Subvention'                => '',
                'Subvention Amount (Rs)'    => '',
                'Interest Rate'             => $emiPercent.'%',
                'Customer Processing Fee'   => '',
                'Customer Processing Amt'   => '',
                'Emi Amount'                => $emiAmount,
                'Transaction Amount'        => $this->getFormattedAmount($emiPayment->getAmount()),

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

    protected function getRrnNumber($data)
    {
        $CPS_PARAMS = [
            \RZP\Reconciliator\Base\Constants::RRN
        ];

        $paymentIds = array();

        foreach ($data as $payment)
        {
            array_push($paymentIds, $payment->id);
        }

        $request = [
            'fields'        => $CPS_PARAMS,
            'payment_ids'   => $paymentIds,
        ];

        $response = App::getFacadeRoot()['card.payments']->fetchAuthorizationData($request);

        return $response;
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount/100, 2, '.', '');
    }

}
