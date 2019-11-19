<?php

namespace RZP\Models\NodalBeneficiary;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    const CARD         = 'card';

    const BANK_ACCOUNT = 'bank_account';

    protected $entity = 'nodal_beneficiary';

    protected $appFetchParamRules = [
        Entity::CHANNEL             => 'filled|string|max:8',
        Entity::MERCHANT_ID         => 'filled|string|size:14',
        Entity::CARD_ID             => 'sometimes|string|size:14',
        Entity::BANK_ACCOUNT_ID     => 'sometimes|string|size:14',
        Entity::REGISTRATION_STATUS => 'filled|string|max:40',
        Entity::BENEFICIARY_CODE    => 'sometimes|filled|nullable|string|max:30',
    ];

    /**
     * @param string $bankAccountId
     * @param string $channel
     * @return Entity
     */
    public function fetchBankAccountBeneficiaryDetailsForChannel(string $bankAccountId, string $channel): Entity
    {
        return $this->newQuery()
                    ->where(Entity::BANK_ACCOUNT_ID, $bankAccountId)
                    ->where(Entity::CHANNEL, $channel)
                    ->withTrashed()
                    ->firstorFail();
    }

    public function fetchCardBeneficiaryDetailsForChannel(string $cardId, string $channel): Entity
    {
        return $this->newQuery()
                    ->where(Entity::CARD_ID, $cardId)
                    ->where(Entity::CHANNEL, $channel)
                    ->withTrashed()
                    ->firstorFail();
    }

    public function fetchNonRegisteredBankAccountBeneficiary(string $bankAccountId, string $channel)
    {
        $result =  $this->newQuery()
                        ->where(Entity::BANK_ACCOUNT_ID, $bankAccountId)
                        ->where(Entity::CHANNEL, $channel)
                        ->withTrashed()
                        ->first();

        return $result;
    }

    public function fetchNonRegisteredCardBeneficiary(string $cardId, string $channel)
    {
        $result =  $this->newQuery()
                        ->where(Entity::CARD_ID, $cardId)
                        ->where(Entity::CHANNEL, $channel)
                        ->withTrashed()
                        ->first();

        return $result;
    }

    public function fetchNonRegisteredBankAccount(string $channel): array
    {
        return $this->newQuery()
                    ->select(Entity::BANK_ACCOUNT_ID)
                    ->where(Entity::CHANNEL, $channel)
                    ->where(Entity::REGISTRATION_STATUS, '!=', Status::REGISTERED)
                    ->withTrashed()
                    ->pluck(Entity::BANK_ACCOUNT_ID)->toArray();
    }

    /**
     * @param string $bankAccountId
     * @param string $channel
     * @return mixed
     */
    public function fetchActivatedBankAccountBeneficiaryDetailsForChannel(string $bankAccountId, string $channel)
    {
        return $this->newQuery()
                    ->where(Entity::BANK_ACCOUNT_ID, $bankAccountId)
                    ->where(Entity::CHANNEL, $channel)
                    ->first();
    }

    /**
     * @param string $cardId
     * @param string $channel
     * @return mixed
     */
    public function fetchActivatedCardBeneficiaryDetailsForChannel(string $cardId, string $channel)
    {
        return $this->newQuery()
                    ->where(Entity::CARD_ID, $cardId)
                    ->where(Entity::CHANNEL, $channel)
                    ->first();
    }

    /**
     * (select bank_account_id from nodal_beneficiary limit 100 order by id desc)
     *
     * @param $accountType
     * @param $previousCount
     * @param $size
     * @return mixed
     */
    public function fetchVerifiedBeneficiaryNotRegisteredOnFts($accountType, $previousCount, $size)
    {
        $query = $this->newQuery()
                      ->where(Entity::REGISTRATION_STATUS, Status::VERIFIED)
                      ->orderBy(Entity::CREATED_AT, 'asc');

        if ($accountType === self::BANK_ACCOUNT)
        {
            $query->select(Entity::BANK_ACCOUNT_ID)
                  ->whereNotNull(Entity::BANK_ACCOUNT_ID);
        }

        if ($accountType === self::CARD)
        {
            $query->select(Entity::CARD_ID)
                  ->whereNotNull(Entity::CARD_ID);
        }

        return $query->skip($previousCount)
                     ->take($size)
                     ->pluck(Entity::BANK_ACCOUNT_ID)
                     ->toArray();
    }
}
