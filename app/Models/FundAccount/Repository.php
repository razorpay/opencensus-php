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
     * @param  array               $input
     * @param  Merchant\Entity     $merchant
     * @param  Contact\Entity|null $contact
     * @return Entity|null
     */
    public function getFundAccountWithSimilarDetails(
        array $input,
        Merchant\Entity $merchant,
        Contact\Entity $contact = null)
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

        // If no underlying account (bank account/vpa) found, return null.
        if ($account === null)
        {
            return;
        }

        // Else gets latest fund account entity with this account and contact(optionally).
        $query = $this->newQuery()
                      ->merchantId($merchant->getId())
                      ->where(Entity::ACCOUNT_ID, $account->getId())
                      ->where(Entity::ACCOUNT_TYPE, $account->getEntity())
                      ->latest();

        if ($contact !== null)
        {
            $query->where(Entity::SOURCE_ID, $contact->getId())
                  ->where(Entity::SOURCE_TYPE, $contact->getEntity());
        }

        return $query->first();
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
