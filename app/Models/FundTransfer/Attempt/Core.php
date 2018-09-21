<?php

namespace RZP\Models\FundTransfer\Attempt;

use Carbon\Carbon;

use RZP\Constants;
use RZP\Exception\InvalidArgumentException;
use RZP\Exception\LogicException;
use RZP\Models\Base;
use RZP\Models\Settlement;
use RZP\Constants\Timezone;
use RZP\Services\Beam\Service;
use RZP\Mail\Base\Constants as MailConstants;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Models\BankAccount\Entity as BankAccountEntity;
use RZP\Models\Payment\Refund\Entity as RefundEntity;

class Core extends Base\Core
{
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
     * Creates FundTransferAttempt entity for enach payments. Channel is default to YESBANK
     * @param Base\Entity $source - currently refund entity
     * @param $channel = Settlement\Channel::YESBANK
    */
    public function createFundTransferAttempt(
        Base\Entity $source,
        string $channel = Settlement\Channel::YESBANK,
        string $purpose = Purpose::REFUND)
    {
        $fundTransferAttempt = new Entity;

        $fundTransferAttempt->merchant()->associate($source->merchant);

        $fundTransferAttempt->source()->associate($source);

        $fundTransferAttempt->bankAccount()->associate($source->bankAccount);

        $values = [
            Entity::INITIATE_AT     => Carbon::now(Timezone::IST)->getTimestamp(),
            Entity::CHANNEL         => $channel,
            Entity::VERSION         => Version::V3,
            Entity::STATUS          => Status::CREATED,
            Entity::PURPOSE         => $purpose,
        ];

        $fundTransferAttempt->fillAndGenerateId($values);

        $this->repo->saveOrFail($fundTransferAttempt);
    }

    /**
     * @param array $input
     * @return array
     * @throws InvalidArgumentException
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

        $jobName  =  $this->getJobNameForBeamPush($channel, $fileType);

        $this->sendFile($filePath, $jobName, $fileType, $channel);

        return [
            'status' => 'Nodal file upload request sent to beam'
        ];
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
