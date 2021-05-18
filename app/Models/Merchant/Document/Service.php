<?php

namespace RZP\Models\Merchant\Document;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Account;
use RZP\Models\Merchant\AccountV2;
use RZP\Models\Merchant\Stakeholder;


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

    protected function uploadActivationFileByPartner(Merchant\Entity $account, Base\PublicEntity $entity, array $input)
    {
        return $this->mutex->acquireAndRelease(

            $account->getId(),

            function() use ($account, $input, $entity) {

                return $this->core->uploadActivationFile($account, $input, 'true', 'uploadDocument', $entity);
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
        [$entity, $merchant] = $this->validateAndGetDocumentRequest($accountId, $entityType, $entityId);

        return (new DocumentResponse)->documentsResponse($merchant, $entityType, $entity->getId());
    }

    public function postDocumentsByPartner(string $accountId, string $entityType, string $entityId, array $input)
    {
        [$entity, $merchant] = $this->validateAndGetDocumentRequest($accountId, $entityType, $entityId);

        $validator = (new Validator);

        $validator->validateInput('uploadDocument', $input);

        $validator->validateFileSize($input[Entity::FILE]);

        $documents = Type::getValidDocumentForEntity($entity->getEntity());

        if (in_array($input[Entity::DOCUMENT_TYPE], $documents) === false)
        {
            throw new Exception\BadRequestValidationFailureException('invalid document type:'. $input[Entity::DOCUMENT_TYPE] . ' for '. $entity->getEntity());
        }

        $this->uploadActivationFileByPartner($merchant, $entity, $input);

        (new AccountV2\Core())->submitDetailsAndActivateIfApplicable($merchant);

        return (new DocumentResponse)->documentsResponse($merchant, $entity->getEntity(), $entity->getId());
    }

    protected function validateAndGetDocumentRequest(string $accountId, string $entityType, string $entityId)
    {
        Account\Entity::verifyIdAndStripSign($accountId);
        $account   = $this->repo->merchant->findOrFailPublic($accountId);

        (new Account\Core)->validatePartnerAccess($this->merchant, $account->getId());

        if (E::MERCHANT === $entityType)
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
}
