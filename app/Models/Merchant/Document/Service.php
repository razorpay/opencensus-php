<?php

namespace RZP\Models\Merchant\Document;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity;
use RZP\Models\GenericDocument;
use RZP\Models\Merchant\Account;
use RZP\Models\Merchant\Stakeholder;
use RZP\Error\PublicErrorDescription;


class Service extends Base\Service
{
    use Base\Traits\ServiceHasCrudMethods;

    /**
     * @var Core
     */
    protected $core;

    /**
     * @var Repository
     */
    protected $entityRepo;

    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;

        $this->mutex = $this->app['api.mutex'];

        $this->entityRepo = $this->repo->merchant_document;
    }

    /**
     * upload a document in MerchantDocument table
     *
     * @param array $input
     *
     * @return array
     */
    public function uploadActivationFileMerchant(array $input)
    {
        $merchant = $this->merchant;

        return $this->mutex->acquireAndRelease(

            $merchant->getId(),

            function() use ($merchant, $input) {

                return $this->core->uploadActivationFile($this->merchant, $input);
            },

            Merchant\Constants::MERCHANT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_MERCHANT_EDIT_OPERATION_IN_PROGRESS,
            Merchant\Constants::MERCHANT_MUTEX_RETRY_COUNT);
    }

    public function fetchActivationFilesFromDocument(string $mid = null)
    {
        $mid = $mid ?? $this->merchant->getId();

        return $this->core->fetchActivationFilesFromDocument($mid);
    }


    public function delete(string $id)
    {
        $entity = $this->entityRepo->findByPublicIdAndMerchant($id, $this->merchant);

        $response = $this->core->delete($entity);

        return $response;
    }

    public function getDocuments(string $accountId, string $entityType, string $entityId)
    {
        Account\Entity::verifyIdAndStripSign($accountId);
        $account = $this->repo->merchant->findOrFailPublic($accountId);
        [$entity, $merchant] = $this->validateAndGetDocumentRequest($account, $entityType, $entityId);

        return (new DocumentResponse)->linkDocumentsResponse($merchant, $entityType, $entity->getId());
    }

    public function linkDocuments(string $accountId, string $entityType, string $entityId, array $input)
    {
        [$entity, $merchant] = $this->validateDocumentLinkRequestAndGetEntities($input, $entityId, $entityType, $accountId);

        $entityId = $entity->getId();

        $lockId = 'DOCUMENT_LINK_' . $entityId;

        $this->mutex->acquireAndRelease($lockId, function() use ($entity, $merchant, $input) {
            $this->repo->transaction(function() use ($entity, $merchant, $input) {

                foreach ($input as $proofType => $filesArr)
                {
                    foreach ($filesArr as $file)
                    {
                        $fileAttributes = [
                            Constants::FILE_ID => $file[Constants::FILE_ID],
                            Constants::SOURCE  => Source::UFH,
                        ];

                        $this->core->saveMerchantDocument($merchant, $file[Constants::TYPE], $fileAttributes, $entity);
                    }
                }
            });
        },
            Merchant\Constants::MERCHANT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_DOCUMENT_LINK_OPERATION_IN_PROGRESS,
            Merchant\Constants::MERCHANT_MUTEX_RETRY_COUNT
        );

        return (new DocumentResponse)->linkDocumentsResponse($merchant, $entityType, $entityId);
    }

    /**
     * Upon successful validation, return an array of entity and account associated to the entity
     * stakeholder request
     *   $entity   -> stakeholderEntity
     *   $account  -> merchant to which stakeholder is associated with
     *
     * Account request
     *   $entity   -> accountEntity
     *   $merchant -> accountEntity itself
     *
     * @param array $input
     * @param string $entityId
     * @param string $entityType
     *
     * @param string $accountId
     *
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\ServerErrorException
     */
    protected function validateDocumentLinkRequestAndGetEntities(array $input, string $entityId, string $entityType, string $accountId): array
    {
        Account\Entity::verifyIdAndStripSign($accountId);
        $account   = $this->repo->merchant->findOrFailPublic($accountId);
        $validator = new Validator;

        $documentResponse = new DocumentResponse;
        $accountDetails = $documentResponse->getMerchantDetails($account);
        $proofDocumentMapping = $documentResponse->getDocumentsGroupedAndMergedByProofType($accountDetails, $entityType);

        $genericDocumentService = new GenericDocument\Service();

        $fileIds = [];

        foreach ($input as $proofType => $files)
        {
            $validator->validateProofType($proofType, $entityType);

            foreach ($files as $file)
            {
                $validator->validateInput('document_link', $file);

                if (isset($proofDocumentMapping[$proofType]) === false)
                {
                    throw new Exception\BadRequestValidationFailureException('Extra document '. $file[Constants::TYPE]. ' sent');
                }

                if (in_array($file[Constants::TYPE], $proofDocumentMapping[$proofType]) === false)
                {
                    throw new Exception\BadRequestValidationFailureException('Incorrect Document '. $file[Constants::TYPE]. ' sent for proof type '. $proofType);
                }

                array_push($fileIds, $file[Constants::FILE_ID]);
            }
        }

        $fileResponse = $genericDocumentService->fetchFiles($fileIds, $accountId);

        $this->validateFileResponse($fileResponse, $fileIds);

        return $this->validateAndGetDocumentRequest($account, $entityType, $entityId);
    }

    protected function validateAndGetDocumentRequest(Merchant\Entity $account, string $entityType, string $entityId)
    {
        (new Account\Core)->validatePartnerAccess($this->merchant, $account->getId());

        if (Entity::MERCHANT === $entityType)
        {
            return [$account, $account];
        }
        else
        {
            Stakeholder\Entity::verifyIdAndStripSign($entityId);

            $stakeHolder = $this->repo->stakeholder->findOrFailPublic($entityId);

            $stakeHolder->getValidator()->validateAccountStakeholder($account, $stakeHolder);

            return [$stakeHolder, $account];
        }
    }

    private function validateFileResponse(array $response, array $fileIds)
    {
        $fileData = $response['items'] ?? [];

        $validFileIds = array_column($fileData, Merchant\Entity::ID);

        $invalidFileIds = array_diff($fileIds, $validFileIds);

        if (sizeof($invalidFileIds) > 0)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_INVALID_FILE_IDS_PROVIDED . ': ' . implode(', ', $invalidFileIds));
        }
    }
}
