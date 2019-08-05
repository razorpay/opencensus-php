<?php

namespace RZP\Reconciliator\ReconSummary;

use Mail;
use Cache;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Base\JitValidator;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Gateway;
use RZP\Reconciliator\Messenger;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Mail\Reconciliation\DailyReconStatusSummary as ReconSummaryMail;
use RZP\Trace\TraceCode;

class DailyReconStatusSummary extends Base\Core
{
    use FileHandlerTrait;

    protected static $rules = [
        Constants::FROM                      => 'sometimes|epoch',
        Constants::TO                        => 'sometimes|epoch',
        Constants::EMAILS                    => 'sometimes',
        Constants::EMAILS . '.*'             => 'sometimes|email',
        Constants::UNRECON_DATA_FILE         => 'sometimes|bool',
        Constants::RECON_SUMMARY_FILE        => 'sometimes|bool',
        Constants::ADDITIONAL_GATEWAYS       => 'sometimes',
        Constants::MAX_ALLOWED_UNRECON_COUNT => 'sometimes'
    ];

    protected $messenger;

    /**
     * cache key which stores the list of gateways with dates for which corresponding gateway's unreconciled count
     * is greater than a specified limit.
     */
    const UNRECON_DATA_CACHE_KEY = 'unrecon_data_cache_key';

    const LAST_PREVIOUS_UNRECON_DAY = 2;

    const UNRECONCILED_TRANSACTIONS_SUMMARY = 'Unreconciled Transactions Summary';

    const UNRECON_CACHE_TTL = 4320; // cache ttl in mins.(3 days)

    const RECON_ALLOWED_GATEWAYS = [
        Gateway::UPI_MINDGATE,
    ];

    public function __construct()
    {
        parent::__construct();
        $this->messenger = new Messenger();
    }

    public function generateReconSummary(array $input = [])
    {
        $inputParams = $this->setInputParams($input);

        //
        // We need to call validate AFTER setting the input params because we need to first convert
        // the emails string (csv) into an array and then validate each element of that as email.
        //
        $this->validateInput($inputParams);

        $summary = $this->getFormattedSummary(
            $inputParams[Constants::FROM],
            $inputParams[Constants::TO],
            $inputParams[Constants::UNRECON_DATA_FILE],
            $inputParams[Constants::RECON_SUMMARY_FILE]
        );

        $reconSummaryMail = new ReconSummarymail(
            $inputParams[Constants::EMAILS],
            Constants::AGGREGATE_PARAMS,
            $summary
        );

        //
        // Our queue cannot handle the amount of data that gets sent in it. Hence, sync.
        //
        Mail::send($reconSummaryMail);

        return ['success' => true];
    }

    protected function getFormattedSummary(int $from, int $to, bool $attachUnreconFile, bool $attachReconSummaryFile): array
    {
        $data = [];

        $file = [];

        foreach (Constants::ENTITIES as $entity)
        {
            $entityClass = Helpers::getClassName($entity);

            $data[$entity]['summary'] = (new $entityClass)->getReconStatusSummary($from, $to);

            if ($attachUnreconFile === true)
            {
                $data[$entity]['unreconciled_data_file'] = (new $entityClass)->getUnreconciledDataFile($from, $to);
            }

            if ($attachReconSummaryFile === true)
            {
                $reconSummaryData = $data[$entity]['summary'];

                $data[$entity]['recon_summary_file'] = $this->getReconSummaryFile($reconSummaryData, $entity);
            }
        }

        return $data;
    }

