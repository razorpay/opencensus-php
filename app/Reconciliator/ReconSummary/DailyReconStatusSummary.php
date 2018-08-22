<?php

namespace RZP\Reconciliator\ReconSummary;

use Mail;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Base\JitValidator;
use RZP\Constants\Timezone;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Mail\Reconciliation\DailyReconStatusSummary as ReconSummaryMail;

class DailyReconStatusSummary extends Base\Core
{
    use FileHandlerTrait;

    protected static $rules = [
        Constants::FROM          => 'sometimes|epoch',
        Constants::TO            => 'sometimes|epoch',
        Constants::EMAILS        => 'sometimes',
        Constants::EMAILS . '.*' => 'sometimes|email',
        Constants::ATTACH        => 'sometimes|bool',
    ];

    public function generateReconSummary(array $input = [])
    {
        $inputParams = $this->setInputParams($input);

        //
        // We need to call validate AFTER setting the input params because we need to first convert
        // the emails string (csv) into an array and then validate each element of that as email.
        //
        $this->validateInput($inputParams);

        $summary = $this->getFormattedSummary($inputParams[Constants::FROM], $inputParams[Constants::TO], $inputParams[Constants::ATTACH]);

        $reconSummaryMail = new ReconSummarymail($inputParams[Constants::EMAILS], Constants::GATEWAYS, Constants::AGGREGATE_PARAMS, $summary);

        //
        // Our queue cannot handle the amount of data that gets sent in it. Hence, sync.
        //
        Mail::send($reconSummaryMail);

        return ['success' => true];
    }

    protected function getFormattedSummary(int $from, int $to, bool $attach): array
    {
        $data = [];

        foreach (Constants::ENTITIES as $entity)
        {
            $entityClass = Helpers::getClassName($entity);

            $data[$entity]['summary']                = (new $entityClass)->getReconStatusSummary($from, $to);

            if ($attach === true)
            {
                $data[$entity]['unreconciled_data_file'] = (new $entityClass)->getUnreconciledDataFile($from, $to);
            }
        }

        return $data;
    }

    protected function setInputParams(array $input): array
    {
        $input = [
            Constants::EMAILS => (empty($input[Constants::EMAILS]) === false) ?
                                    explode(',', $input[Constants::EMAILS]) :
                                    [],

            Constants::FROM   => (empty($input[Constants::FROM]) === false) ?
                                    $input[Constants::FROM] :
                                    Carbon::today(Timezone::IST)->subDays(Constants::DURATION)->getTimestamp(),

            Constants::TO     => (empty($input[Constants::TO]) === false) ?
                                    $input[Constants::TO] :
                                    Carbon::today(Timezone::IST)->getTimestamp(),

            Constants::ATTACH => boolval($input[Constants::ATTACH] ?? false)
        ];

        return $input;
    }

    // ------ Processes before starting report-generation ------

    /**
     * Validates Input
     *
     * @param $input array
     */
    protected function validateInput(array $input)
    {
        (new JitValidator)->rules(self::$rules)->input($input)->validate();
    }
}
