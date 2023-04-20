<?php

namespace RZP\Models\PayoutsDetails;

use Symfony\Component\HttpFoundation\File\UploadedFile;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Services\UfhService;
use RZP\Models\FileStore\Type;
use RZP\Exception\ServerErrorException;
use RZP\Models\Payout\Core as PayoutCore;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Payout\DualWrite\PayoutDetails as PayoutDetailsDualWrite;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();
    }

    public function create(array $input, PayoutEntity $payout)
    {
        $payoutId = $payout->getId();

        $payoutDetails = $this->createBaseEntity($input, $payoutId);

        $payoutSource = $payout->getInputSourceDetails();

        // rename attachment when payout is a vanilla payout .i.e. no payout source
        if ((isset($input[Entity::ADDITIONAL_INFO]) === true) and
            (isset($payoutDetails->getAdditionalInfo()[Entity::ATTACHMENTS_KEY]) === true) and
            (isset($payoutSource) === false))
        {
            try
            {
                $this->renameAttachments($payoutId, $payoutDetails->getAttachmentsAttribute());
            }
            catch (\Exception $ex)
            {
                $this->trace->error(
                    TraceCode::PAYOUT_ATTACHMENT_RENAME_FAILURE,
                    [
                        'payout_id' => $payoutId,
                        'error'     => $ex->getMessage()
                    ]
                );
            }
        }
    }

    public function uploadAttachment(UploadedFile $file, string $filename, MerchantEntity $merchant)
    {
        $ufhService = $this->getUfhService();

        $uniqueId = Base\UniqueIdEntity::generateUniqueId();

        $modifiedFileName = str_replace(' ', '-', preg_replace('/[^A-Za-z0-9 .]/', '', trim($filename)));

        $modifiedFileName = sprintf('%s_%s', $uniqueId, $modifiedFileName);

        $ufhResponse = $ufhService
            ->uploadFileAndGetUrl($file,
                                  $modifiedFileName,
                                  Type::PAYOUT_ATTACHMENTS,
                                  $merchant);

        $this->trace->info(
            TraceCode::PAYOUT_ATTACHMENT_UPLOADED_SUCCESSFULLY,
            $ufhResponse
        );

        $fileId = '';

        $fileIdHash = '';

        if(isset($ufhResponse[Entity::ATTACHMENTS_FILE_ID]) === true)
        {
            $fileId = $ufhResponse[Entity::ATTACHMENTS_FILE_ID];

            $fileIdHash = Utils::generateAttachmentFileIdHash($fileId);
        }

        return [
            Entity::ATTACHMENTS_FILE_ID     => $fileId,
            Entity::ATTACHMENTS_FILE_NAME   => $filename,
            Entity::ATTACHMENTS_FILE_HASH   => $fileIdHash
        ];
    }

    public function updateAttachments(string $payoutId, array $input): array
    {
        $attachmentsInfo = Utils::prepareAttachmentInfoFromInput($input);

        try
        {
            $payoutServicePayout = $this->repo->payout->getPayoutServicePayout($payoutId);

            if (empty($payoutServicePayout) === false)
            {
                return (new PayoutCore)->updateAttachmentsForPayoutServicePayout($payoutId, $input);
            }

            /** @var Base\PublicCollection $payoutDetails */
            $payoutDetails = $this->repo->payouts_details->getPayoutDetailsByPayoutId($payoutId);

            $attachments = array_pull($input, Entity::ATTACHMENTS_KEY, []);

            if ($payoutDetails->isNotEmpty() === true)
            {
                $payoutDetail = $payoutDetails->first();

                $additionalInfo = json_decode($payoutDetail[Entity::ADDITIONAL_INFO], true);

                $additionalInfo[Entity::ATTACHMENTS] = $attachmentsInfo;

                $additionalInfo = json_encode($additionalInfo, true);

                $updates = array(Entity::ADDITIONAL_INFO => $additionalInfo);

                $this->repo->payouts_details->updatePayoutDetails([$payoutId], $updates);
            }
            else
            {
                $additionalInfo = [
                    Entity::ATTACHMENTS => $attachmentsInfo
                ];

                $input[Entity::ADDITIONAL_INFO] = json_encode($additionalInfo, true);

                $this->createBaseEntity($input, $payoutId);
            }

            $this->response = [
                Entity::STATUS => Entity::SUCCESS,
            ];
        }
        catch (\Throwable $ex)
        {
            throw new ServerErrorException(
                'Could not update attachment for Payout',
                ErrorCode::SERVER_ERROR_ATTACHMENT_UPDATE_FAILURE,
                [
                    'payout_id' => $payoutId,
                    'input'     => $input,
                ],
                $ex
            );
        }

        try
        {
            $this->renameAttachments($payoutId, $attachments);
        }
        catch (\Throwable $ex)
        {
            $this->trace->error(
                TraceCode::PAYOUT_ATTACHMENT_RENAME_FAILURE,
                [
                    'payout_id' => $payoutId,
                    'error'     => $ex->getMessage()
                ]
            );
        }

        return $this->response;
    }

    public function bulkUpdateAttachments(array $validPayoutIds, array $updateRequest)
    {
        try
        {
            $payoutServicePayoutIds = $this->repo->payout->getPayoutServicePayoutIds($validPayoutIds);

            if (empty($payoutServicePayoutIds) === false)
            {
                $validPayoutIds = array_diff($validPayoutIds, $payoutServicePayoutIds);

                $response = (new PayoutCore)->bulkUpdateAttachmentsForPayoutServicePayout($payoutServicePayoutIds,
                                                                                     $updateRequest);

                if (empty($validPayoutIds) === true)
                {
                    return $response;
                }
            }

            // used to store the Payout Ids with existing rows in payouts_details
            $existingPayoutIds = array();

            foreach ($validPayoutIds as $payoutId)
            {
                /** @var Base\PublicCollection $payoutDetails */
                $payoutDetails = $this->repo->payouts_details->getPayoutDetailsByPayoutId($payoutId);

                if ($payoutDetails->isNotEmpty() === true)
                {
                    array_push($existingPayoutIds, $payoutId);
                }
                else
                {
                    $additionalInfo[Entity::ATTACHMENTS] = $updateRequest[Entity::ATTACHMENTS_KEY];

                    $input[Entity::ADDITIONAL_INFO] = json_encode($additionalInfo, true);

                    $this->createBaseEntity($input, $payoutId);
                }
            }

            if (count($existingPayoutIds) > 0)
            {
                $updateKey = sprintf('%s->%s', Entity::ADDITIONAL_INFO, Entity::ATTACHMENTS_KEY);

                $updates = array($updateKey => $updateRequest[Entity::ATTACHMENTS_KEY]);

                $this->repo->payouts_details->updatePayoutDetails($existingPayoutIds, $updates);
            }

            $this->response = [
                Entity::STATUS => 'SUCCESS',
            ];

            return $this->response;
        }
        catch (\Throwable $ex)
        {
            throw new ServerErrorException(
                'Could not update attachment for Payout',
                ErrorCode::SERVER_ERROR_ATTACHMENT_UPDATE_FAILURE,
                [
                    'payout_ids'     => $validPayoutIds,
                    'update_request' => $updateRequest,
                ],
                $ex
            );
        }
    }

    public function getPayoutDetailsById(string $payoutId)
    {
        $payoutDetails = $this->repo->payouts_details->getPayoutDetailsByPayoutId($payoutId)->first();

        /*
         * Doing this for all requests because we want to load the freshest payout details updated in payout service for
         * having no lag on merchant dashboard. if we try to do it only for payout service payouts, we would still have
         * to do an additional db query to fetch the payout to check is_payout_service flag so it's better to directly
         * fetch payout details from payout service.
         */
        $payoutServicePayoutDetails = (new PayoutDetailsDualWrite)->getAPIPayoutsDetailsFromPayoutService($payoutId);

        // Choosing the most updated payout details
        if (empty($payoutServicePayoutDetails) === false)
        {
            $payoutDetails = $payoutServicePayoutDetails;
        }

        return $payoutDetails;
    }

    public function getAttachmentSignedUrl(string $attachmentId)
    {
        try
        {
            $ufhService = $this->getUfhService();

            $response = $ufhService->getSignedUrl($attachmentId);

            $this->trace->info(
                TraceCode::PAYOUT_DETAILS_ATTACHMENT_GET_SIGNED_URL_RESPONSE,
                $response
            );

            return $response;
        }
        catch (\Exception $ex)
        {
            throw new ServerErrorException(
                'Could not get signed URL for the given attachment id',
                ErrorCode::SERVER_ERROR_ATTACHMENT_GET_FAILURE,
                [
                    'attachment_id' => $attachmentId,
                ],
                $ex
            );
        }
    }

    public function getAttachmentDetails(string $attachmentId, string $merchantId)
    {
        try
        {
            $ufhService = $this->getUfhService();

            $response = $ufhService->getFileDetails($attachmentId, $merchantId);

            $this->trace->info(
                TraceCode::PAYOUT_ATTACHMENT_GET_DETAILS_RESPONSE,
                $response
            );

            return $response;
        }
        catch (\Exception $ex)
        {
            throw new ServerErrorException(
                'Could not get details for the given attachment id',
                ErrorCode::SERVER_ERROR_ATTACHMENT_GET_FAILURE,
                [
                    'attachment_id' => $attachmentId,
                ],
                $ex
            );
        }
    }

    protected function getUfhService()
    {
        $ufhServiceMock = $this->app['config']->get('applications.ufh.mock');

        if ($ufhServiceMock === false)
        {
            $this->ufhService = new UfhService($this->app,
                                               $this->app['basicauth']->getMerchantId(),
                                               EntityConstants::PAYOUT);
        }
        else
        {
            $this->ufhService = new \RZP\Services\Mock\UfhService($this->app);
        }

        if (is_null($this->ufhService) == true)
        {
            $this->trace->info(
                TraceCode::PAYOUT_DETAILS_UFH_SERVICE_NULL,
                [
                    'ufh_service' => $this->ufhService,
                ]
            );

            throw new ServerErrorException(
                'Could not get UFH Client',
                ErrorCode::SERVER_ERROR_INVALID_UFH_CLIENT,
                null
            );
        }

        return $this->ufhService;
    }

    public function renameAttachments($payoutId, array $attachments)
    {
        $responseArray = [];

        foreach ($attachments as $attachment)
        {
            $fileId = $attachment[Entity::ATTACHMENTS_FILE_ID];

            $fileName = $attachment[Entity::ATTACHMENTS_FILE_NAME];

            $uniqueId = Base\UniqueIdEntity::generateUniqueId();

            $fileName = str_replace(' ', '-', preg_replace('/[^A-Za-z0-9 .]/', '', trim($fileName)));

            $fileName = sprintf('%s_%s', $uniqueId, $fileName);

            $response = $this->getUfhService()->renameFile($fileId, $fileName);

            $responseArray[] = $response;
        }

        return $responseArray;
    }

    public function updateTaxPayment(string $payoutId, string $taxPaymentId)
    {
        try
        {
            $update = [
                Entity::TAX_PAYMENT_ID => $taxPaymentId
            ];

            $this->repo
                ->payouts_details
                ->updatePayoutDetails([$payoutId], $update);

            return [
                Entity::STATUS => 'SUCCESS',
            ];
        }
        catch (\Exception $ex)
        {
            throw new ServerErrorException(
                'Could not update tax payment ID for Payout',
                ErrorCode::SERVER_ERROR_TAX_PAYMENT_ID_UPDATE_FAILURE,
                [
                    'payout_id'      => $payoutId,
                    'tax_payment_id' => $taxPaymentId,
                ],
                $ex
            );
        }
    }

    private function createBaseEntity(array $input, string $payoutId): Entity
    {
        $input[Entity::PAYOUT_ID] = $payoutId;

        $this->trace->info(
            TraceCode::PAYOUT_DETAILS_ENTITY_CREATE_REQUEST,
            $input
        );

        $payoutDetails = (new Entity)->build($input);

        $this->repo->saveOrFail($payoutDetails);

        $this->trace->info(
            TraceCode::PAYOUT_DETAILS_ENTITY_CREATED,
            $payoutDetails->toArray()
        );

        return $payoutDetails;
    }

    public function getAttachmentIdsByPayoutIds(array $payoutIds): array {
        try
        {
            $payoutDetails = $this->repo->payouts_details->getPayoutDetailsByPayoutIds($payoutIds);

            if ($payoutDetails->isEmpty() == true)
            {
                // no entry in payout_details, so no attachments available for the payoutIds
                $this->trace->info(
                    TraceCode::ATTACHMENTS_NOT_FOUND_FOR_GIVEN_PAYOUT_IDS,
                    [
                        'payout_ids' => $payoutIds,
                    ]
                );
                return [];
            }

            /*
             * Stores file_id to payout_ids mapping
             * Example: Consider the below cases
             * 1. payout_1, payout_2, payout_3 are created from payout_link_1 which has a file with id as file_id_123
             * 2. payout_4, payout_5 are created from payout_link_2 which has a file with id as file_id_456
             * Then $attachmentIds will be
             * [
             *      file_id_123 => [payout_1, payout_2, payout_3],
             *      file_id_456 => [payout_4, payout_5],
             * ]
             *
             */
            $attachmentIds = array();

            foreach ($payoutDetails as $payoutDetail)
            {
                $payoutId = Entity::PAYOUT_PUBLIC_SIGN . '_' . $payoutDetail[Entity::PAYOUT_ID];

                $additionalInfo = json_decode($payoutDetail[Entity::ADDITIONAL_INFO], true);

                if (isset($additionalInfo[Entity::ATTACHMENTS]))
                {
                    $attachments = $additionalInfo[Entity::ATTACHMENTS];

                    foreach ($attachments as $attachment)
                    {
                        if (isset($attachment[Entity::ATTACHMENTS_FILE_ID]))
                        {
                            $fileId = $attachment[Entity::ATTACHMENTS_FILE_ID];

                            $existingPayoutIds = array();

                            if (isset($attachmentIds[$fileId]))
                            {
                                $existingPayoutIds = $attachmentIds[$fileId];
                            }

                            array_push($existingPayoutIds, $payoutId);

                            $attachmentIds[$fileId] = $existingPayoutIds;
                        }
                    }
                }
            }

            if (sizeof($attachmentIds) == 0)
            {
                // no attachments found for the payouts
                $this->trace->info(
                    TraceCode::ATTACHMENTS_NOT_FOUND_FOR_GIVEN_PAYOUT_IDS,
                    [
                        'payout_ids' => $payoutIds,
                    ]
                );
                return [];
            }

            return $attachmentIds;
        }
        catch (\Exception $ex)
        {
            throw new ServerErrorException(
                'Cannot get attachments for the given payouts',
                ErrorCode::SERVER_ERROR_GET_ATTACHMENTS_FAILURE,
                [
                    'payout_ids' => $payoutIds,
                ],
                $ex
            );
        }
    }
}
