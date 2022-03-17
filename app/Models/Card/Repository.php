<?php

namespace RZP\Models\Card;

use DB;

use RZP\Models\Base;
use RZP\Models\Base\Traits\ExternalCore;
use RZP\Models\Base\Traits\ExternalRepo;
use RZP\Models\Card;
use RZP\Base\BuilderEx;
use RZP\Models\Payment;
use RZP\Models\Customer\Token;
use RZP\Models\Merchant\Account;

class Repository extends Base\Repository
{
    use ExternalRepo, ExternalCore;

    protected $entity = 'card';

    protected $appFetchParamRules = [
        Entity::IIN             => 'sometimes|integer|digits:6',
        Entity::LAST4           => 'sometimes|string|digits:4',
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
        Entity::NETWORK         => 'sometimes|alpha_space',
        Entity::INTERNATIONAL   => 'sometimes|in:0,1',
        Payment\Entity::STATUS  => 'sometimes|string',
        Entity::EXPIRY_MONTH    => 'sometimes|integer|digits_between:1,2|max:12|min:1',
        Entity::EXPIRY_YEAR     => 'sometimes|integer|digits:4|non_past_year',
        Entity::VAULT_TOKEN     => 'sometimes|alpha_num',
        Entity::VAULT           => 'required_with:token|in:tokenex',
        Entity::GLOBAL_CARD_ID  => 'sometimes|alpha_num',
    ];

    /**
     * Returns a query on certain card entity attributes which
     * is called during payment repo fetch.
     *
     * @param  array  $params
     * @return BuilderEx
     */
    public function buildCardFetchSubQuery(array $params): BuilderEx
    {
        $query = $this->newQuery();

        foreach ($params as $key => $value)
        {
            $query->where($key, $value);
        }

        return $query;
    }

    public function retrieveIinDetails($iin)
    {
        if (strlen($iin) > 6)
        {
            $iin = intval(substr($iin, 0, 6));
        }

        //
        // retrieve iin details
        //
        return Card\IIN\Entity::find($iin);
    }

    public function getByParams($params, $relations = [], $limit = 1)
    {
        $query = $this->newQuery();

        foreach ($params as $key => $value)
        {
            $query = $query->where($key, '=', $value);
        }

        if (count($relations) > 0)
        {
            $query->with(...$relations);
        }

        $query->limit($limit);

        return $query->get();
    }

    public function fetchForPayment(Payment\Entity $payment)
    {
        if ($payment->hasRelation('card'))
        {
            return $payment->card;
        }

        $card = $this->findOrFail($payment->getCardId());

        $payment->setRelation('card', $card);

        return $card;
    }

    public function fetchForToken(Token\Entity $token)
    {
        if ($token->hasRelation('card'))
        {
            return $token->card;
        }

        $card = $this->findOrFail($token->getCardId());

        $token->setRelation('card', $card);

        return $card;
    }

    public function updateSavedCardsWithIins()
    {
        $count = $this->newQueryWithoutTimestamps()
                      ->join('iins', 'iins.iin', '=', 'cards.iin')
                      ->whereNotNull('cards.vault')
                      ->where(function ($q)
                      {
                         $q->where('cards.issuer', '!=', 'iins.issuer')
                           ->orWhere('cards.network', '!=', 'iins.network');
                      })
                      ->update([
                         'cards.issuer'  => DB::raw('iins.issuer'),
                         'cards.network' => DB::raw('iins.network'),
                      ]);

        $countryCount = $this->newQueryWithoutTimestamps()
                      ->join('iins', 'iins.iin', '=', 'cards.iin')
                      ->whereNotNull('cards.vault')
                      ->where('cards.network', '!=', NetworkName::AMEX)
                      ->where(function ($q)
                      {
                         $q->where('cards.country', '!=', 'iins.country')
                           ->orWhereNull('cards.country');
                      })
                      ->update([
                         'cards.country' => DB::raw('iins.country'),
                      ]);

        $typeCount = $this->newQueryWithoutTimestamps()
                      ->join('iins', 'iins.iin', '=', 'cards.iin')
                      ->whereNotNull('cards.vault')
                      ->where('cards.type', '!=', 'iins.type')
                      ->whereNotNull('iins.type')
                      ->update([
                         'cards.type'    => DB::raw('iins.type'),
                      ]);

        return compact('count', 'countryCount', 'typeCount');
    }

    /**
     * Fetches card id's with either own vault token or global card's vault token
     * equal to the given vault token
     *
     * @param  string $vautltToken vault token to check
     * @param  string $merchantId  merchant_id whose local cards needs to be checked
     * @return array               card ids matching
     */
    public function fetchWithVaultToken(string $vautltToken, string $merchantId): array
    {
        $globalCardIdsWithToken = $this->newQuery()
                                       ->select(Entity::ID)
                                       ->where(Entity::MERCHANT_ID, '=', Account::SHARED_ACCOUNT)
                                       ->where(Entity::VAULT_TOKEN, '=', $vautltToken)
                                       ->pluck(Entity::ID)->toArray();

        // Query Executed - select `id` from `cards` where `merchant_id` = ? and
        // (`vault_token` = ? or `global_card_id` in (?))
        return $this->newQuery()
                    ->select(Entity::ID)
                    ->where(Entity::MERCHANT_ID, '=',$merchantId)
                    ->where(function ($query) use ($vautltToken, $globalCardIdsWithToken)
                    {
                        $query->where(Entity::VAULT_TOKEN, '=', $vautltToken)
                              ->orWhereIn(Entity::GLOBAL_CARD_ID, $globalCardIdsWithToken);
                    })->pluck(Entity::ID)->toArray();
    }

