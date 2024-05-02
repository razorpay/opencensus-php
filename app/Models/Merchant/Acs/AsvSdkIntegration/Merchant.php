<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration;

use RZP\Error\ErrorCode;
use RZP\Exception\BaseException;
use Razorpay\Asv\RequestMetadata;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestException;
use Rzp\Accounts\Merchant\V1 as MerchantV1;
use Rzp\Accounts\Merchant\V1\FilterRequest;
use Illuminate\Database\Eloquent\Collection;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\ProtoToEntityConverter\Merchant as MerchantProtoMapper;

class Merchant extends Base
{
    const FILTER_TIMEOUT_IN_MICRO_SECONDS = 5000000;

    const MERCHANT_FIND_BY_IDS
        = 'merchant_find_by_ids';
    const GET_NON_SUSPENDED_MERCHANTS_FROM_IDS
        = 'get_non_suspended_merchants_from_ids';
    const GET_MERCHANTS_BY_PARENT_ID_AND_ACCOUNT_CODE_LIMIT_ONE
        = 'get_merchants_by_parent_id_and_account_code_limit_one';
    const GET_NON_SUSPENDED_LINKED_ACCOUNTS_FROM_PARENT_ID
        = 'get_non_suspended_linked_accounts_from_parent_id';
    const GET_LINKED_ACCOUNTS_SUSPENDED_DUE_TO_PARENT_SUSPENSION_FROM_PARENT_ID
        = 'get_linked_accounts_suspended_due_to_parent_suspension_from_parent_id';
    const GET_SECOND_FACTOR_AUTH_ENABLED_MERCHANTS_FROM_IDS
        = 'get_second_factor_auth_enabled_merchants_from_ids';
    const GET_MERCHANTS_BY_LEGAL_ENTITY_ID
        = 'get_merchants_by_legal_entity_id';
    const GET_LINKED_ACCOUNT_COUNT
        = 'get_linked_accounts_count';
    const GET_LINKED_ACCOUNTS_FROM_PARENT_ID
        = 'get_linked_accounts_from_parent_id';
    const GET_LINKED_ACCOUNTS_FROM_PARENT_ID_WITH_LIMIT_OFFSET
        = 'get_linked_accounts_from_parent_id_with_limit_offset';
    const FILTER_MERCHANTS_WITH_FUNDS_NOT_ON_HOLD
        = 'filter_merchants_with_funds_not_on_hold';
    const GET_LINKED_ACCOUNTS_FROM_MULTIPLE_PARENT_IDS
        = 'get_linked_accounts_from_multiple_parent_ids';
    const FETCH_LINKED_ACCOUNT_IDS_FROM_PARENT_ID_WITH_ACTIVATED
        = 'fetch_linked_account_ids_from_parent_id_with_activated';
    const FETCH_ACTIVATED_MERCHANTS_BEFORE_TIMESTAMP
        = 'fetch_activated_merchants_before_timestamp';
    const FETCH_ACTIVATED_MERCHANTS_BEFORE_TIMESTAMP_WITH_MERCHANT_IDS
        = 'fetch_activated_merchants_before_timestamp_with_merchant_ids';
    const FETCH_ACTIVATED_MERCHANTS_BEFORE_TIMESTAMP_WITH_MERCHANT_IDS_EXCLUDED
        = 'fetch_activated_merchants_before_timestamp_with_merchant_ids_excluded';
    const FETCH_ACTIVATED_MERCHANTS_BEFORE_TIMESTAMP_WITH_MERCHANT_IDS_AND_EXCLUDED
        = 'fetch_activated_merchants_before_timestamp_with_merchant_ids_and_excluded';

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
     * @param string $parentId
     * @param bool   $checkForActivated
     * @param string $lastMerchantId
     *
     * @return Collection|PublicCollection
     * @throws BadRequestException
     * @throws BaseException
     */
    public function fetchLinkedAccountIdsFromParentIdWithActivated(
        string $parentId, bool $checkForActivated, string $lastMerchantId = ''
    ): Collection|PublicCollection
    {
        if (!$checkForActivated)
        {
            return $this->fetchLinkedAccountsFromParentId($parentId, $lastMerchantId);
        }

        $filterRequest = new FilterRequest();
        $filterRequest->setQueryIdentifier(self::FETCH_LINKED_ACCOUNT_IDS_FROM_PARENT_ID_WITH_ACTIVATED);
        $filterRequest->setBindings(json_encode([$parentId, 1, $lastMerchantId]));

        $response = $this->getFilterResponseFromAsv($filterRequest);

        return $this->getMerchantCollectionFromResponse($response);
    }

