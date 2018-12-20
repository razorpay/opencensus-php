<?php

namespace RZP\Models\FundTransfer\Attempt;

use Carbon\Carbon;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Settlement;
use RZP\Constants\Timezone;
use RZP\Services\Beam\Service;
use RZP\Exception\LogicException;
use RZP\Models\Vpa\Entity as VpaEntity;
use RZP\Mail\Base\Constants as MailConstants;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Models\BankAccount\Entity as BankAccountEntity;

class Core extends Base\Core
{
    public function createWithBankAccount(
        Base\Entity $source,
        BankAccountEntity $bankAccount,
        array $values = []): Entity
    {
        $fundTransferAttempt = $this->create($source, $values);

        // TODO: Make this polymorphic instead of having bankAccount and vpa separately
        $fundTransferAttempt->bankAccount()->associate($bankAccount);

        // This needs to be done after associating bank account only
        // because it needs the association to figure out destination bank
        $fundTransferAttempt->modifyModeIfRequired();

        $this->repo->saveOrFail($fundTransferAttempt);

        return $fundTransferAttempt;
    }

    public function createWithVpa(Base\Entity $source, VpaEntity $vpa, array $values = []): Entity
    {
        $fundTransferAttempt = $this->create($source, $values);

        // TODO: Make this polymorphic instead of having bankAccount and vpa separately
        $fundTransferAttempt->vpa()->associate($vpa);

        $this->repo->saveOrFail($fundTransferAttempt);

        return $fundTransferAttempt;
    }

    /**
     * @param array $input
     *
     * @return array
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
     * @param Base\Entity $source - currently refund entity
     * @param array       $values Attributes of the created FTA
     *
     * @return Entity
     */
    protected function create(Base\Entity $source, array $values = [])
    {
        $fundTransferAttempt = new Entity;

        $fundTransferAttempt->merchant()->associate($source->merchant);

        $fundTransferAttempt->source()->associate($source);

        $defaultValues = [
            Entity::INITIATE_AT => Carbon::now(Timezone::IST)->getTimestamp(),
            Entity::CHANNEL     => Settlement\Channel::YESBANK,
            Entity::VERSION     => Version::V3,
            Entity::STATUS      => Status::CREATED,
            Entity::PURPOSE     => Purpose::REFUND,
        ];

        $values = array_merge($defaultValues, $values);

        $fundTransferAttempt->fillAndGenerateId($values);

        // this needs to be done after filling FTA
        // since it uses getters on the entity
        $fundTransferAttempt->getValidator()->validateModeIfSet($values);

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
}
