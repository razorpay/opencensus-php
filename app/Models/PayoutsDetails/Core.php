<?php

namespace RZP\Models\PayoutsDetails;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Services\UfhService;
use RZP\Models\FileStore\Type;
use RZP\Exception\ServerErrorException;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\PayoutSource\Entity as PayoutSourceEntity;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();
    }

    public function create(array $input, PayoutEntity $payout)
    {
        $payoutId = $payout->getId();

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

        $payoutSource = $payout->getInputSourceDetails();

        if (isset($input[Entity::ADDITIONAL_INFO]) and
            isset($payoutDetails->getAdditionalInfo()[Entity::ATTACHMENTS_KEY]) and
            !isset($payoutSource))
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
                        'error' => $ex->getMessage()
                    ]
                );
            }
        }
    }

    public function uploadAttachment($file, $filename, $entity)
    {
        $ufhService = $this->getUfhService();

        $uniqueId = Base\UniqueIdEntity::generateUniqueId();

        // adding uniqueID to file name
        $modifiedFileName = sprintf('%s_%s', $uniqueId, urlencode(str_replace(' ', '-', $filename)));

        $ufhResponse = $ufhService
            ->uploadFileAndGetUrl($file,
                                 $modifiedFileName,
                                 Type::PAYOUT_ATTACHMENTS,
                                 $entity);

        $this->trace->info(
            TraceCode::PAYOUT_ATTACHMENT_UPLOADED_SUCCESSFULLY,
            $ufhResponse
        );

        $fileId = '';

        if(isset($ufhResponse[Entity::ATTACHMENTS_FILE_ID]))
        {
            $fileId = $ufhResponse[Entity::ATTACHMENTS_FILE_ID];
        }

        return [
            Entity::ATTACHMENTS_FILE_ID     => $fileId,
            Entity::ATTACHMENTS_FILE_NAME   => $filename
        ];
    }

    public function updateAttachments(string $payoutId, array $input): array
    {
        $updateKey = sprintf('%s->%s', Entity::ADDITIONAL_INFO, Entity::ATTACHMENTS_KEY);

        $updates = array($updateKey => $input[Entity::ATTACHMENTS_KEY]);

        try
        {
            $this->repo->payouts_details->updatePayoutDetails([$payoutId], $updates);

            $this->response = [
                Entity::STATUS => Entity::SUCCESS,
            ];
        }
        catch (\Exception $ex)
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
            $this->renameAttachments($payoutId, $input[Entity::ATTACHMENTS_KEY]);
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

        return $this->response;
    }

    public function bulkUpdateAttachments(array $validPayoutIds, array $updateRequest)
    {
        try
        {

            $updateKey = sprintf('%s->%s', Entity::ADDITIONAL_INFO, Entity::ATTACHMENTS_KEY);

            $updates = array($updateKey => $updateRequest[Entity::ATTACHMENTS_KEY]);

            $this
                ->repo
                ->payouts_details
                ->updatePayoutDetails($validPayoutIds, $updates);

            $this->response = [
                Entity::STATUS => 'SUCCESS',
            ];

            return $this->response;
        }
        catch (\Exception $ex)
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
        return $this->repo
            ->payouts_details
            ->getPayoutDetailsByPayoutId($payoutId)
            ->first();
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
                'Could not get attachment for Payout',
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

    private function renameAttachments($payoutId, array $attachments)
    {
        foreach ($attachments as $attachment)
        {
            $fileId = $attachment[Entity::ATTACHMENTS_FILE_ID];

            $fileName = $attachment[Entity::ATTACHMENTS_FILE_NAME];

            $uniqueId = Base\UniqueIdEntity::generateUniqueId();

            $modifiedFileName = sprintf('%s_%s_%s',
                                        sprintf('%s%s', PayoutEntity::getIdPrefix(), $payoutId),
                                        $uniqueId, urlencode(str_replace(' ', '-', $fileName)));

            $this->getUfhService()->renameFile($fileId, $modifiedFileName);
        }
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
                    'payout_id'         => $payoutId,
                    'tax_payment_id'    => $taxPaymentId,
                ],
                $ex
            );
        }
    }
}
