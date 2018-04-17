<?php

namespace RZP\Reconciliator\ReconSummary;

use Mail;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Constants\Timezone;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Mail\Reconciliation\DailyReconStatusSummary as ReconSummaryMail;

class DailyReconStatusSummary extends Base\Core
{
    use FileHandlerTrait;

    public function generateReconSummary(array $input = [])
    {
        $inputParams = $this->setInputParams($input);

        $summary = $this->getFormattedSummary($inputParams['from'], $inputParams['to']);

        $reconSummaryMail = new ReconSummarymail($inputParams['emails'], Constants::GATEWAYS, Constants::AGGREGATE_PARAMS, $summary);

        Mail::send($reconSummaryMail);

        return ['success' => true];
    }

    protected function getFormattedSummary(int $from, int $to): array
    {
        $data = [];

        foreach (Constants::ENTITIES as $entity)
        {
            $entityClass = Helpers::getClassName($entity);

            $data[$entity]['summary']                = (new $entityClass)->getReconStatusSummary($from, $to);
            $data[$entity]['unreconciled_data_file'] = (new $entityClass)->getUnreconciledDataFile($from, $to);
        }

        return $data;
    }

    protected function setInputParams(array $input): array
    {
        $input = [
            'emails'    => (empty($input['email']) === false) ? explode(',', $input['email']) : null,
            'from'      => (empty($input['from']) === false) ?
                                  $input['from'] :
                                  Carbon::today(Timezone::IST)->subDays(Constants::DURATION)->getTimestamp(),
            'to'        => (empty($input['to']) === false) ?
                                  $input['to'] :
                                  Carbon::today(Timezone::IST)->getTimestamp()
        ];

        return $input;
    }
}