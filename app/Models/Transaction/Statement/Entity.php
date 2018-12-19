<?php

namespace RZP\Models\Transaction\Statement;

use RZP\Models\Payout;
use RZP\Models\Transaction;
use RZP\Models\BankTransfer;
use RZP\Constants\Entity as E;
use RZP\Models\Base\PublicEntity;
use RZP\Exception\LogicException;

/**
 * Class Entity
 *
 * @package RZP\Models\Transaction\Statement
 */
class Entity extends Transaction\Entity
{
    // Derived attributes
    const ACCOUNT_NUMBER = 'account_number';

    protected $entity = 'statement';

    protected $public = [
        self::ID,
        self::ENTITY,
        self::ACCOUNT_NUMBER,
        self::AMOUNT,
        self::CURRENCY,
        self::CREDIT,
        self::DEBIT,
        self::BALANCE,
        self::SOURCE,
        self::CREATED_AT,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::SOURCE,
    ];

    protected $appends = [
        self::ACCOUNT_NUMBER,
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
                throw new LogicException(
                    'Transaction of unexpected type is being exposed to public!',
                    null,
                    array_only($array[self::SOURCE], [self::ID, self::ENTITY]));
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

    /**
     * {@inheritDoc}
     * Transaction/Statement/* is internal code organization for exposing transaction.
     * Exposed APIs and entity names etc are 'transaction' only.
     */
    public function setPublicEntityAttribute(array & $array)
    {
        $array[self::ENTITY] = 'transaction';
    }

    // Appends

    public function getAccountNumberAttribute()
    {
        return $this->accountBalance->getAccountNumber();
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
