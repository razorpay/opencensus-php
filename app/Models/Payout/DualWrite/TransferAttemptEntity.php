<?php

namespace RZP\Models\Payout\DualWrite;

use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Entity as EntityConstant;
use RZP\Exception;
use RZP\Constants;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Payout\Entity;
use RZP\Models\Payout\Status;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\FundTransfer\Attempt\Entity as TransferEntity;



class TransferAttemptEntity extends Base
{
    public function dualWriteFundTransferEntity(string $id)
    {
        try
        {
            $this->trace->info(
                TraceCode::PAYOUT_SERVICE_DUAL_WRITE_TRANSFER_ATTEMPT_ENTITY_INIT,
                ['payout_id' => $id]
            );

            $psPayout = (new Payout())->getPayoutFromPayoutServiceWithoutModifications($id);
            $psPayout = get_object_vars($psPayout);

            if (empty($psPayout) === true)
            {
                $this->trace->info(
                    TraceCode::PAYOUT_SERVICE_DUAL_WRITE_TRANSFER_ATTEMPT_ENTITY_PAYOUT_NOT_FOUND,
                    ['payout_id' => $id]
                );
                return null;
            }

            $requestPayload = [
                'id' => $psPayout[Entity::MERCHANT_ID],
                'experiment_name' => RazorxTreatment::FTS_REQUEST_FROM_PAYOUTS_SERVICE,
                'request_data'  => json_encode(['id' =>  $psPayout[Entity::MERCHANT_ID]]),
            ];

            if ((new Merchant\Core())->isSplitzExperimentEnable($requestPayload,RazorxTreatment::FTS_REQUEST_FROM_PAYOUTS_SERVICE) !== true)
            {

                $this->trace->info(
                    TraceCode::PAYOUT_SERVICE_DUAL_WRITE_TRANSFER_ATTEMPT_ENTITY_SPLITZ_DISABLE,
                    ['payout_id' => $id]
                );
                return null;
            }
            /*
             * Same Splitz Experiment is used at Payouts Service for Fund Transfer Service Request via PS
             * There can be a possibility when the splitz is enabled older dual write message
             * whose FTS request is gone from API Monolith goes through this flow
             * So to avoid that extra check is used of destination_id (bank_account_id,vpa_id,card_id)
             * which gets set in the flow where FTS request is made from payouts Service
             */

            if (array_key_exists(Entity::DESTINATION_ID, $psPayout) === false
                || empty($psPayout[Entity::DESTINATION_ID]) === true)
            {
                $this->trace->info(
                    TraceCode::PAYOUT_SERVICE_DUAL_WRITE_DESTINATION_ID_NOT_FOUND,
                    ['payout_id' => $id]
                );
                return null;
            }

            /** @var \RZP\Models\FundTransfer\Attempt\Entity $transferAttemptEntity */

            $transferAttemptEntity = $this->repo->fund_transfer_attempt->getAttemptByFTSTransferId($psPayout[Entity::FTS_TRANSFER_ID]);

            if ( ($transferAttemptEntity === null) and  (isset($id) === true))
            {
                $transferAttemptEntity = $this->repo
                    ->fund_transfer_attempt
                    ->getFTSAttemptBySourceId(
                        $id,
                        'payout',
                        true);

            }

            $transferAttemptAttribute = $this->getFundTransferEntityFromPayoutsService($id, $psPayout);

            $this->trace->info(
                TraceCode::PAYOUT_SERVICE_DUAL_WRITE_TRANSFER_ATTEMPT_ENTITY_ATTRIBUTES,
                [
                    'payout_id' => $id,
                    'transfer_attempt_entity_attributes' => $transferAttemptAttribute,
                    'transfer_attempt_entity' => $transferAttemptEntity,
                ]
            );

            if (empty($transferAttemptEntity) === true)
            {
                $transferAttemptEntity = new TransferEntity;

                $transferAttemptEntity->setRawAttributes($transferAttemptAttribute);

                $transferAttemptEntity->generateId();

                // Explicitly setting the connection.
                $transferAttemptEntity->setConnection($this->mode);

                $this->trace->info(
                    TraceCode::PAYOUT_SERVICE_DUAL_WRITE_TRANSFER_ATTEMPT_ENTITY_CREATION,
                    [
                        'payout_id' => $id,
                        'transfer_attempt_entity' => $transferAttemptEntity,
                    ]
                );

            }
            else
            {
                $transferAttemptEntity->setRawAttributes($transferAttemptAttribute);

                // Explicitly setting the connection.
                $transferAttemptEntity->setConnection($this->mode);

                $this->trace->info(
                    TraceCode::PAYOUT_SERVICE_DUAL_WRITE_TRANSFER_ATTEMPT_ENTITY_UPDATION,
                    [
                        'payout_id' => $id,
                        'transfer_attempt_entity' => $transferAttemptEntity,
                    ]
                );

            }

            $this->repo->fund_transfer_attempt->saveOrFail($transferAttemptEntity);


            $timestamp = Carbon::now(Timezone::IST)->getTimestamp();

            $this->trace->info(
                TraceCode::PAYOUT_SERVICE_DUAL_WRITE_TRANSFER_ATTEMPT_ENTITY_DONE,
                [
                    'payout_id' => $id,
                    'timestamp' => $timestamp,
                    'status' => $transferAttemptAttribute[Entity::STATUS],
                ]
            );

            return $transferAttemptEntity;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAYOUTS_SERVICE_TRANSFER_ENTITY_UPSERT_FAILED,
                [
                    'entity_id' => $id,
                    'entity_type' => EntityConstant::PAYOUT,
                ]
            );
            return null;
        }

    }

    public function getPayoutInitiatedAtFromPayoutService(string $payoutId)
    {

        /*
         * Till this point Payout Entity has been dual written to API Monolith from Payouts Service
         * Directly using API Monolith Payout Entity to get the initiated_at timestamp
         */
        /** @var Entity $apiPayout */
        $apiPayout = $this->repo->payout->find($payoutId);

        if (empty($apiPayout) === true)
        {
            $this->trace->info(
                TraceCode::PAYOUT_SERVICE_DUAL_WRITE_PAYOUT_NOT_FOUND,
                ['payout_id' => $payoutId]
            );
            return null;
        }

        return $apiPayout->getInitiatedAt();

    }

    public function getFundTransferEntityFromPayoutsService(string $payoutId, $psPayout)
    {
        $transferAttemptAttribute = array();

        $payoutPermanentMeta = $this->getPayoutPermanentMetaDataFromPayoutServiceForDualWrite($payoutId, 'fund_transfer_service_info_meta');

        /*
         * Fund Transfer Service Info Meta will be available only
         * when the FTS request is made from Payouts Service
         * and FTS Webhook request has come to Payouts Service in Backward Flow
         */
        if (empty($payoutPermanentMeta) === true)
        {
            $this->trace->info(
                TraceCode::PAYOUT_SERVICE_DUAL_WRITE_TRANSFER_ATTEMPT_ENTITY_META_VALUE_NOT_FOUND,
                ['payout_id' => $payoutId]
            );
        }
        else
        {
            $metaData = get_object_vars(json_decode($payoutPermanentMeta['meta_value']));
            if (empty($metaData) === false)
            {
                $transferAttemptAttribute[TransferEntity::CMS_REF_NO]           = $metaData[TransferEntity::CMS_REF_NO];
                $transferAttemptAttribute[TransferEntity::DATE_TIME]            = $metaData[TransferEntity::DATE_TIME];
            }

        }


        switch ($psPayout[Entity::DESTINATION_TYPE])
        {
            case Constants\Entity::BANK_ACCOUNT:

                $transferAttemptAttribute[TransferEntity::BANK_ACCOUNT_ID] = $psPayout[Entity::DESTINATION_ID];
                break;

            case Constants\Entity::VPA:
                $transferAttemptAttribute[TransferEntity::VPA_ID] = $psPayout[Entity::DESTINATION_ID];
                break;

            case Constants\Entity::CARD:
                $transferAttemptAttribute[TransferEntity::CARD_ID] = $psPayout[Entity::DESTINATION_ID];

                break;

            case Constants\Entity::WALLET_ACCOUNT:
                $transferAttemptAttribute[TransferEntity::WALLET_ACCOUNT_ID] = $psPayout[Entity::DESTINATION_ID];
                break;

            default:
                throw new Exception\InvalidArgumentException(
                    'Payout fta destination entity is invalid. '. $psPayout[Entity::DESTINATION_ID],
                    [
                        'payout_id'             => $payoutId,

                    ]);
        }

        $transferAttemptAttribute[TransferEntity::MERCHANT_ID]          = $psPayout[Entity::MERCHANT_ID];
        $transferAttemptAttribute[TransferEntity::PURPOSE]              = $psPayout[Entity::PURPOSE_TYPE];
        $transferAttemptAttribute[TransferEntity::SOURCE_TYPE]          = Entity::PAYOUT;
        $transferAttemptAttribute[TransferEntity::SOURCE_ID]            = $psPayout[Entity::ID];
        $transferAttemptAttribute[TransferEntity::CHANNEL]              = $psPayout[Entity::CHANNEL];
        $transferAttemptAttribute[TransferEntity::VERSION]              = Attempt\Version::V3;
        $transferAttemptAttribute[TransferEntity::BANK_STATUS_CODE]     =$psPayout[Entity::STATUS_CODE];
        $transferAttemptAttribute[TransferEntity::MODE]                 = $psPayout[Entity::MODE];
        $transferAttemptAttribute[TransferEntity::STATUS]               = $psPayout[Entity::STATUS];
        $transferAttemptAttribute[TransferEntity::IS_FTS]               = 1 ;
        $transferAttemptAttribute[TransferEntity::FTS_TRANSFER_ID]      = $psPayout[Entity::FTS_TRANSFER_ID];
        $transferAttemptAttribute[TransferEntity::UTR]                  = $psPayout[Entity::UTR];
        $transferAttemptAttribute[TransferEntity::NARRATION]            = $psPayout[Entity::NARRATION];
        $transferAttemptAttribute[TransferEntity::REMARKS]              = $psPayout[Entity::REMARKS];
        $transferAttemptAttribute[TransferEntity::FAILURE_REASON]       = $psPayout[Entity::FAILURE_REASON];
        $transferAttemptAttribute[TransferEntity::GATEWAY_REF_NO]       = $psPayout[TransferEntity::GATEWAY_REF_NO];
        $transferAttemptAttribute[TransferEntity::INITIATE_AT]          = $this->getPayoutInitiatedAtFromPayoutService($payoutId);

        return $transferAttemptAttribute;

    }


    public function getPayoutPermanentMetaDataFromPayoutServiceForDualWrite(string $payoutId, string $metaName)
    {
        $metadata = $this->repo->payout->getPayoutServicePayoutMetaPermanent($payoutId, $metaName);

        if (count($metadata) === 0)
        {
            return [];
        }

        $metadata = $metadata[0];

        return get_object_vars($metadata);
    }
}
