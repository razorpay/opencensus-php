<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration;

use Rzp\Accounts\Merchant\V1\FilterRequest;
use RZP\Exception\BaseException;
use Razorpay\Asv\RequestMetadata;
use RZP\Exception\BadRequestException;
use Rzp\Accounts\Merchant\V1 as MerchantV1;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\ProtoToEntityConverter\Merchant as MerchantProtoMapper;

class Merchant extends Base
{
    const FILTER_TIMEOUT_IN_MICRO_SECONDS = 5000000;
    const MERCHANT_FIND_BY_IDS = 'merchant_find_by_ids';
    const GET_NON_SUSPENDED_MERCHANTS_FROM_IDS = 'get_non_suspended_merchants_from_ids';
    const GET_NON_SUSPENDED_LINKED_ACCOUNTS_FROM_PARENT_ID = 'get_non_suspended_linked_accounts_from_parent_id';
    const GET_LINKED_ACCOUNTS_SUSPENDED_DUE_TO_PARENT_SUSPENSION_FROM_PARENT_ID = 'get_linked_accounts_suspended_due_to_parent_suspension_from_parent_id';
    const GET_SECOND_FACTOR_AUTH_ENABLED_MERCHANTS_FROM_IDS = 'get_second_factor_auth_enabled_merchants_from_ids';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    public function getById(string $id, RequestMetadata $requestMetadata = null): MerchantEntity
    {
        /**
         * @var MerchantV1\MerchantResponse $response
         */
        list($response, $err) = $this->asvSdkClient->getMerchant()->getById(
            $id,
            $this->getRequestMetaData($requestMetadata)
        );

        if ($err !== null) {
            $this->handleError($err);
        }

        $merchant = $response->getMerchant();

        return (new MerchantProtoMapper($merchant))->ToEntity();
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    public function fetchActivatedMerchantsBeforeTimestamp(int   $limit,
                                                           int   $skip,
                                                           int   $end,
                                                           array $merchantIds = [],
                                                           array $merchantIdsExcluded = []): array
    {
        $filterRequest =  new FilterRequest();
        $filterRequest->setQueryIdentifier('merchant_03');
        $filterRequest->setBindings(
            json_encode([
                1, $end, $merchantIdsExcluded, $merchantIds, $merchantIdsExcluded, strval($limit), strval($skip)
            ])
        );

        $response = $this->getFilterResponseFromAsv($filterRequest);

        return $this->getMerchantCollectionFromResponse($response)->pluck('id')->toArray();
    }

    /**
     * fetches the linked accounts MIDs associated with a parentID
     * which were suspended for the given reason
     *
     * @param string $parentId
     * @param string $reason
     * @param int    $limit
     * @param int    $offset
     *
     * @return array
     * @throws BadRequestException
     * @throws BaseException
     */
    public function fetchLinkedAccountMidsSuspendedDueToParentMerchantSuspension(string $parentId, string $reason, int $limit, int $offset): array
    {
        $filterRequest =  new FilterRequest();
        $filterRequest->setQueryIdentifier(self::GET_LINKED_ACCOUNTS_SUSPENDED_DUE_TO_PARENT_SUSPENSION_FROM_PARENT_ID);
        $filterRequest->setBindings(
            json_encode([$parentId, $reason, $limit, $offset])
        );

        $response = $this->getFilterResponseFromAsv($filterRequest, self::FILTER_TIMEOUT_IN_MICRO_SECONDS);

        return $this->getMerchantCollectionFromResponse($response)->pluck('id')->toArray();
    }

    /**
     * fetches all unsuspended linked account MIDs associated with a parentId
     *
     * @param string $parentId
     * @param int    $limit
     * @param int    $offset
     *
     * @return array
     * @throws BadRequestException
     * @throws BaseException
     */
    public function fetchUnsuspendedLinkedAccountMids(string $parentId, int $limit, int $offset): array
    {
        $filterRequest =  new FilterRequest();
        $filterRequest->setQueryIdentifier(self::GET_NON_SUSPENDED_LINKED_ACCOUNTS_FROM_PARENT_ID);
        $filterRequest->setBindings(
            json_encode([$parentId, $limit, $offset])
        );

        $response = $this->getFilterResponseFromAsv($filterRequest, self::FILTER_TIMEOUT_IN_MICRO_SECONDS);

        return $this->getMerchantCollectionFromResponse($response)->pluck('id')->toArray();
    }

    public function fetchMerchantsByIds(array $ids)
    {
        $filterRequest =  new FilterRequest();
        $filterRequest->setQueryIdentifier(self::MERCHANT_FIND_BY_IDS);
        $filterRequest->setBindings(
            json_encode([$ids])
        );

        $response = $this->getFilterResponseFromAsv($filterRequest, self::FILTER_TIMEOUT_IN_MICRO_SECONDS);

        return $this->getMerchantCollectionFromResponse($response);
    }

    public function getNonSuspendedMerchantsFromIds(array $ids):  PublicCollection|\Illuminate\Database\Eloquent\Collection
    {
        $filterRequest =  new FilterRequest();
        $filterRequest->setQueryIdentifier(self::GET_NON_SUSPENDED_MERCHANTS_FROM_IDS);
        $filterRequest->setBindings(
            json_encode([$ids])
        );

        $response = $this->getFilterResponseFromAsv($filterRequest, self::FILTER_TIMEOUT_IN_MICRO_SECONDS);
        return $this->getMerchantCollectionFromResponse($response);
    }

    public function getMerchantsWithSecondFactorAuthPresentInIds(array $ids): PublicCollection|\Illuminate\Database\Eloquent\Collection
    {
        $filterRequest =  new FilterRequest();

        $filterRequest->setQueryIdentifier(self::GET_SECOND_FACTOR_AUTH_ENABLED_MERCHANTS_FROM_IDS);

        $filterRequest->setBindings(
            json_encode([$ids])
        );

        $response = $this->getFilterResponseFromAsv($filterRequest, self::FILTER_TIMEOUT_IN_MICRO_SECONDS);

        return $this->getMerchantCollectionFromResponse($response);
    }

    public function fetchMerchantsActivatedBetweenForOrg($from, $to, $orgId): array
    {
        $filterRequest =  new FilterRequest();
        $filterRequest->setQueryIdentifier('fetch_merchants_activated_between_for_org');
        $filterRequest->setBindings(
            json_encode([
                            $orgId, $from, $to
                        ])
        );

        $response = $this->getFilterResponseFromAsv($filterRequest);

        return $this->getMerchantCollectionFromResponse($response)->toArray();
    }

    public function getMerchantsForSettlementsEventsCron($from, $to): array
    {
        $filterRequest =  new FilterRequest();
        $filterRequest->setQueryIdentifier('get_merchants_for_settlements_event_cron');
        $filterRequest->setBindings(
            json_encode([
                            $from, $to
                        ])
        );

        $response = $this->getFilterResponseFromAsv($filterRequest);

        return $this->getMerchantCollectionFromResponse($response)->toArray();
    }

}
