<?php

namespace RZP\Models\FundTransfer\Attempt;

use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Jobs\FundTransfer;
use RZP\Models\Settlement;
use RZP\Constants\Timezone;
use RZP\Services\Beam\Service;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\FundTransfer\Mode;
use RZP\Exception\LogicException;
use RZP\Models\Vpa\Entity as VpaEntity;
use RZP\Models\Card\Entity as CardEntity;
use RZP\Models\Card\Issuer as CardIssuer;
use RZP\Models\Transaction\ReconciledType;
use RZP\Constants\Entity as EntityConstant;
use RZP\Mail\Base\Constants as MailConstants;
use RZP\Jobs\FTS\FundTransfer as FtsFundTransfer;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Models\BankAccount\Entity as BankAccountEntity;
use RZP\Models\FundTransfer\Base\Initiator\NodalAccount;
use RZP\Models\FundTransfer\Attempt\Constants as AttemptConstants;

class Core extends Base\Core
{
    const FTS_DISPATCH_DELAY = 5;

    public function createWithBankAccount(
        Base\PublicEntity $source,
        BankAccountEntity $bankAccount,
        array $values = [],
        $instantDispatch = false): Entity
    {
        $fundTransferAttempt = $this->create($source, $values);

        // TODO: Make this polymorphic instead of having bankAccount and vpa separately
        $fundTransferAttempt->bankAccount()->associate($bankAccount);

        // This needs to be done after associating bank account only
        // because it needs the association to figure out destination bank
        $fundTransferAttempt->modifyModeIfRequired();

        // This needs to be done after filling FTA since it uses getters on the entity
        // Also, this needs to be done after associating vpa or bank_account only
        // because it needs the association to figure out the destination type.
        $fundTransferAttempt->getValidator()->validateModeIfSet($values);

        $this->repo->saveOrFail($fundTransferAttempt);

        if ($fundTransferAttempt->getIsFTS() === true)
        {
            $this->sendFTSFundTransferRequest($fundTransferAttempt);
        }
        else if ($instantDispatch === true)
        {
            $this->dispatchForTransfer($fundTransferAttempt);
        }


        return $fundTransferAttempt;
    }

    public function createWithCard(
        Base\PublicEntity $source,
        CardEntity $card,
        array $values = []): Entity
    {
        $fundTransferAttempt = $this->create($source, $values, $card);

        // TODO: Make this polymorphic instead of having bankAccount, vpa and card separately
        $fundTransferAttempt->card()->associate($card);

        // This needs to be done after filling FTA since it uses getters on the entity.
        // Also, this needs to be done after associating destination only
        // because it needs the association to figure out the destination type.
        $fundTransferAttempt->getValidator()->validateModeIfSet($values);

        $this->repo->saveOrFail($fundTransferAttempt);

        if ($fundTransferAttempt->getIsFTS() === true)
        {
            $this->sendFTSFundTransferRequest($fundTransferAttempt);
        }

        return $fundTransferAttempt;
    }

