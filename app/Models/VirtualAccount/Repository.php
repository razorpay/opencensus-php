<?php

namespace RZP\Models\VirtualAccount;

use Carbon\Carbon;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Base\BuilderEx;
use RZP\Models\Merchant\Entity as Merchant;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::VIRTUAL_ACCOUNT;

    protected function addQueryParamReceiverType(BuilderEx $query, array $params)
    {
        $receiverTypes = explode(',', $params[Entity::RECEIVER_TYPE]);

        $query->where(function ($query) use ($receiverTypes)
        {
            foreach ($receiverTypes as $receiverType)
            {
                $query->orWhereNotNull($receiverType . '_id');
            }
        });
    }

    public function getActiveVirtualAccountFromBalanceId(string $balanceId)
    {
        return $this->newQuery()
                    ->where(Entity::STATUS, '=', Status::ACTIVE)
                    ->where(Entity::BALANCE_ID, '=', $balanceId)
                    ->first();
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

    public function findByPublicIdAndMerchantWithRelations(string $id, Merchant $merchant, array $relations = [])
    {
        Entity::verifyIdAndStripSign($id);

        return $this->newQuery()
                    ->merchantId($merchant->getId())
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

    public function fetchVirtualAccountsToBeClosed()
    {
        $currentTime = Carbon::now()->getTimestamp();

        $query = $this->newQuery()
                      ->where(Entity::STATUS, '=', Status::ACTIVE)
                      ->whereNotNull(Entity::CLOSE_BY)
                      ->where(Entity::CLOSE_BY, '<', $currentTime);

        return $query->get();
    }

    public function getActiveVirtualAccountFromVpaId(string $vpaId)
    {
        return $this->newQuery()
                    ->where(Entity::STATUS, '=', Status::ACTIVE)
                    ->where(Entity::VPA_ID, '=', $vpaId)
                    ->first();
    }

}
