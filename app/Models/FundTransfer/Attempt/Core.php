<?php

namespace RZP\Models\FundTransfer\Attempt;

use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Jobs\FundTransfer;
use RZP\Models\Settlement;
use RZP\Models\FundAccount;
use RZP\Constants\Timezone;
use RZP\Services\Beam\Service;
use RZP\Models\Admin\ConfigKey;
use RZP\Exception\LogicException;
use RZP\Models\Vpa\Entity as VpaEntity;
use RZP\Models\Card\Entity as CardEntity;
use RZP\Models\Card\Issuer as CardIssuer;
use RZP\Mail\Base\Constants as MailConstants;
use RZP\Jobs\FTS\FundTransfer as FtsFundTransfer;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Models\BankAccount\Entity as BankAccountEntity;
use RZP\Models\FundTransfer\Base\Initiator\NodalAccount;
use RZP\Models\FundTransfer\Attempt\Constants as AttemptConstants;

class Core extends Base\Core
{
    public function createWithBankAccount(
        Base\Entity $source,
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

        if ($instantDispatch === true)
        {
            $this->dispatchForTransfer($fundTransferAttempt);
        }
        else if ($fundTransferAttempt->getIsFTS() === true)
        {
            $this->sendFTSFundTransferRequest($fundTransferAttempt, FundAccount\Type::BANK_ACCOUNT);
        }

        return $fundTransferAttempt;
    }

    public function createWithCard(Base\Entity $source, CardEntity $card, array $values = []): Entity
    {
        $fundTransferAttempt = $this->create($source, $values, $card);

        // TODO: Make this polymorphic instead of having bankAccount and vpa separately
        $fundTransferAttempt->card()->associate($card);

        // This needs to be done after filling FTA since it uses getters on the entity.
        // Also, this needs to be done after associating vpa or bank_account only
        // because it needs the association to figure out the destination type.
        $fundTransferAttempt->getValidator()->validateModeIfSet($values);

        $this->repo->saveOrFail($fundTransferAttempt);

        if ($fundTransferAttempt->getIsFTS() === true)
        {
            $this->sendFTSFundTransferRequest($fundTransferAttempt, FundAccount\Type::BANK_ACCOUNT);
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
            $this->trace->info(TraceCode::FTA_TRANSFER_DISPATCH_FAILED, [
                'fta_id' => $fta->getId(),
            ]);
        }
    }

    public function createWithVpa(Base\Entity $source, VpaEntity $vpa, array $values = []): Entity
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
            $this->sendFTSFundTransferRequest($fundTransferAttempt, FundAccount\Type::BANK_ACCOUNT);
        }

        return $fundTransferAttempt;
    }

    /**
     * @param array $input
     *
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

        $this->sendFile($filePath, $jobName, $fileType, $channel);

        return [
            'status' => 'Nodal file upload request sent to beam'
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
     * @param Base\Entity     $source
     * @param string          $sourceType
     * @param CardEntity|null $card
     * @param array           $values
     * @return array
     */
    protected function getChannelForTransfer(Base\Entity $source, string $sourceType, CardEntity $card = null, array $values = []): array
    {
        if (in_array($sourceType, AttemptConstants::ALLOWED_PRODUCTS_ON_FTS, true) === true)
        {
            $redis = $this->app['redis']->connection();

            $ftsChannels = $redis->SMEMBERS(ConfigKey::FTS_CHANNELS);

            $amount = $source->getAmount();

            $validCardRefund = $this->isFTSSupportedCardRefund($card);

            //
            // check if card is valid and channel is active to accept traffic at FTS side
            //
            if (($validCardRefund === false) or
                (in_array(Settlement\Channel::ICICI, $ftsChannels, true) === false))
            {
                return [false, Settlement\Channel::YESBANK];
            }

            if($amount < NodalAccount::MAX_IMPS_AMOUNT)
            {
                $randomValue = mt_rand(1, 100);
                $requestThreshold = (int) $this->app['cache']->get(ConfigKey::FTS_REQUEST_PERCENTAGE);

                if ($randomValue <= $requestThreshold)
                {
                    return [true, Settlement\Channel::ICICI];
                }
            }
        }

        //
        // If the transfer is not refund type then use the channel given in values
        // if there are not channel specified in the values the use yesbank as default channel
        //
        $channel = $values[Entity::CHANNEL] ?? Settlement\Channel::YESBANK;

        return [false, $channel];
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

        if ($iin !== null)
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
     * @param Base\Entity     $source - currently refund entity
     * @param array           $values Attributes of the created FTA
     * @param CardEntity|null $card
     *
     * @return Entity
     */
    protected function create(Base\Entity $source, array $values = [], CardEntity $card = null)
    {
        $fundTransferAttempt = new Entity;

        $fundTransferAttempt->merchant()->associate($source->merchant);

        $fundTransferAttempt->source()->associate($source);

        list($isFTS, $channel) = $this->getChannelForTransfer($source, $fundTransferAttempt->getSourceType(), $card, $values);

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

                    default:
                        throw new LogicException('Invalid settlement channel', null, $channel);
                }

            case Entity::BENEFICIARY:
                switch ($channel)
                {
                    case Settlement\Channel::ICICI:
                        return BeamConstants::ICICI_BENEFICIARY_JOB_NAME;

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

        $this->app['beam']->beamPush($data, $timelines, $mailInfo);
    }

    /**
     * @param Entity $fta
     * @param string $accountType
     * @param bool   $isRegistered
     */
    public function sendFTSFundTransferRequest(Entity $fta, string $accountType, bool $isRegistered = false)
    {
        try
        {
            $redis = $this->app['redis']->connection();

            $ftsChannels = $redis->SMEMBERS(ConfigKey::FTS_CHANNELS);

            if(in_array($fta->getChannel(), $ftsChannels, true) === false)
            {
                $this->trace->info(
                    TraceCode::FTS_INVALID_CHANNEL,
                    [
                        'channel' => $fta->getChannel(),
                    ]);

                return;
            }

            FtsFundTransfer::dispatch($this->mode, $fta->getId(), $accountType, $isRegistered);

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

            if(empty($input[Entity::UTR]) === false)
            {
                $fta->setUtr($input[Entity::UTR]);
            }

            $fta->fill($input);

            $fta->source->setFTSTransferId($input[Entity::FUND_TRANSFER_ID]);

            $fta->source->fill($input);

            $this->repo->transaction(function() use ($fta)
            {
                $this->repo->fund_transfer_attempt->saveOrFail($fta);

                $this->repo->saveOrFail($fta->source);
            });
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
        }
    }
}
