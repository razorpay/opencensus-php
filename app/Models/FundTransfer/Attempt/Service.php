<?php

namespace RZP\Models\FundTransfer\Attempt;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Settlement;
use RZP\Models\Payment\Refund;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\FundTransfer\Mode;
use Razorpay\Trace\Logger as Trace;
use http\Exception\RuntimeException;
use RZP\Models\FundTransfer\Redaction;
use RZP\Models\FundTransfer\Attempt\Core;
use RZP\Models\FundTransfer\M2P\M2PConfigs;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\FundTransfer\Attempt\Validator;
use RZP\Services\FTS\Constants as FTSConstants;
use RZP\Models\FundTransfer\Attempt\Status as AttemptStatus;
use RZP\Models\FundAccount\Validation\Service as FavService;

class Service extends Base\Service
{
    public function initiateFundTransfers(array $input, $channel = null)
    {
        (new Validator)->validateInput('initiate_fund_transfer', $input);

        $this->trace->info(
            TraceCode::INITIATE_FUND_TRANSFER,
            [
                'input'     => $input,
                'channel'   => $channel
            ]);

        if(($input[Entity::PURPOSE] === Purpose::SETTLEMENT) and ($input[Entity::SOURCE_TYPE] === Type::SETTLEMENT))
        {
            $channelState = $this->getChannelState();

            if((isset($channelState[$channel]) === true) and $channelState[$channel] === Constants::DISABLE)
            {
                $this->trace->info(
                    TraceCode::SETTLEMENT_TRANSFER_DISABLED,
                    [
                        'channel' => $channel,
                    ]);

                return ['status' => 'failed'];
            }
        }

        return (new Initiator)->initiateFundTransfers($input, $channel);
    }

    public function reconcileFundTransfers(array $input, string $channel): array
    {
        $this->trace->info(TraceCode::FTA_BULK_RECONCILE_REQUEST, $input);

        $summary = (new BulkRecon($input, $channel))->process();

        return $summary;
    }

    private function checkBulkUpdateSkipConditions(Entity $fundTransferAttempt, array $params)
    {
        if ($fundTransferAttempt->getIsFts() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_FUND_TRANSFER_ATTEMPT_UPDATE_NOT_ALLOWED,
                null,
                null,
                'Only FTS can update this attempt');
        }

