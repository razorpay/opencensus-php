<?php

namespace RZP\Models\Card;

use DB;
use App;
use RZP\Base\ConnectionType;
use RZP\Constants\Country;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Admin;
use RZP\Base\BuilderEx;
use RZP\Models\Payment;
use RZP\Constants\Table;
use RZP\Trace\TraceCode;
use RZP\Models\FundAccount;
use RZP\Models\Customer\Token;
use RZP\Models\Merchant\Account;
use RZP\Models\Base\Traits\ExternalCore;
use RZP\Models\Base\Traits\ExternalRepo;

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
     * Fetches card id's with given provider_reference_id and merchant_id
     *
     * @param  string $providerReferenceId
     * @param  string $merchantId
     * @return array               card ids matching
     */
    public function fetchCardIdsWithProviderReferenceId(string $providerReferenceId, string $merchantId): array
    {
        return $this->newQuery()
                    ->select(Entity::ID)
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::PROVIDER_REFERENCE_ID, '=', $providerReferenceId)
                    ->pluck(Entity::ID)->toArray();
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

    public function findCardMerchantIdsByFingerprint(string $fingerprint,  array $merchant_ids, int $limit)
    {
        $createdAt  = $this->dbColumn(Entity::CREATED_AT);

        $globalFingerprint = $this->dbColumn(Entity::GLOBAL_FINGERPRINT);


        // TODO: Further optimization can be picked up later on this. Once a merchant is found for given fingerprint
        //       These is no need to query further rows.
        return  $this->newQueryWithConnection($this->getSlaveConnection())
            ->select(Entity::MERCHANT_ID)
            ->whereNotNull($globalFingerprint)
            ->where($globalFingerprint, '=', $fingerprint)
            ->whereIn(Entity::MERCHANT_ID, $merchant_ids)
            ->distinct()
            ->limit($limit)
            ->get()
            ->pluck(Entity::MERCHANT_ID)->toArray();
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
        $card = null;

        try
        {
            $card = $this->findOrFail($cardId);
        }
        catch (\Throwable $exception) {}

        return $card;
    }

    public function fetchCardsWithVaultToken(string $vaultToken, string $merchantId)
    {
        $connectionName = $this->getSlaveConnection();

        if ((bool) ConfigKey::get(ConfigKey::CARD_ARCHIVAL_FALLBACK_ENABLED, false))
        {
            $connectionName = $this->getConnectionFromType(ConnectionType::ARCHIVED_DATA_REPLICA);
        }

        // Query Executed - select `*` from `cards` where `merchant_id` = ? and `vault_token` = ?
        return $this->newQueryWithConnection($connectionName)
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

    public function addQueryParamFundAccountType($query, $params)
    {
        $fundAccount = Table::FUND_ACCOUNT;
        $cardsIdColumn = $this->repo->card->dbColumn(Entity::ID);
        $fundAccountIdForeignColumn = $this->repo->fund_account->dbColumn(FundAccount\Entity::ACCOUNT_ID);

        $query->from(\DB::raw(Table::CARD.' USE INDEX (cards_created_at_index)'));

        return $query->select(Table::CARD . ".*")
                    ->join($fundAccount, $cardsIdColumn, '=', $fundAccountIdForeignColumn)
                    ->where('fund_accounts.account_type', $params['fund_account_type']);
    }


    public function saveOrFail($card , array $options = [])
    {
        $arr = [
            Card\Entity::NAME           => $card[Card\Entity::NAME] ?? '',
            Card\Entity::IIN            => $card[Card\Entity::IIN] ?? '',
            Card\Entity::EXPIRY_MONTH   => $card[Card\Entity::EXPIRY_MONTH] ?? '',
            Card\Entity::EXPIRY_YEAR    => $card[Card\Entity::EXPIRY_YEAR] ?? '',
        ];

        $this->app = App::getFacadeRoot();

        $setNullConfig = $this->isUnsetCardMetaDataConfigEnabled();

        $this->app['trace']->info(TraceCode::STORE_EMPTY_VALUE_CARD_METADATA, [
            'setNullConfig' => $setNullConfig,
            'card_id'       => $card->getId()
        ]);

        if ( $setNullConfig === true and $this->checkIfCardMetaDataIsApplicableForDBSave($card) === false)
        {
            unset($card[Card\Entity::IIN]);
            unset($card[Card\Entity::NAME]);
            unset($card[Card\Entity::EXPIRY_MONTH]);
            unset($card[Card\Entity::EXPIRY_YEAR]);
        }

        parent::saveOrFail($card);

        if ($setNullConfig === true)
        {
            $card->fill($arr);
        }
    }

    public function checkIfCardMetaDataIsApplicableForDBSave(Card\Entity $card) : bool
    {
        $app  = \App::getFacadeRoot();

        $auth = $app['basicauth'];

        $merchant = $auth->getMerchant();

        if ($merchant === null)
        {
            $merchant = $this->merchant;
        }
        $isMalaysianRegionalFlow = $merchant != null && Country::matches($merchant->getCountry(), Country::MY);

        if ($card->isInternational() ===  true or $card->isBajaj() === true or $isMalaysianRegionalFlow)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    protected function isUnsetCardMetaDataConfigEnabled(): bool
    {
        return (bool) Admin\ConfigKey::get(Admin\ConfigKey::SET_CARD_METADATA_NULL, true);
    }

    public function getCardNetwork($disputeId)
    {
        $paymentCardIdColumn = $this->repo->payment->dbColumn(Payment\Entity::CARD_ID);
        $paymentIdColumn = $this->repo->payment->dbColumn(Payment\Entity::ID);
        $disputePaymentIdColumn = $this->repo->dispute->dbColumn(\RZP\Models\Dispute\Entity::PAYMENT_ID);
        $cardIdColumn = $this->repo->card->dbColumn(Entity::ID);
        $disputeIdColumn = $this->repo->dispute->dbColumn(Entity::ID);

        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->select(\RZP\Models\Card\Entity::NETWORK)
            ->join(Table::PAYMENT, $paymentCardIdColumn, '=', $cardIdColumn)
            ->join(Table::DISPUTE, $disputePaymentIdColumn, '=', $paymentIdColumn)
            ->where($disputeIdColumn, '=', $disputeId)
            ->pluck(\RZP\Models\Card\Entity::NETWORK)
            ->toArray();
    }
}