    public function dispatchForTransfer(Entity $fta)
    {
        try
        {
            FundTransfer::dispatch($this->mode, $fta->getId());

            $this->trace->info(TraceCode::FTA_TRANSFER_DISPATCH, [
                'fta_id' => $fta->getId(),
            ]);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FTA_TRANSFER_DISPATCH_FAILED,
                [
                    'fta_id' => $fta->getId(),
                ]);
        }
    }

    public function createWithVpa(Base\PublicEntity $source, VpaEntity $vpa, array $values = []): Entity
    {
        $fundTransferAttempt = $this->create($source, $values);

        // TODO: Make this polymorphic instead of having bankAccount and vpa separately
        $fundTransferAttempt->vpa()->associate($vpa);

        // This needs to be done after filling FTA since it uses getters on the entity.
        // Also, this needs to be done after associating vpa or bank_account only
        // because it needs the association to figure out the destination type.
        $fundTransferAttempt->getValidator()->validateModeIfSet($values);

        $this->repo->saveOrFail($fundTransferAttempt);

        if ($fundTransferAttempt->getIsFTS() === true)
        {
            $this->sendFTSFundTransferRequest($fundTransferAttempt);
        }

        return $fundTransferAttempt;
    }

    /**
     * @param array $input
     * @return array
     * @throws LogicException
     */
    public function nodalFileUploadThroughBeam(array $input): array
    {
        (new Validator)->validateInput('retry_beam_file_upload', $input);

        $fileStoreId = $input['file_id'];

        $fileEntity = $this->repo->file_store->findOrFail($fileStoreId);

        $filePath = $fileEntity->getLocation();

        $channel  = $input[Entity::CHANNEL];

        $fileType = $input[Entity::FILE_TYPE];

        $jobName  = $this->getJobNameForBeamPush($channel, $fileType);

        $response = $this->sendFile($filePath, $jobName, $fileType, $channel);

        return [
            'response' => $response
        ];
    }

    /**
     * Takes an array of the reconciled rows as an input, each of them having 2 keys
     *   - entity
     *   - fire_webhook
     * Sends a webhook to notify the merchant about the settlement
     *
     * @param array $reconciledRows
     */
    public function notifyMerchantViaWebhook(array $reconciledRows)
    {
        $settlementCore = new Settlement\Core;

        foreach ($reconciledRows as $reconciledRow)
        {
            // Entity could be of class Settlement, Refund etc
            $entity = $reconciledRow['entity'];

            $fireWebhook = $reconciledRow['fire_webhook'];

            if ($fireWebhook === false)
            {
                continue;
            }

            // Allow only the settlement entities
            if ($entity->getEntityName() !== Constants\Entity::SETTLEMENT)
            {
                continue;
            }

            $settlementCore->triggerSettlementWebhook($entity);
        }
    }

    /**
     * It'll evaluate the given source and card details
     * it'll provide the channel from which transfer has to be done
     * and also whether to route it through FTS or not
     *
     * @param Base\PublicEntity $source
     * @param string            $sourceType
     * @param CardEntity|null   $card
     * @return array
     *
     * TODO: refactor this section so that we don't have to use `shouldUseGateway` and `getChannelForTransfer`
     * for different reasons. A single method should give us which path should be chosen
     * use RazorX here for easy config
     */
    protected function getChannelForTransfer(Base\PublicEntity $source, string $sourceType, CardEntity $card = null): array
    {
        $redis = $this->app['redis']->connection();

        if (in_array($sourceType, AttemptConstants::ALLOWED_PRODUCTS_ON_FTS, true) === true)
        {
            if (($sourceType === Type::PAYOUT) and
                (in_array($source->getChannel(), Settlement\Channel::getFtsSupportedPayoutChannels(), true) === true))
            {
                return [true, $source->getChannel()];
            }

            $srcMerchantId = $source->getMerchantId();

            $merchantList = $this->app['cache']->get(ConfigKey::FTS_TEST_MERCHANT);

            $merchantIds = (empty($merchantList) === false) ? explode(',', $merchantList) : [];

            if (in_array($srcMerchantId, $merchantIds, true) === false)
            {
                return [false, Settlement\Channel::YESBANK];
            }

            if ($sourceType === EntityConstant::FUND_ACCOUNT_VALIDATION)
            {
                if ($this->isTestMode() === true)
                {
                    return [false, Settlement\Channel::YESBANK];
                }
                else
                {
                    return [true, Settlement\Channel::ICICI];
                }
            }

            $amount = $source->getAmount();

            $mode = strtolower(Mode::IMPS);

            //
            // only imps is supported for now
            //
            if($amount >= NodalAccount::MAX_IMPS_AMOUNT)
            {
                return [false, Settlement\Channel::YESBANK];
            }

            $validCardRefund = $this->isFTSSupportedCardRefund($card);

            $supportedModes = $redis->hget(ConfigKey::FTS_CHANNELS, Settlement\Channel::ICICI);

            $supportedModes = explode(',', $supportedModes);

            //
            // check if card is valid and channel is active to accept traffic at FTS side
            //
            if (($validCardRefund === false) or
                (in_array($mode, $supportedModes, true) === false))
            {
                return [false, Settlement\Channel::YESBANK];
            }

            $randomValue = mt_rand(1, 100);
            $requestThreshold = (int) $this->app['cache']->get(ConfigKey::FTS_ROUTE_PERCENTAGE);

            if ($randomValue <= $requestThreshold)
            {
                return [true, Settlement\Channel::ICICI];
            }
        }

        return [false, Settlement\Channel::YESBANK];
    }

    /**
     * This will check if its a valid card payout which is supported by FTS
     *
     * @param CardEntity|null $card
     * @return bool
     */
    protected function isFTSSupportedCardRefund(CardEntity $card = null): bool
    {
        if ($card === null)
        {
            return false;
        }

        $iin = $card->iinRelation;

        if ($iin === null)
        {
            return false;
        }

        //
        // No intense checks are added here as those will be taken care while refund is been created
        //
        $cardIssuer = $iin->getIssuer();

        if ($cardIssuer !== CardIssuer::ICIC)
        {
            return true;
        }

        return false;
    }

    /**
     * @param Base\PublicEntity $source - refund/payout/fa-validation/etc entity
     * @param array             $values Attributes of the created FTA
     * @param CardEntity|null   $card
     *
     * @return Entity
     */
    protected function create(Base\PublicEntity $source, array $values = [], CardEntity $card = null)
    {
        $fundTransferAttempt = new Entity;

        $fundTransferAttempt->merchant()->associate($source->merchant);

        $fundTransferAttempt->source()->associate($source);

        list($isFTS, $channel) = $this->getChannelForTransfer($source, $fundTransferAttempt->getSourceType(), $card);

        $defaultValues = [
            Entity::INITIATE_AT => Carbon::now(Timezone::IST)->getTimestamp(),
            Entity::CHANNEL     => $channel,
            Entity::VERSION     => Version::V3,
            Entity::STATUS      => Status::CREATED,
            Entity::PURPOSE     => Purpose::REFUND,
            Entity::IS_FTS      => $isFTS,
        ];

        $values = array_merge($defaultValues, $values);

        $fundTransferAttempt->fillAndGenerateId($values);

        return $fundTransferAttempt;
    }

    /**
     * Fetch beam job name using channel and file type
     * @param string $channel
     * @param string $fileType
     * @return string
     * @throws LogicException
     */
    protected function getJobNameForBeamPush(string $channel, string $fileType): string
    {
        switch ($fileType)
        {
            case Entity::SETTLEMENT:
                switch ($channel)
                {
                    case Settlement\Channel::AXIS:
                        return BeamConstants::AXIS_SETTLEMENT_JOB_NAME;

                    case Settlement\Channel::ICICI:
                        return BeamConstants::ICICI_SETTLEMENT_JOB_NAME;

                    case Settlement\Channel::AXIS2:
                        return BeamConstants::AXIS2_SETTLEMENT_JOB_NAME;

                    default:
                        throw new LogicException('Invalid settlement channel', null, $channel);
                }

            case Entity::BENEFICIARY:
                switch ($channel)
                {
                    case Settlement\Channel::ICICI:
                        return BeamConstants::ICICI_BENEFICIARY_JOB_NAME;

                    case Settlement\Channel::AXIS2:
                        return BeamConstants::AXIS2_BENEFICIARY_JOB_NAME;

                    default:
                        throw new LogicException('Invalid Beneficiary channel', null, $channel);
                }

            default:
                throw new LogicException('Invalid file type', null, $fileType);
        }
    }

    /**
     * Prepare request and push file to beam
     *
     * @param string $filename
     * @param string $jobName
     * @param string $fileType
     * @param string $channel
     * @return mixed
     */
    protected function sendFile(string $filename, string $jobName, string $fileType, string $channel)
    {
        $fileInfo = [$filename];

        $data =  [
            Service::BEAM_PUSH_FILES   => $fileInfo,
            Service::BEAM_PUSH_JOBNAME => $jobName
        ];

        // In seconds
        $timelines = [];

        $mailInfo = [
            'fileInfo'  => $fileInfo,
            'channel'   => $channel,
            'filetype'  => $fileType,
            'subject'   => $channel . ' ' . $fileType . ' File Send Failure',
            'recipient' => MailConstants::MAIL_ADDRESSES[MailConstants::SETTLEMENT_ALERTS]
        ];

        return $this->app['beam']->beamPush($data, $timelines, $mailInfo, true);
    }

    /**
     * @param Entity $fta
     * @param bool   $isRegistered
     */
    public function sendFTSFundTransferRequest(Entity $fta, bool $isRegistered = false)
    {
        try
        {
            if ($fta->shouldUseGateway() === true)
            {
                return;
            }

            $redis = $this->app['redis']->connection();

            $ftsChannelMode = $redis->HGET(ConfigKey::FTS_CHANNELS, $fta->getChannel());

            if (empty($ftsChannelMode) === true)
            {
                $this->trace->info(
                    TraceCode::FTS_INVALID_CHANNEL,
                    [
                        'channel' => $fta->getChannel(),
                    ]);

                return;
            }

            FtsFundTransfer::dispatch($this->mode, $fta->getId(), $isRegistered)->delay(self::FTS_DISPATCH_DELAY);

            $this->trace->info(
                TraceCode::FTS_FUND_TRANSFER_JOB_DISPATCHED,
                [
                    'fta_id'      => $fta->getId(),
                    'source_type' => $fta->getSourceType(),
                ]);
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FTS_FUND_TRANSFER_DISPATCH_FAILED,
                [
                    'fta_id'      => $fta->getId(),
                    'source_type' => $fta->getSourceType(),
                ]);
        }
    }

    public function getFTAEntity(string $ftaId)
    {
        return $this->repo->fund_transfer_attempt->findOrFailPublic($ftaId);
    }

    public function updateFTA(Entity $fta, $ftsTransferId, string $status)
    {
        $fta->setFTSTransferId($ftsTransferId);

        $fta->setStatus($status);

        $this->repo->saveOrFail($fta);
    }

    public function updateFundTransfer(array $input)
    {
        // TODO: should support bulk updates
        try
        {
            if (array_key_exists(Entity::STATUS, $input) === true)
            {
                $input[Entity::STATUS] = strtolower($input[Entity::STATUS]);
            }

            (new Validator)->validateInput('fts_status_update', $input);

            $input[Entity::STATUS] = strtolower($input[Entity::STATUS]);

            $fta = $this->repo->fund_transfer_attempt->getAttemptByFTSTransferId($input[Entity::FUND_TRANSFER_ID]);

            if(($fta === null) and
               (isset($input[Entity::SOURCE_ID]) === true) and
               (isset($input[Entity::SOURCE_TYPE]) === true))
            {
                $fta = $this->repo
                            ->fund_transfer_attempt
                            ->getFTSAttemptBySourceId(
                                $input[Entity::SOURCE_ID],
                                $input[Entity::SOURCE_TYPE]);

                $fta->setFTSTransferId($input[Entity::FUND_TRANSFER_ID]);
            }

            $fta = $this->updateFtaWithInput($input, $fta);

            $fta->fill($input);

            if (method_exists($fta->source, 'setFTSTransferId') === true)
            {
                $fta->source->setFTSTransferId($input[Entity::FUND_TRANSFER_ID]);
            }

            $this->repo->fund_transfer_attempt->saveOrFail($fta);

            $this->updateSourceEntityByFta($fta, $input);

            $this->updateMerchantEntity($fta);

            return [
                'message' => 'FTA and source updated successfully',
            ];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FTS_UPDATE_FUND_TRANSFER_ATTEMPT_FAILED,
                [
                    'error' => $e->getMessage()
                ]);

            throw $e;
        }
    }

    public function getStatusClass(Entity $fta)
    {
        $channel = $fta->getChannel();

        if ($fta->shouldUseGateway() === true)
        {
            return '\\RZP\\Models\\FundTransfer\\' . ucfirst($channel) . '\\Reconciliation\\GatewayStatus';
        }

        return 'RZP\\Models\\FundTransfer\\' . ucfirst($channel) . '\\Reconciliation\\Status';
    }

    public function updateTransactionEntity($source, $reconciledType = ReconciledType::MIS)
    {
        // Source entity might update the transaction but because we would have already fetched
        // the transaction from source earlier. Then if we try to access $this->source->transaction now,
        // It will return an old copy. Not the updated transaction. Hence, we reload the relation.
        $source->load(EntityConstant::TRANSACTION);

        $currentTime = Carbon::now(Timezone::IST)->timestamp;

        $source->transaction->setReconciledAt($currentTime);

        $source->transaction->setReconciledType($reconciledType);

        $source->transaction->saveOrFail();
    }

    public function updateMerchantEntity(Entity $fta, bool $holdFunds = false)
    {
        if ($holdFunds === true)
        {
            $fta->merchant->setHoldFunds(true);

            $this->repo->saveOrFail($fta->merchant);
        }
    }

    public function updateSourceEntity(Entity $fta)
    {
        $statusNamespace = $this->getStatusClass($fta);

        $statusClass = new $statusNamespace;

        $isInternalError = $statusClass::isInternalError($fta);

        $bankStatusCode = $fta->getBankStatusCode();

        $publicErrorMessage = $statusClass::getPublicFailureReason($bankStatusCode);

        $ftaData = [
            'bank_account_id'   => $fta->getBankAccountId(),
            'vpa_id'            => $fta->getVpaId(),
            'merchant_id'       => $fta->getMerchantId(),
            'fta_id'            => $fta->getId(),
            'source_id'         => $fta->source->getId(),
            'beneficiary_name'  => null,
            'utr'               => $fta->getUtr(),
            'mode'              => $fta->getMode(),
            'remarks'           => $fta->getRemarks(),
            'fta_status'        => $fta->getStatus(),
            'bank_status_code'  => $bankStatusCode,
            'internal_error'    => $isInternalError,
            'failure_reason'    => $publicErrorMessage,
        ];

        $this->postFtaRecon($fta->source, $ftaData);

        $this->updateTransactionEntity($fta->source);
    }

    /**
     * @param       $source
     * @param array $ftaData
     */
    protected function postFtaRecon($source, array $ftaData)
    {
        $this->trace->info(
            TraceCode::FTA_SOURCE_PROCESSING_DATA,
            $ftaData);

        try
        {
            $entityType = $source->getEntity();

            $sourceCoreClass = EntityConstant::getEntityNamespace($entityType) . '\\Core';

            $sourceCore = new $sourceCoreClass();

            if (method_exists($sourceCore, 'updateStatusAfterFtaRecon') === false)
            {
                return;
            }

            $sourceCore->updateStatusAfterFtaRecon($source, $ftaData);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FTA_SOURCE_PROCESSING_FAILED,
                $ftaData
            );

            $slackData = [
                'headLine'  => 'fta source processing failed',
                'fta_id'    => $ftaData['fta_id'],
                'status'    => $ftaData['fta_status'],
                'source_id' => $ftaData['source_id'],
            ];

            $alerts = new Alerts();

            $alerts->notifySlack($slackData, Alerts::ALERT);
        }
    }

    public function updateSourceEntityByFta(Entity $fta, array $input)
    {
        $extraInfo = $input['extra_info'] ?? [];

        $ftaData = [
            'bank_account_id'   => $fta->getBankAccountId(),
            'vpa_id'            => $fta->getVpaId(),
            'merchant_id'       => $fta->getMerchantId(),
            'fta_id'            => $fta->getId(),
            'source_id'         => $fta->source->getId(),
            'utr'               => $fta->getUtr(),
            'mode'              => $fta->getMode(),
            'remarks'           => $fta->getRemarks(),
            'fta_status'        => $fta->getStatus(),
            'is_fts'            => $fta->getIsFTS(),
            'bank_status_code'  => $fta->getBankStatusCode(),
            'failure_reason'    => $fta->getFailureReason(),
        ] + $extraInfo;

        $this->sourceReconByFta($fta->source, $ftaData);

        if (($fta->getSourceType() === Type::PAYOUT) and
            (in_array($fta->getChannel(), Settlement\Channel::getNonTransactionChannels(), true) === true))
        {
            return;
        }

        if (($fta->getSourceType() === Type::REFUND) and ($fta->getStatus() !== Status::PROCESSED))
        {
            //
            // For refund fta, not updating transaction entity if fta is not processed.
            // Do not want to set recon details of transaction entity for non-processed refunds
            //
            return;
        }

        $this->updateTransactionEntity($fta->source);
    }

    protected function sourceReconByFta($source, array $ftaData)
    {
        $this->trace->info(
            TraceCode::FTA_SOURCE_PROCESSING_DATA,
            $ftaData);

        try
        {
            $entityType = $source->getEntity();

            $sourceCoreClass = EntityConstant::getEntityNamespace($entityType) . '\\Core';

            $sourceCore = new $sourceCoreClass();

            if (method_exists($sourceCore, 'updateWithDetailsBeforeFtaRecon') === true)
            {
                $sourceCore->updateWithDetailsBeforeFtaRecon($source, $ftaData);
            }

            if (method_exists($sourceCore, 'updateStatusAfterFtaRecon') === true)
            {
                $sourceCore->updateStatusAfterFtaRecon($source, $ftaData);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FTA_SOURCE_PROCESSING_FAILED,
                $ftaData
            );
        }
    }


    /**
     * @param string $channel
     * @param array  $input
     * @return array
     * @throws LogicException
     */
    public function healthCheck(string $channel, array $input): array
    {
        $validChannels = Settlement\Channel::getChannelsWithHealthCheck();

        if (in_array($channel, $validChannels, true) !== true)
        {
            throw new LogicException('channel does\'nt have health check implemented');
        }

        $nodalAccountClass = 'RZP\\Models\\FundTransfer\\' . ucwords($channel). '\\NodalAccount';

        $response = (new $nodalAccountClass)->healthCheck($input);

        return $response;
    }

    /**
     * @param array $input
     * @return array
     */
    public function getDataToUpdateFromInput(array $input)
    {
        $beneficiaryName = null;

        $extraInfo = $input['extra_info'] ?? [];

        if (empty($extraInfo[BankAccountEntity::BENEFICIARY_NAME]) === false)
        {
            $beneficiaryName = $extraInfo[BankAccountEntity::BENEFICIARY_NAME];
        }

        $internalError = false;

        if (empty($extraInfo[AttemptConstants::INTERNAL_ERROR]) === false)
        {
            $internalError = $extraInfo[AttemptConstants::INTERNAL_ERROR];
        }

        return array($beneficiaryName, $internalError);
    }

    /**
     * @param array $input
     * @param Entity $fta
     * @return Entity
     */
    public function updateFtaWithInput(array $input, Entity $fta)
    {
        if (empty($input[Entity::UTR]) === false) {
            $fta->setUtr($input[Entity::UTR]);
        }

        if (empty($input[Entity::MODE]) === false) {
            $fta->setMode($input[Entity::MODE]);
        }

        if (empty($input[AttemptConstants::BANK_PROCESSED_TIME]) === false) {
            $fta->setDateTime($input[AttemptConstants::BANK_PROCESSED_TIME]);
        }

        return $fta;
    }
}
