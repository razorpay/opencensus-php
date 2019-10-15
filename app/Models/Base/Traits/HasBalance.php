<?php

namespace RZP\Models\Base\Traits;

use RZP\Models\Merchant\Balance;

/**
 * A few entities have balance relation e.g. virtual_account, transaction & payout.
 * This trait includes the relation and few helper methods.
 *
 * @property Balance\Entity $balance
 */
trait HasBalance
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function balance()
    {
        return $this->belongsTo(Balance\Entity::class);
    }

    /**
     * @return string|null
     */
    public function getBalanceId()
    {
        return $this->getAttribute(self::BALANCE_ID);
    }

    public function hasBalance(): bool
    {
        return $this->isAttributeNotNull(self::BALANCE_ID);
    }

    /**
     * Returns balance type.
     * Handles old entities where balance_id is not back filled yet by defaulting to type 'primary'.
     *
     * @return string
     */
    public function getBalanceType(): string
    {
        return optional($this->balance)->getType() ?: Balance\Type::PRIMARY;
    }

    public function isBalanceTypePrimary(): bool
    {
        return $this->getBalanceType() === Balance\Type::PRIMARY;
    }

    public function isBalanceTypeBanking(): bool
    {
        return $this->getBalanceType() === Balance\Type::BANKING;
    }

    public function isBalanceTypeCommission(): bool
    {
        return $this->getBalanceType() === Balance\Type::COMMISSION;
    }

    public function setPublicBalanceIdAttribute(array & $attributes)
    {
        $balanceId = $this->getAttribute(self::BALANCE_ID);

        $attributes[self::BALANCE_ID] = Balance\Entity::getSignedIdOrNull($balanceId);
    }

    /**
     * Appends balance.account_number attribute in used-by entity's toArray() if
     * ACCOUNT_NUMBER exists in $appends.
     *
     * @return string|null
     */
    public function getAccountNumberAttribute()
    {
        return optional($this->balance)->getAccountNumber();
    }
}
