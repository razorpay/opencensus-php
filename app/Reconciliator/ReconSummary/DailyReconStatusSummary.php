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
        'from'     => 'sometimes|epoch',
        'to'       => 'sometimes|epoch',
        'emails'   => 'sometimes|email',
        'attach'   => 'sometimes|bool',
    ];

    public function generateReconSummary(array $input = [])
    {
        $this->validateInput($input);

        $inputParams = $this->setInputParams($input);

        $summary = $this->getFormattedSummary($inputParams['from'], $inputParams['to'], $inputParams['attach']);

        $reconSummaryMail = new ReconSummarymail($inputParams['emails'], Constants::GATEWAYS, Constants::AGGREGATE_PARAMS, $summary);

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
            'emails'    => (empty($input['email']) === false) ? explode(',', $input['email']) : [],
            'from'      => (empty($input['from']) === false) ?
                                  $input['from'] :
                                  Carbon::today(Timezone::IST)->subDays(Constants::DURATION)->getTimestamp(),
            'to'        => (empty($input['to']) === false) ?
                                  $input['to'] :
                                  Carbon::today(Timezone::IST)->getTimestamp(),
            'attach'    => boolval($input['attach'] ?? false)
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
