<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration;

use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Email;
use RZP\Models\Base\Collection;
use RZP\Exception\BaseException;
use Razorpay\Asv\RequestMetadata;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestException;
use Rzp\Accounts\Merchant\V1 as MerchantV1;
use Rzp\Accounts\Merchant\V1\FilterRequest;
use RZP\Models\Merchant\Email\Entity as MerchantEmailEntity;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\ProtoToEntityConverter\MerchantEmail as MerchantEmailProtoMapper;

class MerchantEmail extends Base
{
    const FILTER_TIMEOUT_IN_MICRO_SECONDS = 5000000;

    const GET_EMAILS_BY_MERCHANT_IDS_AND_TYPES
        = 'get_emails_by_merchant_ids_and_types';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    public function getAllByMerchantId(string $merchantId, RequestMetadata $requestMetadata = null): PublicCollection
    {
        /**
         * @var MerchantV1\MerchantEmailResponseByMerchantId $response
         */
        list($response, $err) = $this->asvSdkClient->getEmail()->getByMerchantId(
            $merchantId,
            $this->getRequestMetaData($requestMetadata)
        );

        if ($err !== null) {
            $this->handleError($err);
        }

        $emailsArray = [];

        $emails = $response->getEmails();

        /**
         * @var $email MerchantV1\Email
         */
        foreach ($emails as $email) {
            $merchantEmailProtoConvertor = new MerchantEmailProtoMapper($email);
            $emailEntity = $merchantEmailProtoConvertor->ToEntity();
            $emailsArray[] = $emailEntity;
        }

        return (new Email\Entity)->newCollection($emailsArray);
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    public function getById(string $id, RequestMetadata $requestMetadata = null): MerchantEmailEntity
    {
        /**
         * @var MerchantV1\MerchantEmailResponse $response
         */
        list($response, $err) = $this->asvSdkClient->getEmail()->getById(
            $id,
            $this->getRequestMetaData($requestMetadata)
        );

        if ($err !== null) {
            $this->handleError($err);
        }

        $email = $response->getEmail();

        return (new MerchantEmailProtoMapper($email))->ToEntity();
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    public function getAllExceptPartnerDummyByMerchantId(string $merchantId, ?RequestMetadata $requestMetadata = null): PublicCollection
    {
        try {
            $merchantEmailsByMerchantId = $this->getAllByMerchantId($merchantId, $requestMetadata);
        } catch (\Exception $e) {
            if ($e->getCode() == ErrorCode::BAD_REQUEST_INVALID_ARGUMENT) {
                return (new Email\Entity)->newCollection([]);
            }

            throw $e;
        }

        $merchantEmailsExceptPartnerDummy = [];

        /**
         * @var Email\Entity $merchantEmail
         */
        foreach ($merchantEmailsByMerchantId as $merchantEmail) {
            if ($merchantEmail->getType() !== Email\Type::PARTNER_DUMMY) {
                $merchantEmailsExceptPartnerDummy[] = $merchantEmail;
            }
        }

        return (new Email\Entity)->newCollection($merchantEmailsExceptPartnerDummy);
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    public function getByTypeAndMerchantId(string $type, string $merchantId, ?RequestMetadata $requestMetadata = null): ?Email\Entity
    {
        try {
            $merchantEmailsByMerchantId = $this->getAllByMerchantId($merchantId, $requestMetadata);
        } catch (\Exception $e) {
            if ($e->getCode() == ErrorCode::BAD_REQUEST_INVALID_ARGUMENT) {
                return null;
            }

            throw $e;
        }

        $merchantEmailByTypeAndMerchantId = [];

        /**
         * @var Email\Entity $merchantEmail
         */
        foreach ($merchantEmailsByMerchantId as $merchantEmail) {
            if ($merchantEmail->getType() === $type) {
                $merchantEmailByTypeAndMerchantId = $merchantEmail;
                break;
            }
        }

        if (empty($merchantEmailByTypeAndMerchantId) === true) {
            return null;
        }

        return $merchantEmailByTypeAndMerchantId;
    }

    public function getByTypeAndMerchantIdCallBack(string $type, string $merchantId, ?RequestMetadata $requestMetadata = null): \Closure
    {
        return function () use ($type, $merchantId, $requestMetadata) {
            return $this->getByTypeAndMerchantId($type, $merchantId, $requestMetadata);
        };
    }

    public function getAllExceptPartnerDummyByMerchantIdCallback(string $merchantId, ?RequestMetadata $requestMetadata = null): \Closure
    {
        return function () use ($merchantId, $requestMetadata) {
            return $this->getAllExceptPartnerDummyByMerchantId($merchantId, $requestMetadata);
        };
    }

    /**
     * @param array  $merchantIds
     * @param array  $types
     * @param string $lastMerchantEmailId
     *
     * @return Collection|PublicCollection
     * @throws BadRequestException
     * @throws BaseException
     */
    public function getEmailsByMerchantIdsAndTypes(
        array $merchantIds, array $types, string $lastMerchantEmailId = ''
    ): Collection|PublicCollection
    {
        $filterRequest =  new FilterRequest();
        $filterRequest->setQueryIdentifier(self::GET_EMAILS_BY_MERCHANT_IDS_AND_TYPES);
        $filterRequest->setBindings(
            json_encode([$merchantIds, $types, $lastMerchantEmailId])
        );

        $response = $this->getFilterResponseFromAsv($filterRequest, self::FILTER_TIMEOUT_IN_MICRO_SECONDS);

        $emails = $this->getMerchantEmailCollectionFromResponse($response);

        return (new MerchantEmailEntity())->newCollection(
            $emails->map->only(
                [PublicEntity::MERCHANT_ID, MerchantEmailEntity::TYPE, MerchantEmailEntity::EMAIL]
            )->toArray()
        );
    }
}


