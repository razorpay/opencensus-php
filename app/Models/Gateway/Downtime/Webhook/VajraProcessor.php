<?php

namespace RZP\Models\Gateway\Downtime\Webhook;

use App;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\Gateway\Downtime;
use RZP\Models\Gateway\Downtime\Entity;
use RZP\Models\Gateway\Downtime\Source;
use RZP\Models\Gateway\Downtime\ReasonCode;
use RZP\Models\Gateway\Downtime\Webhook\Constants\Vajra as VajraConstants;

class VajraProcessor implements ProcessorInterface
{
    const COMMENT_JSON_KEYS = [
        VajraConstants::EVAL_MATCHES_KEY,
        VajraConstants::RULE_ID_KEY,
        VajraConstants::RULE_NAME_KEY,
        VajraConstants::RULE_URL_KEY,
        VajraConstants::TITLE_KEY,
    ];

    const UNIQUE_RECORD_KEY_SET = [
        Entity::GATEWAY,
        Entity::METHOD,
        Entity::TERMINAL_ID,
        Entity::SOURCE,
    ];

    protected $app;

    protected $trace;

    protected $core;

    protected $repo;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->repo = $this->app['repo'];

        $this->core = new Downtime\Core;
    }

    public function validate(array $input)
    {
        // Noop
    }

    public function process(array $input)
    {
        $status = $input[VajraConstants::STATUS_KEY];

        $this->validateStatus($status);

        $downtimeDataSet = $this->buildDowntimeDataSet($input);

        $downtimeResultSet = [];

        $downtimeCallback = NULL;

        switch ($status)
        {
            case VajraConstants::STATUS_ALERTING:
                $downtimeCallback = 'createDowntime';

                break;

            case VajraConstants::STATUS_OK:
                $downtimeCallback = 'resolveDowntime';

                break;

            default:
                return [];
        }

        foreach ($downtimeDataSet as $downtimeData)
        {
            try
            {
                $downtimeResultSet[] = $this->{$downtimeCallback}($downtimeData);
            }
            catch (\Exception $e)
            {
                $this->trace->traceException($e);
            }
        }

        return $downtimeResultSet;
    }

    private function createDowntime(array &$downtimeData)
    {
        $this->trace->info(
                TraceCode::GATEWAY_DOWNTIME_VAJRA_CREATE, ['data' => $downtimeData]);

        $downtime = $this->core->fetchMostRecentActive($downtimeData, self::UNIQUE_RECORD_KEY_SET);

        if (is_null($downtime) === false)
        {
            throw new Exception\LogicException(
                'Duplicate Ongoing Downtime found',
                null,
                [
                    'downtimeData' => $downtimeData,
                ]);
        }

        $downtime = $this->core->create($downtimeData, self::UNIQUE_RECORD_KEY_SET);

        return $downtime->toArrayAdmin();
    }

    private function resolveDowntime(array &$downtimeData)
    {
        $this->trace->info(
                TraceCode::GATEWAY_DOWNTIME_VAJRA_EDIT, ['data' => $downtimeData]);

        $downtime = $this->core->fetchMostRecentActive($downtimeData, self::UNIQUE_RECORD_KEY_SET);

        if (is_null($downtime) === true)
        {
            throw new Exception\LogicException(
                'Trying to resolve a non-existent downtime',
                null,
                [
                    'downtimeData' => $downtimeData,
                ]);
        }

        $downtime->setEnd();

        $this->repo->saveOrFail($downtime);

        return $downtime->toArrayAdmin();
    }

    private function buildDowntimeDataSet(array $input)
    {
        $downtimeDataSet = [];

        $downtimeTypeData = $this->getDowntimeTypeAttributes($input);

        $downtimeAlertData = $this->getDowntimeAlertAttributes($input);

        $downtimeDetailsSet = $this->getDowntimeDetailsSet($input);

        foreach ($downtimeDetailsSet as $downtimeData)
        {
            $downtimeDataSet[] = array_merge(
                $downtimeTypeData,
                $downtimeAlertData,
                $downtimeData
            );
        }

        return $downtimeDataSet;
    }

    private function getDowntimeTypeAttributes(array $input)
    {
        return [
            Entity::SOURCE      => Source::VAJRA,
            Entity::REASON_CODE => ReasonCode::LOW_SUCCESS_RATE,
            Entity::PARTIAL     => false,
        ];
    }

    private function getDowntimeAlertAttributes(array $input)
    {
        $alertData = [];

        $commentData = [];

        $status = $input[VajraConstants::STATUS_KEY];

        if ($status === VajraConstants::STATUS_ALERTING)
        {
            // Record downtime start time
            $alertData[Entity::BEGIN] = time();
        }
        else
        {
            // Add downtime end time
            $alertData[Entity::END] = time();
        }

        foreach (self::COMMENT_JSON_KEYS as $key)
        {
            if (array_key_exists($key, $input) === true)
            {
                $commentData[$key] = $input[$key];
            }
        }

        $alertData[Entity::COMMENT] = json_encode($commentData);

        return $alertData;
    }

    private function getDowntimeDetailsSet(array $input)
    {
        $detailsRawData = $input[VajraConstants::DETAILS_DATA_KEY];

        $detailsData = json_decode($detailsRawData, true);

        if (is_null($detailsData) === true)
        {
            $this->trace->error(
                TraceCode::GATEWAY_DOWNTIME_VAJRA_INVALID_MESSAGE,
                [
                    'json' => $detailsRawData,
                    'error' => json_last_error()
                ]);

            throw new Exception\RuntimeException(
                'Failed to convert json to array',
                ['json' => $detailsRawData]);
        }

        $this->validateDetailsData($detailsData);

        $terminalIds = $this->getTerminalIds($detailsData);

        if (empty($terminalIds) === true)
        {
            return [$detailsData];
        }

        $detailsSet = [];

        foreach ($terminalIds as $terminalId)
        {
            $detailsSet[] = array_merge(
                $detailsData,
                [VajraConstants::DETAILS_DATA_TERMINAL_KEY => $terminalId]
            );
        }

        return $detailsSet;
    }

    private function getTerminalIds(array &$detailsData)
    {
        $merchantIds = array_pull($detailsData, VajraConstants::DETAILS_DATA_MERCHANTS_KEY, []);

        $merchantId = array_pull($detailsData, VajraConstants::DETAILS_DATA_MERCHANT_KEY, null);

        $terminalIds = array_pull($detailsData, VajraConstants::DETAILS_DATA_TERMINALS_KEY, []);

        $terminalId = array_pull($detailsData, VajraConstants::DETAILS_DATA_TERMINAL_KEY, null);

        $merchantIds = array_unique(array_merge(
            $merchantIds,
            (is_null($merchantId) === true) ? [] : [$merchantId]
        ));

        $merchantTerminalIds = [];

        $terminalIds = array_unique(array_merge(
            $terminalIds,
            $merchantTerminalIds,
            (is_null($terminalId) === true) ? [] : [$terminalId]
        ));

        return $terminalIds;
    }

    private function validateStatus($status)
    {
        $validStatus = [VajraConstants::STATUS_ALERTING, VajraConstants::STATUS_OK];

        if (in_array($status, $validStatus, true) === false)
        {
            $this->trace->critical(
                TraceCode::GATEWAY_DOWNTIME_VAJRA_INVALID_STATUS,
                ['status' => $status]);

            throw new Exception\BadRequestValidationFailureException(
                'Invalid Vajra status provided: ' . $status);
        }
    }

    private function validateDetailsData(array $detailsData)
    {
        $requiredKeys = [VajraConstants::DETAILS_DATA_METHOD_KEY, VajraConstants::DETAILS_DATA_GATEWAY_KEY];

        $diffSet = array_diff_key(array_flip($requiredKeys), $detailsData);

        if (empty($diffSet) === false)
        {
            $missingKeys = implode(", ", $diffSet);

            $this->trace->critical(
                TraceCode::GATEWAY_DOWNTIME_VAJRA_INVALID_MESSAGE,
                ['missing_keys' => $missingKeys]);

            throw new Exception\BadRequestValidationFailureException(
                'Missing mandatory Vajra Details(Message) attributes: ' . $missingKeys);
        }
    }
}
