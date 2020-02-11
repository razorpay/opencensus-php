<?php

namespace RZP\Models\Emi;

use RZP\Models\Base;
use RZP\Models\Card\IIN;
use RZP\Models\Card\Network;
use RZP\Models\Merchant\Account;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'emi_plan';

    protected $appFetchParamRules = array(
        Entity::BANK            => 'sometimes|string|size:4',
        Entity::NETWORK         => 'sometimes|string|max:12',
    );

    public function fetchEmiPlansByMerchantId(string $merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->get();
    }

    public function fetchRelevantMerchantEmiPlan(IIN\Entity $iin, int $duration, $merchant)
    {
        $bank = $iin->getIssuer();

        $network = $iin->getNetworkCode();

        $query = $this->newQuery()
                      ->where(Entity::DURATION, '=', $duration);
        if ($bank)
        {
            $query->where(Entity::BANK, '=', $bank);
        }
        else if (($network === Network::AMEX) or ($network === Network::BAJAJ))
        {
            $query->where(Entity::NETWORK, '=', $network);
        }

        $merchantIds = [$merchant->getId(), Account::SHARED_ACCOUNT];

        $query->whereIn(Entity::MERCHANT_ID, $merchantIds);

        return $query->get();
    }

    public function fetchByDurationsAndBankOrNetwork(array $durations = [], string $bank = null, string $network = null)
    {
        $query = $this->newQuery();

        if (empty($durations) === false)
        {
            $query->whereIn(Entity::DURATION, $durations);
        }

        if (empty($bank) === false)
        {
            $query->where(Entity::BANK, $bank);
        }

        if (empty($network) === false)
        {
            $query->where(Entity::NETWORK, $network);
        }

        return $query->get();
    }

    public function fetchDurationsByMerchantAndIssuer(string $merchantId, string $issuer)
    {
        return $this->newQuery()
            ->select(Entity::DURATION)
            ->whereIn(Entity::MERCHANT_ID, [$merchantId, Account::SHARED_ACCOUNT])
            ->where(Entity::BANK, '=', $issuer)
            ->pluck(Entity::DURATION)
            ->all();
    }

    public function fetchDurationsByMerchantAndNetwork(string $merchantId, string $paymentNetwork)
    {
        return $this->newQuery()
            ->select(Entity::DURATION)
            ->whereIn(Entity::MERCHANT_ID, [$merchantId, Account::SHARED_ACCOUNT])
            ->where(Entity::NETWORK, '=', $paymentNetwork)
            ->pluck(Entity::DURATION)
            ->all();
    }
 }