    protected function getReconSummaryFile($reconSummaryData, $entity) : array
    {
        $fileContent = [];

        foreach ($reconSummaryData as $date => $rows)
        {
            foreach ($rows as $row)
            {
                // We don't want to include metadata rows, so ignore such rows
                if ($row['gateway'] !== 'All')
                {
                    $fileContent[] = $row;
                }
            }
        }

        return  [
            'url'  => $this->createExcelFile($fileContent, $entity . ' Recon Summary','files/settlement'),
            'name' => $entity . ' Recon Summary.xlsx'
        ];
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

            Constants::UNRECON_DATA_FILE  => boolval($input[Constants::UNRECON_DATA_FILE] ?? false),

            Constants::RECON_SUMMARY_FILE => boolval($input[Constants::RECON_SUMMARY_FILE] ?? true),

            Constants::ADDITIONAL_GATEWAYS => (empty($input[Constants::ADDITIONAL_GATEWAYS]) === false) ?
                                    explode(',', $input[Constants::ADDITIONAL_GATEWAYS]) : [],

            Constants::MAX_ALLOWED_UNRECON_COUNT => $input[Constants::MAX_ALLOWED_UNRECON_COUNT] ?? 0,
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

    private function getUnreconciledGatewayCache()
    {
        return json_decode(Cache::get(self::UNRECON_DATA_CACHE_KEY), true);
    }

    private function setUnreconciledGatewayCache(array $value)
    {
        Cache::put(self::UNRECON_DATA_CACHE_KEY, json_encode($value), self::UNRECON_CACHE_TTL);
    }

    private function setPreviousUnreconciledGateways(array $additionalGateways) : array
    {
        $gatewayUnreconCache = $this->getUnreconciledGatewayCache();

        $allGateways = array_unique(array_merge(self::RECON_ALLOWED_GATEWAYS, $additionalGateways), SORT_REGULAR);

        if (empty($gatewayUnreconCache) === true)
        {
            $gatewayUnreconCache = [];
        }

        $day = Carbon::today(Timezone::IST)->subDays(self::LAST_PREVIOUS_UNRECON_DAY)->getTimestamp();

        foreach($allGateways as $gateway)
        {
            $dates = $gatewayUnreconCache[$gateway] ?? [];

            if (in_array($day, $dates, true) === false)
            {
                array_push($dates, $day);

                $gatewayUnreconCache[$gateway] = $dates;
            }
        }

        return $this->trimUnusedKeysFromCache($gatewayUnreconCache);
    }


    private function removeDateFromGateway(array $gatewayCache, string $gateway, int $date): array
    {
        if (array_key_exists($gateway, $gatewayCache) === true)
        {
            $gatewayData = $gatewayCache[$gateway];

            $index = array_search($date, $gatewayData);

            if ($index !== false)
            {
                unset($gatewayData[$index]);
            }

            if (empty($gatewayData) === true)
            {
                unset($gatewayCache[$gateway]);
            }
            else
            {
                $gatewayCache[$gateway] = $gatewayData;
            }
        }

        return $gatewayCache;
    }

    private function trimUnusedKeysFromCache($gatewayCache)
    {
        $availableGateways = config('gateway.available');

        foreach($gatewayCache as $gateway => $dates)
        {
            if (in_array($gateway, $availableGateways) === false)
            {
                unset($gatewayCache[$gateway]);
            }
        }
        return $gatewayCache;
    }

    private function generateUnreconSummaryByGateway(array $unreconciledGatewaysData, int $maxAllowedUnreconCount)
    {
        $formattedSummary = [];

        foreach(Constants::ENTITIES as $entity)
        {
            $entityClass = Helpers::getClassName($entity);

            $summary = (new $entityClass)->getUnreconStatusSummaryByGateway($unreconciledGatewaysData);

            foreach ($summary as $row)
            {
                $count = $row['count'];
                $date = $row['date'];

                if ($count > $maxAllowedUnreconCount)
                {
                    if (array_key_exists($date, $formattedSummary) === false)
                    {
                        $formattedSummary[$date] = '';
                    }

                    $entry = [
                        'gateway' => $row['gateway'],
                        'count'   => $count,
                        'type'    => $entity
                    ];
                    $formattedSummary[$date] = empty($formattedSummary[$date]) === true
                                   ? json_encode($entry)
                                   : $formattedSummary[$date].','.json_encode($entry);
                }
                else
                {
                    $timeStamp = Carbon::parse($date)->setTimezone(Timezone::IST)
                               ->startOfDay()->subDay(1)->getTimestamp();

                    $unreconciledGatewaysData = $this->removeDateFromGateway($unreconciledGatewaysData,
                               $row['gateway'], $timeStamp);
                }
            }
        }

        if (empty($formattedSummary) === false)
        {
            $formattedSummary['headLine'] = self::UNRECONCILED_TRANSACTIONS_SUMMARY;

            $this->messenger->raiseReconWarn($formattedSummary);
        }

        $this->setUnreconciledGatewayCache($unreconciledGatewaysData);
    }

    public function generateReconSummaryByGateway(array $input)
    {
        $inputParams = $this->setInputParams($input);

        $this->validateInput($inputParams);

        $unreconciledGatewaysData = $this->setPreviousUnreconciledGateways(
            $inputParams[Constants::ADDITIONAL_GATEWAYS]);

        $this->generateUnreconSummaryByGateway($unreconciledGatewaysData,
                                               $inputParams[Constants::MAX_ALLOWED_UNRECON_COUNT]);

        return ['success' => true];
    }
}