    /**
     * @param string $parentId
     * @param int    $limit
     * @param int    $offset
     *
     * @return Collection|PublicCollection
     * @throws BadRequestException
     * @throws BaseException
     */
    public function fetchLinkedAccountsFromParentIdWithLimitOffset(string $parentId, int $limit, int $offset): Collection|PublicCollection
    {
        $filterRequest = (new FilterRequest())
            ->setQueryIdentifier(self::GET_LINKED_ACCOUNTS_FROM_PARENT_ID_WITH_LIMIT_OFFSET)
            ->setBindings(json_encode([$parentId, $limit, $offset]));

        $response = $this->getFilterResponseFromAsv($filterRequest);

        return $this->getMerchantCollectionFromResponse($response);
    }

    /**
     * Fetches the merchants associated with the passed
     * parent_id and account_code
     *
     * @param string $parentId
     * @param string $accountCode
     *
     * @return Collection|PublicCollection
     * @throws BadRequestException
     * @throws BaseException
     */
    public function fetchMerchantsByParentIdAndAccountCode(string $parentId, string $accountCode): Collection|PublicCollection
    {
        $filterRequest = (new FilterRequest())
            ->setQueryIdentifier(self::GET_MERCHANTS_BY_PARENT_ID_AND_ACCOUNT_CODE_LIMIT_ONE)
            ->setBindings(
                json_encode([$parentId, $accountCode])
            );

        $response = $this->getFilterResponseFromAsv($filterRequest);

        return $this->getMerchantCollectionFromResponse($response);
    }

    /**
     * @param string $merchantId
     *
     * @return int
     * @throws BadRequestException
     * @throws BaseException
     */
    public function fetchLinkedAccountsCount(string $merchantId): int
    {
        $filterRequest  =  (new FilterRequest())
            ->setQueryIdentifier(self::GET_LINKED_ACCOUNT_COUNT)
            ->setBindings(json_encode([$merchantId]));
        $response       = $this->getFilterResponseFromAsv($filterRequest);
        $joins          = $response->getJoins();

        return json_decode($joins[0]->serializeToJsonString(), true)["linked_account_count"];
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    public function fetchActivatedMerchantsBeforeTimestamp(
        int   $limit,
        int   $skip,
        int   $end,
        array $parentIdsExcluded,
        array $merchantIds = [],
        array $merchantIdsExcluded = []
    ): Collection|PublicCollection
    {
        $filterRequest =  new FilterRequest();

        if (empty($merchantIds) and empty($merchantIdsExcluded))
        {
            $filterRequest->setQueryIdentifier(
                self::FETCH_ACTIVATED_MERCHANTS_BEFORE_TIMESTAMP
            );
            $filterRequest->setBindings(
                json_encode([1, $end, $parentIdsExcluded, $limit, $skip])
            );
        }
        else if (!empty($merchantIds) and !empty($merchantIdsExcluded))
        {
            $filterRequest->setQueryIdentifier(
                self::FETCH_ACTIVATED_MERCHANTS_BEFORE_TIMESTAMP_WITH_MERCHANT_IDS_AND_EXCLUDED
            );
            $filterRequest->setBindings(
                json_encode([1, $end, $parentIdsExcluded, $merchantIds, $merchantIdsExcluded, $limit, $skip])
            );
        }
        else if (!empty($merchantIds))
        {
            $filterRequest->setQueryIdentifier(
                self::FETCH_ACTIVATED_MERCHANTS_BEFORE_TIMESTAMP_WITH_MERCHANT_IDS
            );
            $filterRequest->setBindings(
                json_encode([1, $end, $parentIdsExcluded, $merchantIds, $limit, $skip])
            );
        }
        else if (!empty($merchantIdsExcluded))
        {
            $filterRequest->setQueryIdentifier(
                self::FETCH_ACTIVATED_MERCHANTS_BEFORE_TIMESTAMP_WITH_MERCHANT_IDS_EXCLUDED
            );
            $filterRequest->setBindings(
                json_encode([1, $end, $parentIdsExcluded, $merchantIdsExcluded, $limit, $skip])
            );
        }
        else
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_ARGUMENT,
                null,
                null,
                "invalid arguments for fetchActivatedMerchantsBeforeTimestamp",
            );
        }

        $response = $this->getFilterResponseFromAsv($filterRequest);

