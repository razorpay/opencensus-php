<?php

namespace RZP\Models\Merchant\Acs\ParityChecker\Helper;

use App;
use RZP\Base\RepositoryManager;
use RZP\Models\Merchant\Acs\ParityChecker\Constant\Constant;

class FetchMerchant
{

    /** @var RepositoryManager */
    protected $repo;

    function __construct()
    {
        $app = App::getFacadeRoot();
        $this->repo = $app['repo'];
    }

    function fetchAllMerchantIdsFromSlaveDB(string $entity, int $count, string $merchantId): array
    {
        $merchantIds = match ($entity) {
            Constant::MERCHANT => $this->getMerchantIds(
                $this->repo->merchant->fetchAllMerchantIDsFromSlaveDB([
                    Constant::COUNT => $count,
                    Constant::AFTER_ID => $merchantId
                ])->toArray(),
                key: Constant::ID),
            Constant::MERCHANT_WEBSITE => $this->getMerchantIds(
                $this->repo->merchant_website->fetchAllMerchantIDsFromSlaveDB([
                    Constant::COUNT => $count,
                    Constant::AFTER_MERCHANT_ID => $merchantId
                ])->toArray(),
                key: Constant::MERCHANT_ID),
            Constant::MERCHANT_EMAIL => $this->getMerchantIds(
                $this->repo->merchant_email->fetchAllMerchantIDsFromSlaveDB([
                    Constant::COUNT => $count,
                    Constant::AFTER_MERCHANT_ID => $merchantId
                ])->toArray(),
                key: Constant::MERCHANT_ID),
            default => $this->getMerchantIds(
                $this->repo->merchant->fetchAllMerchantIDsFromSlaveDB([
                    Constant::COUNT => $count,
                    Constant::AFTER_ID => $merchantId
                ])->toArray(),
                key: Constant::ID),
        };

        return $merchantIds;
    }

    function getMerchantIds(array $entityArray, string $key): array
    {
        $merchantIds = [];
        foreach ($entityArray as $entity) {
            $mid = $entity[$key] ?? '';
            $merchantIds[] = $mid;
        }

        return $merchantIds;
    }

    function selectSubsetMerchantIds(array $merchantIds, int $percentage): array
    {
        $subsetMerchantIds = [];
        foreach ($merchantIds as $merchantId) {
            $randomInt = rand(1, 100);
            if ($randomInt > $percentage) {
                continue;
            }
            $subsetMerchantIds[] = $merchantId;
        }

        return $subsetMerchantIds;
    }

}
