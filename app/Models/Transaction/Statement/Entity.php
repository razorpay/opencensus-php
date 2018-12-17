<?php

namespace RZP\Models\Transaction\Statement;

use RZP\Models\Payout;
use RZP\Models\Transaction;
use RZP\Models\BankTransfer;
use RZP\Constants\Entity as E;
use RZP\Models\Base\PublicEntity;

/**
 * Class Entity
 *
 * @package RZP\Models\Transaction\Statement
 */
class Entity extends Transaction\Entity
{
    protected $entity = 'statement';

    protected $public = [
        self::ID,
        self::ENTITY,
        self::AMOUNT,
        self::CREDIT,
        self::DEBIT,
        self::BALANCE,
        self::SOURCE,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::SOURCE,
    ];


    // Public setters

    /**
     * Sets public attributes of source relation.
     * @param $array
     */
    public function setPublicSourceAttribute(array & $array)
    {
        switch ($this->getType())
        {
            case E::PAYOUT:
                return $this->setPublicSourceAttributeForPayout($array);

            case E::BANK_TRANSFER:
                return $this->setPublicSourceAttributeForBankTransfer($array);

            default:
                return $this->setPublicSourceAttributeForDefault($array);
        }
    }

    protected function setPublicSourceAttributeForPayout(array & $array)
    {
        $array[self::SOURCE] = array_only(
            $array[self::SOURCE],
            [
                Payout\Entity::ID,
                Payout\Entity::ENTITY,
                // Todo: Update these after 'payout-on-fa' branch is merged.
                // Payout\Entity::CUSTOMER_ID,
                // Payout\Entity::DESTINATION_ID,
                Payout\Entity::METHOD,
                Payout\Entity::NOTES,
            ]);

        // Todo: Update these after 'payout-on-fa' branch is merged.
        // $array[self::SOURCE][Payout\Entity::CUSTOMER]    = $this->source->customer->toArrayPublic();
        // $array[self::SOURCE][Payout\Entity::DESTINATION] = $this->source->destination->toArrayPublic();
    }

    protected function setPublicSourceAttributeForBankTransfer(array & $array)
    {
        $array[self::SOURCE] = array_only(
            $array[self::SOURCE],
            [
                BankTransfer\Entity::MODE,
                BankTransfer\Entity::BANK_REFERENCE,
                BankTransfer\Entity::AMOUNT,
                BankTransfer\Entity::PAYER_BANK_ACCOUNT,
            ]);

        /** @var BankTransfer\Entity $bankTransfer */
        $bankTransfer = $this->source;

        // Prepends id & entity as they are not exposed in bank_transfer entity, for now.
        $array[self::SOURCE] = [
                                   BankTransfer\Entity::ID     => $bankTransfer->getPublicId(),
                                   BankTransfer\Entity::ENTITY => $bankTransfer->getEntity(),
                               ] + $array[self::SOURCE];

        $array[self::SOURCE][BankTransfer\Entity::PAYER_NAME]    = $bankTransfer->getPayerName();
        $array[self::SOURCE][BankTransfer\Entity::PAYER_ACCOUNT] = $bankTransfer->getPayerAccount();
        $array[self::SOURCE][BankTransfer\Entity::PAYER_IFSC]    = $bankTransfer->getPayerIfsc();
    }

    protected function setPublicSourceAttributeForDefault(array & $array)
    {
        $array[self::SOURCE] = array_only(
            $array[self::SOURCE],
            [
                PublicEntity::ID,
                PublicEntity::ENTITY,
            ]);
    }

    /**
     * {@inheritDoc}
     */
    public function toArrayPublic()
    {
        // Transaction\Entity's toArrayPublic() for some legacy reason unsets lot of attributes.
        return PublicEntity::toArrayPublic();
    }
}
