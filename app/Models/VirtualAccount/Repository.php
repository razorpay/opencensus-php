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
use Illuminate\Support\Facades\DB;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\UpiTransfer\Entity as UpiTransferEntity;
use RZP\Models\BankTransfer\Entity as BankTransferEntity;

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

    // In bank account table there can be multiple rows with same account number, type column differentiates those rows,
    // if type is virtual account, that bank account is mapped to virtual account
    public function getActiveVirtualAccountFromAccountNumberAndIfsc($accountNumber, $ifsc)
    {
        $virtualAccountIdColumn          = $this->repo->virtual_account->dbColumn(Entity::ID);
        $virtualAccountTableStatusColumn = $this->repo->virtual_account->dbColumn('status');

        $bankAccountTable                = $this->repo->bank_account->getTableName();
        $bankAccountTypeColumn           = $this->repo->bank_account->dbColumn(BankAccount\Entity::TYPE);
        $bankAccountEntityIdColumn       = $this->repo->bank_account->dbColumn(BankAccount\Entity::ENTITY_ID);
        $ifscCodeColumn                  = $this->repo->bank_account->dbColumn(BankAccount\Entity::IFSC_CODE);
        $accountNumberColumn             = $this->repo->bank_account->dbColumn(BankAccount\Entity::ACCOUNT_NUMBER);

        return $this->newQuery()
                    ->join($bankAccountTable, $virtualAccountIdColumn, '=', $bankAccountEntityIdColumn)
                    ->where($accountNumberColumn, '=', $accountNumber)
                    ->where($ifscCodeColumn, '=', $ifsc)
                    ->where($bankAccountTypeColumn, '=', BankAccount\Type::VIRTUAL_ACCOUNT)
                    ->where($virtualAccountTableStatusColumn, '=', Status::ACTIVE)
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

    public function fetchVirtualAccountsToBeClosed($limit = 5000)
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

    public function fetchActiveOrPaidVirtualAccountIds(array $virtualAccountIds, $limit = 1000)
    {
        return $this->newQuery()
            ->whereIn(Entity::STATUS,[Status::ACTIVE, Status::PAID])
            ->whereIn(Entity::ID, $virtualAccountIds)
            ->limit($limit)
            ->get()
            ->all();
    }

    public function fetchInactiveVirtualAccounts(array $params, int $expiryDelta = 90)
    {
        $vaExpiryTimeStamp = Carbon::now(Timezone::IST)->subDays($expiryDelta)->startOfDay()->getTimestamp();

        $virtualAccountCreatedAt = $this->dbColumn(Entity::CREATED_AT);
        $virtualAccountStatus = $this->dbColumn(Entity::STATUS);
        $virtualAccountId = $this->dbColumn(Entity::ID);
        $vaBankAccountId = $this->dbColumn(Entity::BANK_ACCOUNT_ID);

        $bankTransferId = $this->repo->bank_transfer->dbColumn(BankTransferEntity::ID);
        $bankTransferVAId = $this->repo->bank_transfer->dbColumn(BankTransferEntity::VIRTUAL_ACCOUNT_ID);
        $bankTransfersCreatedAt = $this->repo->bank_transfer->dbColumn(BankTransferEntity::CREATED_AT);
        $bankTransferPaymentId = $this->repo->bank_transfer->dbColumn(BankTransferEntity::PAYMENT_ID);

        $upiTransferId = $this->repo->upi_transfer->dbColumn(UpiTransferEntity::ID);
        $upiTransferVaId = $this->repo->upi_transfer->dbColumn(UpiTransferEntity::VIRTUAL_ACCOUNT_ID);
        $upiTransferCreatedAt = $this->repo->upi_transfer->dbColumn(UpiTransferEntity::CREATED_AT);
        $upiTransferPaymentId = $this->repo->upi_transfer->dbColumn(UpiTransferEntity::PAYMENT_ID);


        $query = $this->newQueryWithConnection($this->getSlaveConnection())
            ->select(DB::raw("count($bankTransferId) as bank_payment_count, count($upiTransferId) as upi_payment_count, $virtualAccountId"))
            ->leftJoin(Table::BANK_TRANSFER,function($join)
            use (
                $expiryDelta,
                $virtualAccountId,
                $bankTransferVAId,
                $bankTransfersCreatedAt,
                $vaExpiryTimeStamp,
                $bankTransferPaymentId)
            {
                $join
                    ->on($virtualAccountId, '=', $bankTransferVAId)
                    ->where($bankTransfersCreatedAt,'>=', $vaExpiryTimeStamp)
                    ->where($bankTransferPaymentId,'<>', '');

            })
            ->leftJoin(Table::UPI_TRANSFER,function($join)
            use (
                $expiryDelta,
                $virtualAccountId,
                $upiTransferVaId,
                $upiTransferCreatedAt,
                $vaExpiryTimeStamp,
                $upiTransferPaymentId)
            {
                $join
                    ->on($virtualAccountId, '=', $upiTransferVaId)
                    ->where($upiTransferCreatedAt,'>=', $vaExpiryTimeStamp)
                    ->where($upiTransferPaymentId,'<>', '');

            })
            ->whereIn($virtualAccountStatus, [Status::PAID,Status::ACTIVE])
            ->whereNotNull($vaBankAccountId)
            ->where($virtualAccountCreatedAt, '<=', $vaExpiryTimeStamp);

            $this->addQueryParamGatewayIfapplicable($query, $params);

            $this->addQueryParamMerchantIdIfApplicable($query, $params);

            $this->addQueryParamCreatedAtIfApplicable($query, $params);

            $this->addQueryParamVirtualAccountIdIfApplicable($query, $params);

            $query = $query->orderBy($virtualAccountCreatedAt)
            ->groupBy($virtualAccountId)
            ->having('bank_payment_count', '=', 0)
            ->having('upi_payment_count', '=', 0);

        return $this->getPaginated($query, $params)->pluck(Entity::ID);
    }

    private function addQueryParamGatewayIfapplicable($query, $params)
    {
        if ((isset($params['gateway']) === false) or (empty($params['gateway']) === true))
        {
            return;
        }

        $bankAccountIfscCol = $this->repo->bank_account->dbColumn(BankAccount\Entity::IFSC_CODE);
        $vaBankAccountId    = $this->dbColumn(Entity::BANK_ACCOUNT_ID);
        $bankAccountId      = $this->repo->bank_account->dbColumn(BankAccount\Entity::ID);

        $ifscCode = $params['gateway'];

        $query->join(Table::BANK_ACCOUNT, $vaBankAccountId, '=', $bankAccountId)
            ->where($bankAccountIfscCol, '=', $ifscCode);
    }

    private function addQueryParamMerchantIdIfApplicable($query, $params)
    {
        $merchantId = $this->dbColumn(Entity::MERCHANT_ID);

        if(isset($params['merchant_ids']) === true)
        {
            $query->whereIn($merchantId,$params['merchant_ids']);
        }

        if(isset($params['exclude_mids']) === true)
        {
            $query->whereNotIn($merchantId,$params['exclude_mids']);
        }
    }

    private function addQueryParamCreatedAtIfApplicable($query, $params)
    {
        $createdAt = $this->dbColumn(Entity::CREATED_AT);

        if (isset($params['start_date']) === true and empty($params['start_date']) === false)
        {
            $startDateTimestamp = Carbon::createFromFormat('Y-m-d',$params['start_date'])->startOfDay()
                                                                                                ->timezone(Timezone::IST)
                                                                                                ->getTimestamp();

            $query->where($createdAt,'>',$startDateTimestamp);
        }

        if (isset($params['end_date']) === true and empty($params['end_date']) === false)
        {
            $endDateTimestamp = Carbon::createFromFormat('Y-m-d',$params['end_date'])->startOfDay()
                                                                                            ->timezone(Timezone::IST)
                                                                                            ->getTimestamp();
            $query->where($createdAt,'<',$endDateTimestamp);
        }
    }

    private function addQueryParamVirtualAccountIdIfApplicable($query, $params)
    {
        $virtualAccountId = $this->dbColumn(Entity::ID);

        if (isset($params['virtual_account_ids']) === true and empty($params['virtual_account_ids']) === false)
        {
            $query->whereIn($virtualAccountId,$params['virtual_account_ids']);
        }
    }

    public function fetchByOfflineId(string $offlineId)
    {
        return $this->newQuery()
                    ->where(Entity::OFFLINE_CHALLAN_ID, $offlineId)
                    ->first();
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

    /*
     * select `virtual_accounts`.* from `virtual_accounts` inner join `balance` on
     *      `virtual_accounts`.`balance_id` = `balance`.`id` where
     *          `virtual_accounts`.`status` = ? and
     *          `virtual_accounts`.`merchant_id` = ? and
     *          `balance`.`type` = ? and
     *          `balance`.`account_type` = ? and
     *          `virtual_accounts`.`deleted_at` is null
     */
    public function fetchActiveBankingVirtualAccountsFromMerchantId($merchantId)
    {
        $virtualAccountCols             = $this->dbColumn('*');
        $virtualAccountBalanceIdColumn  = $this->dbColumn(Entity::BALANCE_ID);
        $virtualAccountStatusColumn     = $this->dbColumn(Entity::STATUS);
        $virtualAccountMerchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);

        $balanceTable             = $this->repo->balance->getTableName();
        $balanceIdColumn          = $this->repo->balance->dbColumn(Balance\Entity::ID);
        $balanceTypeColumn        = $this->repo->balance->dbColumn(Balance\Entity::TYPE);
        $balanceAccountTypeColumn = $this->repo->balance->dbColumn(Balance\Entity::ACCOUNT_TYPE);

        return $this->newQuery()
                    ->select($virtualAccountCols)
                    ->join($balanceTable, $virtualAccountBalanceIdColumn, '=', $balanceIdColumn)
                    ->where($virtualAccountStatusColumn, '=', Status::ACTIVE)
                    ->where($virtualAccountMerchantIdColumn, '=', $merchantId)
                    ->where($balanceTypeColumn, '=', Balance\Type::BANKING)
                    ->where($balanceAccountTypeColumn, '=', Balance\AccountType::SHARED)
                    ->get();
    }
}
