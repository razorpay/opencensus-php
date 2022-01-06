<?php

namespace RZP\Models\VirtualAccount;

use Carbon\Carbon;
use RZP\Constants;
use RZP\Models\Vpa;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Base\BuilderEx;
use RZP\Constants\Table;
use RZP\Models\Customer;
use RZP\Constants\Timezone;
use RZP\Models\BankAccount;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Balance;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Merchant\Entity as Merchant;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::VIRTUAL_ACCOUNT;

    protected function addQueryParamReceiverType(BuilderEx $query, array $params)
    {
        $receiverTypes = explode(',', $params[Entity::RECEIVER_TYPE]);

        $query->where(function($query) use ($receiverTypes)
        {
            foreach ($receiverTypes as $receiverType)
            {
                $query->orWhereNotNull($receiverType . '_id');
            }
        });
    }

    /**
     * Return virtual accounts linked to this balanceId
     *
     * @param string $balanceId
     * @param string $seriesPrefix this is the gateway_merchant_id in terminals table
     *
     * @return Entity|null
     */
    public function getActiveVirtualAccountsFromBalanceId(string $balanceId)
    {
        $virtualAccountAttrs            = $this->repo->virtual_account->dbColumn('*');
        $virtualAccountBankAccountIdCol = $this->repo->virtual_account->dbColumn(Entity::BANK_ACCOUNT_ID);
        $bankAccountIdColumn            = $this->repo->bank_account->dbColumn(BankAccount\Entity::ID);
        $bankAccountAccountNumberColumn = $this->repo->bank_account->dbColumn(BankAccount\Entity::ACCOUNT_NUMBER);

        return $this->newQuery()
                    ->select($virtualAccountAttrs, $bankAccountAccountNumberColumn)
                    ->join(Table::BANK_ACCOUNT, $virtualAccountBankAccountIdCol, '=', $bankAccountIdColumn)
                    ->where(Entity::STATUS, '=', Status::ACTIVE)
                    ->where(Entity::BALANCE_ID, '=', $balanceId)
                    ->get();
    }

    public function getActiveVirtualAccountFromBankAccountId(string $bankAccountId)
    {
        return $this->newQuery()
                    ->where(Entity::STATUS, '=', Status::ACTIVE)
                    ->where(Entity::BANK_ACCOUNT_ID, '=', $bankAccountId)
                    ->first();
    }


    public function findActiveVirtualAccountByOrder(Order\Entity $order)
    {
        return $this->newQuery()
                    ->where(Entity::STATUS, '=', Status::ACTIVE)
                    ->where(Entity::ENTITY_ID, '=', $order->getId())
                    ->first();
    }

    public function getActiveVirtualAccountFromQrCodeId(string $qrCodeId)
    {
        return $this->newQuery()
                    ->where(Entity::STATUS, '=', Status::ACTIVE)
                    ->where(Entity::QR_CODE_ID, '=', $qrCodeId)
                    ->first();
    }

    public function getVirtualAccountFromQrCodeId(string $qrCodeId)
    {
        return $this->newQuery()
                    ->where(Entity::QR_CODE_ID, '=', $qrCodeId)
                    ->first();
    }

    public function serializeForIndexing(PublicEntity $entity): array
    {
        $serialized = parent::serializeForIndexing($entity);

        if ($entity->customer !== null)
        {
            $serialized[Customer\Entity::CONTACT] = $entity->customer->getContact();

            $serialized[Customer\Entity::NAME] = $entity->customer->getName();

            $serialized[Customer\Entity::EMAIL] = $entity->customer->getEmail();
        }

        if ($entity->bankAccount !== null)
        {
            $serialized[BankAccount\Entity::ACCOUNT_NUMBER] = $entity->bankAccount->getAccountNumber();
        }

        if ($entity->vpa !== null)
        {
            $serialized[Entity::VPA] = $this->cleanSpecialCharacter($entity->vpa->getAddress());
        }

        return $serialized;
    }

    function cleanSpecialCharacter($string)
    {
        return preg_replace('/[^A-Za-z0-9]/', '', $string); // Removes special chars.
    }

    public function findByPublicIdAndMerchantWithRelations(string $id, Merchant $merchant, array $relations = [])
    {
        Entity::verifyIdAndStripSign($id);

        return $this->newQuery()
                    ->merchantId($merchant->getId())
                    ->with($relations)
                    ->findOrFailPublic($id);
    }

    public function findByPublicIdWithRelations(string $id, array $relations = [])
    {
        Entity::verifyIdAndStripSign($id);

        return $this->newQuery()
                    ->with($relations)
                    ->findOrFailPublic($id);
    }


    public function findActiveByDescriptorAndMerchant(
        string $descriptor,
        Merchant $merchant)
    {
        $query = $this->newQuery()
                      ->merchantId($merchant->getId())
                      ->where(Entity::STATUS, '=', Status::ACTIVE)
                      ->where(Entity::DESCRIPTOR, '=', $descriptor);

        return $query->get();
    }

    public function fetchExcessPaidVirtualAccounts()
    {
        $excessCondition = 'amount_received > (amount_expected + amount_reversed)';

        $query = $this->newQuery()
                      ->where(Entity::STATUS, '=', Status::PAID)
                      ->whereNotNull(Entity::AMOUNT_EXPECTED)
                      ->whereRaw($excessCondition);

        return $query->get();
    }

    public function existsByBalanceId(string $balanceId): bool
    {
        return $this->newQuery()
                    ->where(Entity::BALANCE_ID, $balanceId)
                    ->exists();
    }

    public function fetchVirtualAccountsToBeClosed($limit = 10000)
    {
        $now = Carbon::now(Timezone::IST)->getTimestamp();

        $nowMinus14days = Carbon::now(Timezone::IST)->addDays(-14)->getTimestamp();

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->where(Entity::STATUS, '=', Status::ACTIVE)
                    ->whereBetween(Entity::CLOSE_BY, array($nowMinus14days, $now))
                    ->limit($limit)
                    ->pluck(Entity::ID)
                    ->toArray();
    }

    public function getActiveVirtualAccountFromVpaId(string $vpaId)
    {
        return $this->newQuery()
                    ->where(Entity::STATUS, '!=', Status::PAID)
                    ->where(Entity::VPA_ID, '=', $vpaId)
                    ->first();
    }

    public function getYesbankMigrateQuery(string $afterId, string $fromTime, string $toTime, int $limit, array $merchantIds = [])
    {
        /** @var BuilderEx $query */
        $query = $this->newQuery();

        $query->where(Entity::ID, '>', $afterId)
              ->where(Entity::STATUS, Status::ACTIVE)
              ->whereNotNull(Entity::BANK_ACCOUNT_ID)
              ->whereNull(Entity::BANK_ACCOUNT_ID2)
              ->whereBetween(Entity::CREATED_AT, [$fromTime, $toTime])
              ->orderBy(Entity::ID);

        if (empty($merchantIds) === false)
        {
            $query->whereIn(Entity::MERCHANT_ID, $merchantIds);
        }

        $query->limit($limit);

        return $query;
    }

    /**
     *
     * select  distinct `merchant_id` from `virtual_accounts`
     *         where `status` = ? and
     *        `merchant_id` in (?)
     *         order by `merchant_id` asc
     *
     * @param array $merchantIds
     *
     * @return array
     */
    public function fetchActiveVirtualAccountForMerchantIds(array $merchantIds): array
    {
        return $this->newQuery()
                    ->where(Entity::STATUS, '=', Status::ACTIVE)
                    ->whereIn(Entity::MERCHANT_ID, $merchantIds)
                    ->orderBy(Entity::MERCHANT_ID)
                    ->distinct()
                    ->pluck(Entity::MERCHANT_ID)
                    ->toArray();

    }

    /**
     *
     * select  * from `virtual_accounts`
     *         where `status` = ? and
     *        `merchant_id` in (?)
     *        `id` not in (?)
     *         limit 100
     *
     * @param array $merchantIds
     *
     * @return array
     */
    public function fetchActiveVirtualAccountsForMerchantId($merchantId, $skipVirtualAccountIds, $limit = 100): array
    {
        return $this->newQuery()
                    ->where(Entity::STATUS, '=', Status::ACTIVE)
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->whereNotIn(Entity::ID, $skipVirtualAccountIds)
                    ->limit($limit)
                    ->get()
                    ->all();
    }

    public function fetchActiveVirtualAccountIds(array $virtualAccountIds, $limit = 100)
    {
        return $this->newQuery()
                    ->where(Entity::STATUS, '=', Status::ACTIVE)
                    ->whereIn(Entity::ID, $virtualAccountIds)
                    ->limit($limit)
                    ->get()
                    ->all();
    }

    public function findActiveVirtualAccountForOrderByCustomer(Customer\Entity $customer)
    {
        return $this->newQuery()
                    ->whereIn(Entity::STATUS, [Status::ACTIVE, Status::PAID])
                    ->where(Entity::CUSTOMER_ID, '=', $customer->getId())
                    ->where(Entity::ENTITY_TYPE, '=', 'order')
                    ->whereNotNull(Entity::ENTITY_ID)
                    ->first();
    }

    public function findVirtualAccountWithXBalanceOrFail(string $bankAccountId)
    {
        return $this->newQuery()
                    ->join(Table::BALANCE, Entity::BALANCE_ID,'=', Table::BALANCE.'.'.Balance\Entity::ID)
                    ->where(Entity::BANK_ACCOUNT_ID, '=', $bankAccountId)
                    ->where(Balance\Entity::TYPE, '=', Balance\Type::BANKING)
                    ->where(Balance\Entity::ACCOUNT_TYPE, '=', Balance\AccountType::SHARED)
                    ->firstOrFail();
    }

    public function saveOrFail($virtualAccount, array $options = array())
    {
        $order = $this->stripOrderRelationIfApplicable($virtualAccount);

        parent::saveOrFail($virtualAccount, $options);

        $this->associateOrderIfApplicable($virtualAccount, $order);
    }

    protected function stripOrderRelationIfApplicable($virtualAccount)
    {
        $entity = $virtualAccount->entity;

        if (($entity === null) or
            ($entity->getEntityName() !== E::ORDER))
        {
            return;
        }

        $virtualAccount->entity()->dissociate();

        $virtualAccount->setAttribute(Entity::ENTITY_ID, $entity->getId());

        $virtualAccount->setAttribute(Entity::ENTITY_TYPE, E::ORDER);

        return $entity;
    }

    public function associateOrderIfApplicable($virtualAccount, $order)
    {
        if ($order === null)
        {
            return;
        }

        $virtualAccount->entity()->associate($order);
    }
}