    public function findCardsWithVaultAndNoPayments(string $vault, int $limit)
    {
        $window = 1200;

        $paymentRepo = $this->repo->payment;

        $paymentTable = $paymentRepo->getTableName();

        $paymentCardIdColumn = $paymentRepo->dbColumn(Payment\Entity::CARD_ID);

        $cardData = $this->dbColumn('*');

        $IdColumn = $this->dbColumn(Entity::ID);

        $createdAt  = $this->dbColumn(Entity::CREATED_AT);

        $timestamp = time() - $window;

        return $this->newQuery()
                    ->leftJoin($paymentTable, $IdColumn, $paymentCardIdColumn)
                    ->whereNull($paymentCardIdColumn)
                    ->where(Entity::VAULT, '=', $vault)
                    ->where($createdAt, '<=', $timestamp)
                    ->limit($limit)
                    ->select($cardData)
                    ->get();
    }

    public function findCardsWithoutFingerprint(int $limit, int $timestamp, int $timeWindow)
    {
        $window = 1200;
        $startTime = $timestamp;
        $endTime = min($startTime + $timeWindow, time() - $window);

        $baseQuery = $this->newQueryWithConnection($this->getSlaveConnection())
                    ->where(Entity::CREATED_AT, '>=', $startTime)
                    ->where(Entity::CREATED_AT, '<=', $endTime);

        $minId = $baseQuery->min(Entity::ID);
        $maxId = $baseQuery->max(Entity::ID);

        if ($minId !== null and $maxId !== null)
        {
            return $this->newQueryWithConnection($this->getSlaveConnection())
                ->where(Entity::VAULT, '=', Vault::RZP_VAULT)
                ->WhereNull(Entity::GLOBAL_FINGERPRINT)
                ->whereNotNull(Entity::VAULT_TOKEN)
                ->where(Entity::ID, '>=', $minId)
                ->where(Entity::ID, '<=', $maxId)
                ->orderBy(Entity::ID, 'desc')
                ->limit($limit)
                ->get();
        }

        return [];
    }

    public function migrateCardVaultTokenBulk($existingToken, $newToken, $globalFingerprint)
    {
        $limit = 5000;

        $this->newQuery()
            ->where(Entity::VAULT_TOKEN, '=', $existingToken)
            ->WhereNull(Entity::GLOBAL_FINGERPRINT)
            ->orderBy(Entity::CREATED_AT, 'desc')
            ->limit($limit)
            ->update(['vault_token' => $newToken, 'global_fingerprint' => $globalFingerprint]);
    }

    public function resetCardVaultToken($existingToken)
    {
        if (starts_with($existingToken, "pay_"))
        {
            $limit = 250;

            $this->newQuery()
                ->where(Entity::VAULT_TOKEN, '=', $existingToken)
                ->limit($limit)
                ->update(['vault_token' => null, 'global_fingerprint' => null]);
        }
    }

    protected function addQueryParamInternational($query, $params)
    {
        $international = $this->dbColumn(Entity::INTERNATIONAL);

        $query->where($international, '=', $params[Entity::INTERNATIONAL]);
    }

    protected function addQueryParamStatus($query, $params)
    {
        $status = $params[Payment\Entity::STATUS];
        $status = explode(',', $status);

        Payment\Validator::validateStatusArray($status);

        $paymentCardId = $this->repo->payment->dbColumn(Payment\Entity::CARD_ID);
        $cardId = $this->dbColumn(Card\Entity::ID);

        $query->join($this->repo->payment->getTableName(), $paymentCardId, '=', $cardId)
              ->whereIn(Payment\Entity::STATUS, $status);

        $query->select($query->getModel()->getTable().'.*');
    }

    /**
     * Fetches the details of card for the given id
     *
     * @param string $cardId
     * @return mixed
     */
    public function getCardById(string $cardId)
    {
        return $this->newQuery()
                    ->where(Entity::ID, $cardId)
                    ->first();
    }


    public function fetchCardsWithVaultToken(string $vaultToken, string $merchantId)
    {
        // Query Executed - select `*` from `cards` where `merchant_id` = ? and `vault_token` = ?
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::VAULT_TOKEN, '=', $vaultToken)
            ->get();
    }

    public function fetchLatestCardWithVaultTokenOnly(string $vaultToken)
    {
        // Query Executed - select `*` from `cards` where `vault_token` = ?
        return $this->newQuery()
                    ->where(Entity::VAULT_TOKEN, '=', $vaultToken)
                    ->orderBy(Entity::CREATED_AT, 'asc')
                    ->get()
                    ->last();
    }

    public function updateById($id, $updateData)
    {
        return $this->newQuery()
            ->where(Token\Entity::ID, $id)
            ->update($updateData);
    }
}