        return $this->getMerchantCollectionFromResponse($response);
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
     * @return Collection|PublicCollection
     * @throws BadRequestException
     * @throws BaseException
     */
    public function fetchLinkedAccountMidsSuspendedDueToParentMerchantSuspension(
        string $parentId, string $reason, int $limit, int $offset
    ): Collection|PublicCollection
    {
        $filterRequest =  new FilterRequest();
        $filterRequest->setQueryIdentifier(self::GET_LINKED_ACCOUNTS_SUSPENDED_DUE_TO_PARENT_SUSPENSION_FROM_PARENT_ID);
        $filterRequest->setBindings(
            json_encode([$parentId, $reason, $limit, $offset])
        );

        $response = $this->getFilterResponseFromAsv($filterRequest, self::FILTER_TIMEOUT_IN_MICRO_SECONDS);

        return $this->getMerchantCollectionFromResponse($response);
    }

    /**
     * @param string $parentId
     * @param string $lastMerchantId
     *
     * @return Collection|PublicCollection
     * @throws BadRequestException
     * @throws BaseException
     */
    public function fetchLinkedAccountsFromParentId(
        string $parentId, string $lastMerchantId = ''
    ): Collection|PublicCollection
    {
        $filterRequest =  new FilterRequest();
        $filterRequest->setQueryIdentifier(self::GET_LINKED_ACCOUNTS_FROM_PARENT_ID);
        $filterRequest->setBindings(
            json_encode([$parentId, $lastMerchantId])
        );

        $response = $this->getFilterResponseFromAsv($filterRequest, self::FILTER_TIMEOUT_IN_MICRO_SECONDS);

        return $this->getMerchantCollectionFromResponse($response);
    }

    /**
     * @param array  $parentIds
     * @param string $lastMerchantId
     *
     * @return Collection|PublicCollection
     * @throws BadRequestException
     * @throws BaseException
     */
    public function fetchLinkedAccountsFromMultipleParentIds(
        array $parentIds, string $lastMerchantId = ''
    ): Collection|PublicCollection
    {
        $filterRequest =  new FilterRequest();
        $filterRequest->setQueryIdentifier(self::GET_LINKED_ACCOUNTS_FROM_MULTIPLE_PARENT_IDS);
        $filterRequest->setBindings(
            json_encode([$parentIds, $lastMerchantId])
        );

        $response = $this->getFilterResponseFromAsv($filterRequest, self::FILTER_TIMEOUT_IN_MICRO_SECONDS);

        return $this->getMerchantCollectionFromResponse($response);
    }

    /**
     * fetches all unsuspended linked account MIDs associated with a parentId
     *
     * @param string $parentId
     * @param int    $limit
     * @param int    $offset
     *
     * @return Collection|PublicCollection
     * @throws BadRequestException
     * @throws BaseException
     */
    public function fetchUnsuspendedLinkedAccountMids(string $parentId, int $limit, int $offset): Collection|PublicCollection
    {
        $filterRequest =  new FilterRequest();
        $filterRequest->setQueryIdentifier(self::GET_NON_SUSPENDED_LINKED_ACCOUNTS_FROM_PARENT_ID);
        $filterRequest->setBindings(
            json_encode([$parentId, $limit, $offset])
        );

        $response = $this->getFilterResponseFromAsv($filterRequest, self::FILTER_TIMEOUT_IN_MICRO_SECONDS);

        return $this->getMerchantCollectionFromResponse($response);
    }

    /**
     * @param string $legalEntityId
     *
     * @return Collection|PublicCollection
     * @throws BadRequestException
     * @throws BaseException
     */
    public function fetchMerchantsByLegalEntityId(string $legalEntityId): Collection|PublicCollection
    {
        $filterRequest =  new FilterRequest();
        $filterRequest->setQueryIdentifier(self::GET_MERCHANTS_BY_LEGAL_ENTITY_ID);
        $filterRequest->setBindings(
            json_encode([$legalEntityId])
        );

        $response = $this->getFilterResponseFromAsv($filterRequest, self::FILTER_TIMEOUT_IN_MICRO_SECONDS);

        return $this->getMerchantCollectionFromResponse($response);
    }

    public function fetchMerchantsByIds(array $ids): Collection|PublicCollection
    {
        $filterRequest =  new FilterRequest();
        $filterRequest->setQueryIdentifier(self::MERCHANT_FIND_BY_IDS);
        $filterRequest->setBindings(
            json_encode([$ids])
        );

        $response = $this->getFilterResponseFromAsv($filterRequest, self::FILTER_TIMEOUT_IN_MICRO_SECONDS);

        return $this->getMerchantCollectionFromResponse($response);
    }

