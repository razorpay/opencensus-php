<?php

namespace RZP\Models\FundTransfer\Attempt;

use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Trace\Tracer;
use RZP\Trace\TraceCode;
use RZP\Jobs\FundTransfer;
use RZP\Models\Settlement;
use RZP\Constants\Timezone;
use RZP\Services\Beam\Service;
use RZP\Constants\Entity as E;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\FundTransfer\Mode;
use RZP\Exception\LogicException;
use RZP\Models\FundTransfer\Redaction;
use RZP\Models\Vpa\Entity as VpaEntity;
use RZP\Models\Card\Entity as CardEntity;
use RZP\Models\Card\Issuer as CardIssuer;
use RZP\Models\Transaction\ReconciledType;
use RZP\Constants\Entity as EntityConstant;
use RZP\Mail\Base\Constants as MailConstants;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Models\FileStore\Entity as FileStoreEntity;
use RZP\Models\Payment\Refund\Status as RefundStatus;
use RZP\Models\BankAccount\Entity as BankAccountEntity;
use RZP\Models\FundTransfer\Base\Initiator\NodalAccount;
use RZP\Models\WalletAccount\Entity as WalletAccountEntity;
use RZP\Models\FundTransfer\Attempt\Status as AttemptStatus;
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
        $fundTransferAttempt = $this->create($source, $values, E::BANK_ACCOUNT);

        // TODO: Make this polymorphic instead of having bankAccount and vpa separately
        $fundTransferAttempt->bankAccount()->associate($bankAccount);

        // This needs to be done after associating bank account only
        // because it needs the association to figure out destination bank
        $fundTransferAttempt->modifyModeIfRequired();

        // This needs to be done after filling FTA since it uses getters on the entity
        // Also, this needs to be done after associating vpa or bank_account only
        // because it needs the association to figure out the destination type.
        $fundTransferAttempt->getValidator()->validateModeIfSet($values);

        $this->trace->info(
            TraceCode::FUND_TRANSFER_ATTEMPT_CREATED,
            [
                'id'     => $fundTransferAttempt->getId(),
                'is_fts' => $fundTransferAttempt->getIsFTS(),
                'status' => $fundTransferAttempt->getStatus()
            ]
        );

        $this->repo->saveOrFail($fundTransferAttempt);

        $this->sendFundTransferRequest($fundTransferAttempt, $instantDispatch);

        return $fundTransferAttempt;
    }

    protected function sendFundTransferRequest(Entity $fundTransferAttempt, $instantDispatch = false)
    {
        if ($fundTransferAttempt->getIsFTS() === true)
        {
            // For payouts a sync call for FTS fund transfer will be made after the current transaction closes.
            // Hence skipping sending to queue here.
            if (($fundTransferAttempt->getSourceType() === Type::PAYOUT) and
                ($fundTransferAttempt->source->makeSyncFtsFundTransfer() === true))
            {
                $fundTransferAttempt->source->setFta($fundTransferAttempt);

                return;
            }

            (new Initiator)->sendFTSFundTransferRequest($fundTransferAttempt);
        }
        else if ($instantDispatch === true)
        {
            $this->dispatchForTransfer($fundTransferAttempt);
        }
    }

    public function createWithCard(
        Base\PublicEntity $source,
        CardEntity $card,
        array $values = [],
        $instantDispatch = false): Entity
    {
        $fundTransferAttempt = $this->create($source, $values, E::CARD, $card);

        // TODO: Make this polymorphic instead of having bankAccount, vpa and card separately
        $fundTransferAttempt->card()->associate($card);

        // This needs to be done after filling FTA since it uses getters on the entity.
        // Also, this needs to be done after associating destination only
        // because it needs the association to figure out the destination type.
        $fundTransferAttempt->getValidator()->validateModeIfSet($values);

        $this->repo->saveOrFail($fundTransferAttempt);

        $this->sendFundTransferRequest($fundTransferAttempt, $instantDispatch);

        return $fundTransferAttempt;
    }

    public function dispatchForTransfer(Entity $fta)
    {
        //
        // Adding initiate_at checks to ensure refund is dispatched only if initiate_at is less than current timestamp
        // This helps in setting FTA initiate_at to a future date / time.
        //
        $isEligibleForInitiation = ((empty($fta->getInitiateAt()) === true) or
                                    ($fta->getInitiateAt() <= Carbon::now()->getTimestamp()));

        //
        // Not instantly dispatching for fta's with source type as refund in func environment
        // because of absence of queues, this check must be removed when func environment gets queue infra
        $isEligibleForInstantDispatch = (!(($fta->getSourceType() === Type::REFUND) and
                                          (in_array($this->env, [Constants\Environment::FUNC], true) === true)) and
                                         ($isEligibleForInitiation === true));

        $this->trace->info(
            TraceCode::FTA_IS_INSTANT_DISPATCH,
            [
              'isInstantDispatch' => $isEligibleForInstantDispatch,
            ]
        );

        if ($isEligibleForInstantDispatch === false)
        {
            return;
        }

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

    public function createWithVpa(
        Base\PublicEntity $source,
        VpaEntity $vpa,
        array $values = [],
        $instantDispatch = false): Entity
    {
        $fundTransferAttempt = $this->create($source, $values, E::VPA);

        // TODO: Make this polymorphic instead of having bankAccount and vpa separately
        $fundTransferAttempt->vpa()->associate($vpa);

        // This needs to be done after filling FTA since it uses getters on the entity.
        // Also, this needs to be done after associating vpa or bank_account only
        // because it needs the association to figure out the destination type.
        $fundTransferAttempt->getValidator()->validateModeIfSet($values);

        $this->repo->saveOrFail($fundTransferAttempt);

        $this->sendFundTransferRequest($fundTransferAttempt, $instantDispatch);

        return $fundTransferAttempt;
    }

    public function createWithWalletAccount(
        Base\PublicEntity $source,
        WalletAccountEntity $walletAccount,
        array $values = [],
        $instantDispatch = false): Entity
    {
        $values[Entity::MODE] = Mode::FTS_WALLET_TRANSFERS_MODE;

        $fundTransferAttempt = $this->create($source, $values, E::WALLET_ACCOUNT);


        // TODO: Make this polymorphic instead of having bankAccount and vpa separately
        $fundTransferAttempt->walletAccount()->associate($walletAccount);

        // This needs to be done after filling FTA since it uses getters on the entity.
        // Also, this needs to be done after associating vpa or bank_account only
        // because it needs the association to figure out the destination type.
        $fundTransferAttempt->getValidator()->validateModeIfSet($values);

        $this->repo->saveOrFail($fundTransferAttempt);

        $this->sendFundTransferRequest($fundTransferAttempt, $instantDispatch);

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

        $response = $this->sendFile($filePath, $jobName, $fileType, $channel, $fileEntity);

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

            $sendFailedSms = isset($reconciledRow['send_fail_sms']) === true  ? $reconciledRow['send_fail_sms'] : false;

            $settlementCore->triggerSettlementWebhook($entity, null, $sendFailedSms);
        }
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

        $issuer = $iin->getIssuer();

        if ($issuer !== CardIssuer::ICIC)
        {
            return true;
        }

        return false;
    }

    /**
     * @param Base\PublicEntity $source
     * @param array $values
     * @param string $accountType
     * @param CardEntity|null $card
     * @return Entity
     * @throws LogicException
     */
    protected function create(Base\PublicEntity $source, array $values = [],  string $accountType, CardEntity $card = null)
    {
        $fundTransferAttempt = new Entity;

        $fundTransferAttempt->merchant()->associate($source->merchant);

        $fundTransferAttempt->source()->associate($source);

        if (($source->getEntity() === Constants\Entity::REFUND) and
            (isset($values[AttemptConstants::MODE]) === true))
        {
            $fundTransferAttempt->setMode($values[AttemptConstants::MODE]);
        }

        list($isFTS, $channel) = $this->getChannelForTransfer($fundTransferAttempt, $accountType, $card);

        $defaultValues = [
            Entity::INITIATE_AT => Carbon::now(Timezone::IST)->getTimestamp(),
            Entity::CHANNEL     => $channel,
            Entity::VERSION     => Version::V3,
            Entity::STATUS      => Status::CREATED,
            Entity::PURPOSE     => Purpose::REFUND,
            Entity::IS_FTS      => $isFTS,
        ];

        $values = array_merge($defaultValues, $values);

        $this->trace->info(
            TraceCode::REQUEST_CREATE_VALUES_TO_FTS,
            $values
        );

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
     * @param FileStoreEntity $fileStoreEntity
     * @return mixed
     */
    protected function sendFile(string $filename, string $jobName, string $fileType, string $channel, FileStoreEntity $fileStoreEntity)
    {
        $fileInfo = [$filename];

        $data =  [
            Service::BEAM_PUSH_FILES   => $fileInfo,
            Service::BEAM_PUSH_JOBNAME => $jobName,
            Service::BEAM_PUSH_BUCKET_NAME   => $fileStoreEntity->getBucket(),
            Service::BEAM_PUSH_BUCKET_REGION => $fileStoreEntity->getRegion(),
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

    public function getFTAEntity(string $ftaId)
    {
        return $this->repo->fund_transfer_attempt->findOrFailPublic($ftaId);
    }

    public function updateFTA(Entity $fta, $ftsTransferId, string $status = null, string $failureReason = null)
    {
        if ($status !== null && AttemptStatus::isValidStateTransition($fta->getStatus(), $status) === false)
        {
            throw new LogicException('Not a valid state transition');
        }

        if (empty($failureReason) === false)
        {
            $fta->setFailureReason($failureReason);
        }

        if (empty($ftsTransferId) === false)
        {
            $fta->setFTSTransferId($ftsTransferId);
        }

        if (empty($status) === false)
        {
            $fta->setStatus($status);
        }

        $this->repo->saveOrFail($fta);
    }

    /**
     * To Update FTA and source Using incoming webhook from FTS
     *
     * @param array $input
     * @return array
     * @throws \Throwable
     */
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
                                $input[Entity::SOURCE_TYPE],
                                true);

                // Set fts_transfer_id only in live mode
                // because in test mode we don't call FTS service
                if ($this->isLiveMode() === true)
                {
                    $fta->setFTSTransferId($input[Entity::FUND_TRANSFER_ID]);
                }
            }

            if (AttemptStatus::isValidStateTransition($fta->getStatus(), $input[Entity::STATUS]) === false) {
                return [
                    'message' => 'webhook update skipped due to invalid state transition',
                ];
            }

            $fta = $this->updateFtaWithInput($input, $fta);

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

            $this->trace->count(Metric::WEBHOOK_UPDATE_FAILURE_COUNT,
                                [
                                    'error' => $e->getMessage()
                                ]);

            throw $e;
        }
    }

    public function getStatusClass(Entity $fta)
    {
        $channel = $fta->getChannel();

        if ($fta->shouldUseGateway($fta->getMode()) === true)
        {
            return '\\RZP\\Models\\FundTransfer\\' . ucfirst($channel) . '\\Reconciliation\\GatewayStatus';
        }

        return 'RZP\\Models\\FundTransfer\\' . ucfirst($channel) . '\\Reconciliation\\Status';
    }

    public function updateTransactionEntity($source, $reset = false, $reconciledType = ReconciledType::MIS)
    {
        // Source entity might update the transaction but because we would have already fetched
        // the transaction from source earlier. Then if we try to access $this->source->transaction now,
        // It will return an old copy. Not the updated transaction. Hence, we reload the relation.
        $source->load(EntityConstant::TRANSACTION);

        $reconciledTime = Carbon::now(Timezone::IST)->timestamp;

        if ($reset === true)
        {
            $reconciledTime = $reconciledType = null;
        }

        $source->transaction->setReconciledAt($reconciledTime);

        $source->transaction->setReconciledType($reconciledType);

        $source->transaction->saveOrFail();
    }

    public function updateMerchantEntity(Entity $fta, bool $holdFunds = false)
    {
        if ($holdFunds === true)
        {
            $fta->merchant->setHoldFunds(true);

            $fta->merchant->setHoldFundsReason('bank account/transaction was rejected from bank');

            $this->repo->saveOrFail($fta->merchant);
        }
    }

    public function updateSourceEntity(Entity $fta)
    {
        $statusNamespace = $this->getStatusClass($fta);

        $statusClass = new $statusNamespace;

        $isInternalError = $statusClass::isInternalError($fta);

        $bankStatusCode = $fta->getBankStatusCode();

        $publicErrorMessage = $statusClass::getPublicFailureReason($bankStatusCode, $fta->getBankResponseCode());

        $ftaData = [
            'bank_account_id'    => $fta->getBankAccountId(),
            'vpa_id'             => $fta->getVpaId(),
            'merchant_id'        => $fta->getMerchantId(),
            'fta_id'             => $fta->getId(),
            'source_id'          => $fta->source->getId(),
            'beneficiary_name'   => null,
            'utr'                => $fta->getUtr(),
            'mode'               => $fta->getMode(),
            'remarks'            => $fta->getRemarks(),
            'fta_status'         => $fta->getStatus(),
            'bank_status_code'   => $bankStatusCode,
            'bank_response_code' => $fta->getBankResponseCode(),
            'internal_error'     => $isInternalError,
            'failure_reason'     => $publicErrorMessage,
        ];

        $this->postFtaRecon($fta->source, $ftaData);

        if ($fta->getSourceType() === Type::REFUND)
        {
            return;
        }

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
            (new Redaction())->redactData($ftaData));

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



            $this->trace->count(Metric::WEBHOOK_UPDATE_FAILURE_COUNT,
                                [
                                    'error' => $e->getMessage()
                                ]);
        }
    }

    public function updateSourceEntityByFta(Entity $fta, array $input = [])
    {
        $extraInfo = $input['extra_info'] ?? [];

        $gatewayErrorCode = $input['gateway_error_code'] ?? '';

        $statusDetails    = $input[Entity::STATUS_DETAILS] ?? null;

        $sourceAccountID = $input[Entity::SOURCE_ACCOUNT_ID] ?? null;

        $bankAccountType = $input[Entity::BANK_ACCOUNT_TYPE] ?? null;

        $ftaData = [
            'bank_account_id'          => $fta->getBankAccountId(),
            'vpa_id'                   => $fta->getVpaId(),
            'merchant_id'              => $fta->getMerchantId(),
            'fta_id'                   => $fta->getId(),
            'source_id'                => $fta->source->getId(),
            'utr'                      => $fta->getUtr(),
            'mode'                     => $fta->getMode(),
            'remarks'                  => $fta->getRemarks(),
            'fta_status'               => $fta->getStatus(),
            'is_fts'                   => $fta->getIsFTS(),
            'bank_status_code'         => $fta->getBankStatusCode(),
            'bank_response_code'       => $fta->getBankResponseCode(),
            'failure_reason'           => $fta->getFailureReason(),
            'channel'                  => $fta->getChannel(),
            Entity::GATEWAY_REF_NO     => $fta->getGatewayRefNo(),
            Entity::SOURCE_ACCOUNT_ID  => $sourceAccountID,
            Entity::BANK_ACCOUNT_TYPE  => $bankAccountType,
            Entity::GATEWAY_ERROR_CODE => $gatewayErrorCode,
            Entity::STATUS_DETAILS     => $statusDetails,
            Entity::FTS_TRANSFER_ID    => $fta->getFTSTransferId()
        ] + $extraInfo;

        if (isset($ftaData['return_utr']) === true)
        {
            $ftaData += [ 'return_utr' => $ftaData['return_utr'] ];
        }

        $this->sourceReconByFta($fta->source, $ftaData);

        if (($fta->getSourceType() === Type::PAYOUT) and
            (in_array($fta->getChannel(), Settlement\Channel::getNonTransactionChannels(), true) === true) and
            ($fta->source->isBalanceAccountTypeDirect() == true))
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

        // Not adding a check for FAV as FTA is deprecated for FAV.
        if ($fta->getSourceType() !== EntityConstant::PAYOUT)
        {
            $this->updateTransactionEntity($fta->source);
        }
    }

    protected function sourceReconByFta($source, array $ftaData)
    {
        $this->trace->info(
            TraceCode::FTA_SOURCE_PROCESSING_DATA,
            (new Redaction())->redactData($ftaData));

        $entityType = null;

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

            $this->trace->count(Metric::WEBHOOK_UPDATE_FAILURE_COUNT,
                                [
                                    'error' => $e->getMessage()
                                ]);

            if ($entityType === EntityConstant::PAYOUT)
            {
                throw $e;
            }
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

        if ((isset($input['extra_info']) === true) and (is_array($input['extra_info']) === true))
        {
            $this->updateExtraInfo($input['extra_info'], $fta);
        }

        if (empty($input[Entity::STATUS]) === false) {
            $fta->setStatus($input[Entity::STATUS]);
        }

        if (empty($input[Entity::FAILURE_REASON]) === false) {
            $fta->setFailureReason($input[Entity::FAILURE_REASON]);
        }

        if (empty($input[Entity::BANK_STATUS_CODE]) === false) {
            $fta->setBankStatusCode($input[Entity::BANK_STATUS_CODE]);
        }

        if (empty($input[Entity::REMARKS]) === false) {
            $fta->setRemarks($input[Entity::REMARKS]);
        }

        if (empty($input[Entity::GATEWAY_REF_NO]) === false) {
            $fta->setGatewayRefNo($input[Entity::GATEWAY_REF_NO]);
        }

        if (empty($input[Entity::CHANNEL]) === false) {
            $fta->setChannel(strtolower($input[Entity::CHANNEL]));
        }

        return $fta;
    }

    protected function updateExtraInfo(array $info, Entity $fta)
    {
        if (isset($info['cms_ref_no']) === true)
        {
            $fta->setCmsRefNo($info['cms_ref_no']);
        }
    }


    /**
     *  It'll evaluate the given source and card details
     * it'll provide the channel from which transfer has to be done
     * and also whether to route it through FTS or not
     * for different reasons. A single method should give us which path should be chosen
     * use RazorX here for easy config
     *
     * TODO: refactor this section so that we don't have to use `shouldUseGateway` and `getChannelForTransfer`
     *
     * @param Base\PublicEntity $source
     * @param Entity $fta
     * @param string $accountType
     * @param CardEntity|null $card
     * @return array
     * @throws LogicException
     */
    protected function getChannelForTransfer(Entity $fta, string $accountType, CardEntity $card = null): array
    {
        $sourceType = $fta->getSourceType();

        $method = 'getChannelFor' . studly_case($sourceType);

        if (method_exists($this, $method) === true)
        {
            return $this->{$method}($fta, $accountType, $card);
        }

        throw new LogicException('Invalid Source Type for Channel');
    }

    protected function getChannelForPayout(Entity $fta, string $accountType, CardEntity $card = null)
    {
        $source = $fta->source;

        $channel = $source->getChannel();

        // Imps payout for channel ICICI go via FTS. Rest via API
        if ($source->getPayoutType() === PayoutEntity::ON_DEMAND)
        {
            if (($channel === Settlement\Channel::ICICI) and ($source->getMode() === Mode::IMPS))
            {
                return [true, $channel];
            }

            return [false, $channel];
        }

        if (empty($channel) === true)
        {
            return [false, Settlement\Channel::YESBANK];
        }

        if ($source->isBalanceTypeBanking() === true)
        {
            return [true, $source->getChannel()];
        }

        return [false, $source->getChannel()];
    }

    protected function getChannelForRefund(Entity $fta,
                                           string $accountType,
                                           CardEntity $card = null): array
    {
        $source = $fta->source;

        $amount = $source->getAmount();

        $mode = $fta->getMode();

        $cardType = null;

        $iin = null;

        if (empty($card) === false)
        {
            $cardType = $card->getType();
        }

        if (empty($card) === false)
        {
            $iin = $card->iinRelation;
        }

        if (empty($iin) === false)
        {
            $issuer = $iin->getIssuer();

            $networkCode = $iin->getNetworkCode();

            $cardType = $iin->getType();
        }

        if (($cardType === \RZP\Models\Card\Type::DEBIT) and
            ($mode === Mode::CT))
        {
            return [true, Settlement\Channel::M2P];
        }

        $key = 'fts_refund_' . strtolower($accountType);

        $this->trace->info(TraceCode::FTA_REFUND_RAMP_INIT, ['key' => $key]);

        $rampOnFts = $this->app->razorx->getTreatment(
            $source->getMerchantId(),
            $key,
            $this->mode
        );

        $this->trace->info(TraceCode::FTA_REFUND_RAMP_COMPLETE,
            [
                'key'           => $key,
                'mode'          => $this->mode,
                'ramp_status'   => $rampOnFts,
            ]);

        if (strtolower($rampOnFts) === 'on')
        {
            //TODO: need to update this when icici supports modes other than IMPS also.
            if ((empty($mode) === true) or ($mode === Mode::IMPS)) {

                if (($accountType === Constants\Entity::CARD) and
                    ($amount <= AttemptConstants::MAX_IMPS_AMOUNT) and
                    (in_array(Mode::IMPS, Mode::getSupportedModes($issuer, $networkCode)))) {
                    return [true, Settlement\Channel::ICICI];
                }

                if (($accountType === Constants\Entity::BANK_ACCOUNT) and
                    ($amount <= AttemptConstants::MAX_IMPS_AMOUNT)) {
                    return [true, Settlement\Channel::ICICI];
                }
            }

            return [true, Settlement\Channel::YESBANK];
        }

        return [false, Settlement\Channel::YESBANK];
    }

    protected function getChannelForFundAccountValidation(Entity $fta,
                                                          string $accountType,
                                                          CardEntity $card = null): array
    {
        $source = $fta->source;

        $key = 'fts_penny_testing_' . strtolower($accountType);

        $this->trace->info(TraceCode::FTA_PENNY_TESTING_RAMP_INIT, ['key' => $key]);

        $rampingOnPennyTesting = $this->app->razorx->getTreatment(
            $source->getMerchantId(),
            $key,
            $this->mode
        );

        $this->trace->info(TraceCode::FTA_PENNY_TESTING_RAMP_COMPLETE,
            [
                'key'           => $key,
                'mode'          => $this->mode,
                'ramp_status'   => $rampingOnPennyTesting,
            ]);

        if(strtolower($rampingOnPennyTesting) === 'on')
        {
            return [false, Settlement\Channel::YESBANK];
        }

        return [true, Settlement\Channel::ICICI];
    }

    protected function getRampingStatus(Base\PublicEntity $source, string $key = ConfigKey::FTS_TEST_MERCHANT)
    {
        $srcMerchantId = $source->getMerchantId();

        $merchantList = $this->app['cache']->get($key);

        $merchantIds = (empty($merchantList) === false) ? explode(',', $merchantList) : [];

        if (in_array($srcMerchantId, $merchantIds, true) === true)
        {
            return true;
        }

        return false;
    }

    public function getAttemptsFromIds(array $ftaIds)
    {
        return $this->repo->fund_transfer_attempt->fetchFtsAttemptUsingId($ftaIds);
    }
}