        //
        // Payouts have a proper status management and is exposed to the merchants.
        // FTA cannot change it randomly. Payouts creates reversals in case of failures.
        // Payouts state cannot change from reversed to processed.
        //
        if ($fundTransferAttempt->getSourceType() === Type::PAYOUT)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_FUND_TRANSFER_ATTEMPT_UPDATE_NOT_ALLOWED,
                null,
                null,
                'Payouts cannot be updated directly, must go via recon flow');
        }

        //
        // Temporarily allowing update of channel for Refund attempts.
        // This is because we don't have a way to change channel in a
        // clean way at the moment, but we may still want to change the
        // channel sometimes, and retry it.
        //
        if ((isset($params[Entity::CHANNEL]) === true) and
            ($fundTransferAttempt->isRefund() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_FUND_TRANSFER_ATTEMPT_UPDATE_NOT_ALLOWED,
                null,
                null,
                'Channel can only be edited for Refund attempts!');
        }

        // Allowing only specific state transitions on FTA
        //  processed -> failed
        //  initiated -> processed
        //  initiated -> failed
        if ((isset($params[Entity::STATUS]) === true) and
            (($params[Entity::STATUS] === $fundTransferAttempt->getStatus()) or
             (in_array($params[Entity::STATUS], [Status::PROCESSED, Status::FAILED], true) === false) or
             (in_array($fundTransferAttempt->getStatus(), [Status::PROCESSED, Status::INITIATED], true) === false)))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_FUND_TRANSFER_ATTEMPT_UPDATE_NOT_ALLOWED,
                null,
                null,
                'Cant update status to ' . $params[Entity::STATUS] . ' from ' . $fundTransferAttempt->getStatus());
        }
    }

    public function bulkUpdate(array $input)
    {
        $this->trace->info(
            TraceCode::FUND_TRANSFER_ATTEMPT_BULK_UPDATE_REQUEST,
            $input);

        $ids = array_keys($input);

        Entity::verifyIdAndSilentlyStripSignMultiple($ids);

        $fundTransferAttempts = $this->repo
                                     ->fund_transfer_attempt
                                     ->findManyWithRelations($ids, ['source']);

        $updatedIds = [];

        $notUpdatedIds = [];

        foreach ($fundTransferAttempts as $fundTransferAttempt)
        {
            $id = $fundTransferAttempt->getId();

            $params = $input[$id];

            (new Validator)->validateInput('edit', $params);

            try
            {
                $this->checkBulkUpdateSkipConditions($fundTransferAttempt, $params);
            }
            catch (\Throwable $ex)
            {
                $this->trace->error(TraceCode::FUND_TRANSFER_ATTEMPT_UPDATE_SKIPPED,
                    [
                        'fta_id'        => $id,
                        'reason'        => $ex->getMessage(),
                    ]);

                $notUpdatedIds[] = $id;

                continue;
            }

            $fundTransferAttempt->fill($params);

            $this->repo->saveOrFail($fundTransferAttempt);

            if ($fundTransferAttempt->isBatchSameAsSource() === true)
            {
                $this->core()->updateSourceEntity($fundTransferAttempt);
            }

            $updatedIds[] = $id;
        }

        $response = [
            'updated_ids'       => $updatedIds,
            'not_updated_ids'   => $notUpdatedIds
        ];

        $this->trace->info(TraceCode::FUND_TRANSFER_ATTEMPT_UPDATED, $response);

        return $response;
    }

    /**
     * Updated fund transfer source with the request param
     *
     * @param Base\Entity $source
     * @param array $params
     * @return null|string
     */
    protected function updateSource(Base\Entity $source, array $params)
    {
        $status = false;

        foreach ($params as $key => $value)
        {
            if ($key === Entity::STATUS)
            {
                $value = $this->getSourceStatus($source, $value);
            }

            $status |= $this->setSourceAttribute($source, $key, $value);
        }
        if ((bool) $status === true)
        {
            $this->repo->saveOrFail($source);

            return $source->getId();
        }

        return null;
    }

    protected function getSourceStatus(Base\Entity $source, string $value)
    {
        if ($value !== AttemptStatus::INITIATED)
        {
            return $value;
        }

        switch (true)
        {
            case $source instanceof Settlement\Entity:

                return Settlement\Status::CREATED;

            case $source instanceof Refund\Entity:

                return Refund\Status::CREATED;

            default:

                return $value;
        }
    }

    /**
     * Sets the attribute value of property if available in source
     *
     * @param Base\Entity $source
     * @param string $key
     * @param string $value
     * @return bool
     */
    protected function setSourceAttribute(Base\Entity $source, string $key, string $value): bool
    {
        $method = 'set' . studly_case($key);

        // If will check for the setter method for the property and if exist it'll update it.
        // It is dont because some source entity has mocked the setter method.
        // So rather then checking for attribute, we are checking for setter method.
        if (method_exists($source, $method) === true)
        {
            $source->{$method}($value);

            return true;
        }

        return false;
    }

    public function sendFTAReconReport()
    {
        $progressReport = new Report;

        $failureReport  = clone $progressReport;

        $fileInfo = [];

        $progressReport->sendFTAReconReport(Report::FTA_PROGRESS);

        $failureReport->sendFTAFailureReport(Report::FTA_FAILURES);

        Report::notify($progressReport, $failureReport);

        Report::sendEmail($progressReport, $failureReport);

        $data = array_merge($progressReport->getSummary(), $failureReport->getSummary());

        return $data;
    }

    /**
     * @param array $input
     * @return array
     */
    public function nodalFileUploadThroughBeam(array $input): array
    {
        $this->trace->info(
            TraceCode::RETRY_BEAM_FILE_UPLOAD,
            [
                'input'     => $input
            ]);

        return $this->core()->nodalFileUploadThroughBeam($input);
    }

    public function updateFundTransferAttempt(array $input): array
    {
        $this->trace->info(
            TraceCode::FTS_UPDATE_FUND_TRANSFER_ATTEMPT,
            [
                'input'     => (new Redaction())->redactData($input)
            ]);

        return $this->core()->updateFundTransfer($input);
    }

    public function healthCheck(string $channel, array $input): array
    {
        return $this->core()->healthCheck($channel, $input);
    }

    public function setChannelState(string $channel,string $action)
    {
        (new Validator)->validateInput('fta_control', [
            Entity::CHANNEL => $channel,
            'action'        => $action,
        ]);

        $redis = $this->app['redis']->Connection('mutex_redis');

        $user = $this->core()->getInternalUsernameOrEmail();

        $status = false;

        try
        {
            $redis->HMSET(ConfigKey::FTA_CHANNELS, [$channel => $action]);

            $data = [
                'channel'           => $channel,
                'user'              => $user ,
                'action'            => $action,
                'mode'              => $this->mode,
            ];

            (new SlackNotification)->send('fta_control', $data, null, 1, SlackNotification::SETTLEMENT);

            $status = true;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                Trace::CRITICAL,
                TraceCode::SET_CHANNEL_STATE_FAILED
                );
        }

        return [
            'status' => $status,
        ];
    }

    public function getChannelState(): array
    {
        $redis = $this->app['redis']->Connection('mutex_redis');

        $values = [];

        try
        {
            $values = $redis->HGETALL(ConfigKey::FTA_CHANNELS);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                Trace::CRITICAL,
                TraceCode::GET_CHANNEL_STATE_FAILED
            );
        }

        return $values;
    }

    public function processFundTransfersUsingFts(array $input, string $channel)
    {
        return (new Initiator)->processFundTransfersUsingFts($input, $channel);
    }

    public function getSupportedModes(array $input)
    {
        $xRequestId = $this->app['request']->header('X-Request-ID');

        $this->trace->info(
            TraceCode::FTA_FETCH_SUPPORTED_MODES_REQUEST,
            [
                'input'        => $input,
                'x-request-id' => $xRequestId,
            ]
        );

        $supportedModes = Mode::getSupportedModesMap();

        $networkRailsSupportedModesMap = M2PConfigs::getNetworkRailsSupportedModesMap();

        $supportedModes[Payout\Entity::CARD] = $networkRailsSupportedModesMap;

        $networkRailsIssuerCount = (isset($supportedModes[Payout\Entity::CARD]) === true) ?
            count($supportedModes[Payout\Entity::CARD]) : 0;

        //
        // Not logging entire map, since we are logging only to confirm we have reached this point
        //
        $this->trace->info(
            TraceCode::FTA_FETCH_SUPPORTED_MODES_RESPONSE,
            [
                'issuer_count'               => count(array_keys($supportedModes)),
                'network_rails_issuer_count' => $networkRailsIssuerCount,
                'x-request-id'               => $xRequestId,
            ]
        );

       return $supportedModes;
    }
}