    public function getNonSuspendedMerchantsFromIds(array $ids):  PublicCollection|Collection
    {
        $filterRequest =  new FilterRequest();
        $filterRequest->setQueryIdentifier(self::GET_NON_SUSPENDED_MERCHANTS_FROM_IDS);
        $filterRequest->setBindings(
            json_encode([$ids])
        );

        $response = $this->getFilterResponseFromAsv($filterRequest, self::FILTER_TIMEOUT_IN_MICRO_SECONDS);
        return $this->getMerchantCollectionFromResponse($response);
    }

    public function getMerchantsWithSecondFactorAuthPresentInIds(array $ids): PublicCollection|Collection
    {
        $filterRequest =  new FilterRequest();

        $filterRequest->setQueryIdentifier(self::GET_SECOND_FACTOR_AUTH_ENABLED_MERCHANTS_FROM_IDS);

        $filterRequest->setBindings(
            json_encode([$ids])
        );

        $response = $this->getFilterResponseFromAsv($filterRequest, self::FILTER_TIMEOUT_IN_MICRO_SECONDS);

        return $this->getMerchantCollectionFromResponse($response);
    }

    /**
     * @param int    $from
     * @param int    $to
     * @param string $lastMerchantId
     *
     * @return Collection|PublicCollection
     * @throws BadRequestException
     * @throws BaseException
     */
    public function fetchMerchantsCreatedBetween(
        int $from, int $to, string $lastMerchantId = ''
    ): Collection|PublicCollection
    {
        $filterRequest =  new FilterRequest();
        $filterRequest->setQueryIdentifier('fetch_merchants_created_between');
        $filterRequest->setBindings(
            json_encode([$from, $to, $lastMerchantId])
        );

        $response = $this->getFilterResponseFromAsv($filterRequest, self::FILTER_TIMEOUT_IN_MICRO_SECONDS);

        return $this->getMerchantCollectionFromResponse($response);
    }

    /**
     * @param int    $from
     * @param int    $to
     * @param string $lastMerchantId
     *
     * @return Collection|PublicCollection
     * @throws BadRequestException
     * @throws BaseException
     */
    public function getMerchantsForSettlementsEventsCron(
        int $from, int $to, string $lastMerchantId = ''
    ): Collection|PublicCollection
    {
        $filterRequest =  new FilterRequest();
        $filterRequest->setQueryIdentifier('get_merchants_for_settlements_event_cron');
        $filterRequest->setBindings(
            json_encode([$from, $to, $lastMerchantId])
        );

        $response = $this->getFilterResponseFromAsv($filterRequest, self::FILTER_TIMEOUT_IN_MICRO_SECONDS);

        return $this->getMerchantCollectionFromResponse($response);
    }


    public function fetchMerchantsCountWithPricingPlanId(string $planId): int
    {
        $filterRequest =  new FilterRequest();
        $filterRequest->setQueryIdentifier('fetch_merchants_count_with_pricing_plan_id');
        $filterRequest->setBindings(
            json_encode([
                            $planId
                        ])
        );

        $response = $this->getFilterResponseFromAsv($filterRequest, self::FILTER_TIMEOUT_IN_MICRO_SECONDS);

        $count = 0;

        foreach ($response->getJoins() as $join) {
            $count = json_decode($join->serializeToJsonString(), true)['aggregate'];
            break;
        }

        return $count;
    }

    /**
     * @param string $planId
     *
     * @return array
     * @throws BadRequestException
     * @throws BaseException
     */
    public function fetchFeeBearersForPlanId(string $planId): array
    {
        $filterRequest =  new FilterRequest();
        $filterRequest->setQueryIdentifier('fetch_fee_bearers_for_plan_id');
        $filterRequest->setBindings(
            json_encode([$planId])
        );

        $response = $this->getFilterResponseFromAsv($filterRequest, self::FILTER_TIMEOUT_IN_MICRO_SECONDS);

        $feeBearersList = [];

        foreach ($response->getJoins() as $join) {
            $feeBearersList[] = json_decode($join->serializeToJsonString(), true)['fee_bearer'];
        }

        return $feeBearersList;
    }

    /**
     * @param array $merchantIds
     *
     * @return Collection|PublicCollection
     * @throws BadRequestException
     * @throws BaseException
     */
    public function filterMerchantsWithFundsNotOnHold(array $merchantIds): Collection|PublicCollection
    {
        $filterRequest =  new FilterRequest();
        $filterRequest->setQueryIdentifier(self::FILTER_MERCHANTS_WITH_FUNDS_NOT_ON_HOLD);
        $filterRequest->setBindings(
            json_encode([$merchantIds])
        );

        $response = $this->getFilterResponseFromAsv($filterRequest, self::FILTER_TIMEOUT_IN_MICRO_SECONDS);

        return $this->getMerchantCollectionFromResponse($response);
    }

}
