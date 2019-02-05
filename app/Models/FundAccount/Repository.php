<?php

namespace RZP\Models\FundAccount;

use RZP\Models\Vpa;
use RZP\Models\Base;
use RZP\Models\Contact;
use RZP\Models\Merchant;
use RZP\Models\BankAccount;
use RZP\Constants\Entity as E;

/**
 * Class Repository
 *
 * @package RZP\Models\FundAccount
 */
class Repository extends Base\Repository
{
    protected $entity = 'fund_account';

    protected $expands = [
        Entity::ACCOUNT,
    ];

    /**
     * Get fund account if exists with similar details.
     * @param  array           $input
     * @param  Merchant\Entity $merchant
     * @param  Contact\Entity  $merchant
     * @return Entity|null
     */
    public function getFundAccountWithSimilarDetails(array $input, Merchant\Entity $merchant, Contact\Entity $contact)
    {
        // Finds account (bank account/vpa) against input details.
        switch ($input[Entity::ACCOUNT_TYPE])
        {
            case Type::BANK_ACCOUNT:
                $account = $this->repo
                                ->bank_account
                                ->findLatestBankAccountByAccountNumber(
                                    $input[Entity::DETAILS][BankAccount\Entity::ACCOUNT_NUMBER],
                                    $input[Entity::DETAILS][BankAccount\Entity::IFSC],
                                    E::CONTACT,
                                    $merchant->getId());

                break;

            case Type::VPA:
                $account = $this->repo
                                ->vpa
                                ->findLatestByAddressAndMerchantId(
                                    $input[Entity::DETAILS][Vpa\Entity::ADDRESS],
                                    $merchant->getId());

                break;

            default:
                $account = null;

                break;
        }

        // If account exists then returns first fund account against this account and given contact.
        if ($account !== null)
        {
            return $this->newQuery()
                        ->where(Entity::ACCOUNT_ID, $account->getId())
                        ->where(Entity::ACCOUNT_TYPE, $account->getEntity())
                        ->where(Entity::SOURCE_ID, $contact->getId())
                        ->where(Entity::SOURCE_TYPE, $contact->getEntity())
                        ->first();
        }
    }

    protected function addQueryParamCustomerId($query, $params)
    {
        $query->where(Entity::SOURCE_ID, $params[Entity::CUSTOMER_ID])
              ->where(Entity::SOURCE_TYPE, E::CUSTOMER);
    }

    protected function addQueryParamContactId($query, $params)
    {
        $query->where(Entity::SOURCE_ID, $params[Entity::CONTACT_ID])
              ->where(Entity::SOURCE_TYPE, E::CONTACT);
    }
}
