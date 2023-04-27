<?php

namespace RZP\Models\Gateway\File\Processor\Emi;

use App;
use RZP\Models\Bank\IFSC;
use RZP\Services;
use RZP\Models\FileStore;
use RZP\Trace\TraceCode;

class Scbl extends Base
{
    const BANK_CODE   = IFSC::SCBL;
    const FILE_TYPE   = FileStore\Type::SCBL_EMI_FILE;
    const FILE_NAME   = 'Scbl_Emi_File';
    const DATE_FORMAT = 'd/m/Y';

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

            $formattedData[] = [
                'Card Number'                  => $emiPayment->card->getLast4(),
                'Transaction Amount'           => $this->getFormattedAmount($emiPayment->getAmount()),
                'Transaction Date'             => $this->getFormattedDate($emiPayment->getCaptureTimestamp()),
                'Auth Code'                    => $this->getAuthCode($emiPayment),
                'Merchant Name'                => $merchant->getDbaName(),
                'RRN'                          => $rrn[$emiPayment->getId()]['rrn'] ?? '',
                'ARN'                          => '',
                'Settlement Date'              => $this->getFormattedDate($txn->getSettledAt()),
                'MID'                          => '',
                'TID'                          => '',
                'Card Hash'                    => '',
                'EMI ID'                       => $emiPayment->getId(),
                'Tenure'                       => $emiTenure,
                // Below fields are optional
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
                    'bank'       => static::BANK_CODE,
                ]
            );
        }

        return $formattedData;
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2,'.', '');
    }

    protected function getFormattedEmiRate($emiRate)
    {
        return rtrim(number_format($emiRate / 10000, 4), '0');
    }

    protected function getRrnNumber($data)
    {
        $CPS_PARAMS = [
            Services\CardPaymentService::RRN
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
}
