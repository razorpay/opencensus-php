<?php

namespace RZP\Models\Contact;

use RZP\Models\Base;
use RZP\Models\FundAccount;
use RZP\Models\BankAccount;
use RZP\Models\Payout;

/**
 * Class Repository
 *
 * @package RZP\Models\Contact
 */
class Repository extends Base\Repository
{
    protected $entity = 'contact';

    public function isMerchantIdRequiredForFetch()
    {
        return false;
    }

    public function addQueryParamAccountNumber($query, $params)
    {
       $query->join(
            $this->repo->fund_account->getTableName(),
            function ($join)
            {
                $contactId = $this->repo->contact->dbColumn(Entity::ID);

                $fundAccountContactId = $this->repo->fund_account->dbColumn(FundAccount\Entity::CONTACT_ID);

                $fundAccountAccountType = $this->repo->fund_account->dbColumn(FundAccount\Entity::ACCOUNT_TYPE);

                $join->on($fundAccountContactId, '=', $contactId);

                $join->where($fundAccountAccountType, '=', FundAccount\Type::BANK_ACCOUNT);
            }
       );

       $query->join(
          $this->repo->bank_account->getTableName(),
          function ($join) use ($params)
          {
            $bankAccountTable = $this->repo->bank_account->getTableName();
            $fundAccountTable = $this->repo->fund_account->getTableName();

            $join->on($bankAccountTable . '.' . BankAccount\Entity::ID, '=', $fundAccountTable . '.' . FundAccount\Entity::ACCOUNT_ID);

            $join->where($bankAccountTable . '.' . BankAccount\Entity::ACCOUNT_NUMBER, '=', $params['account_number']);
          }
       );

       $query->select($query->getModel()->getTable().'.*');
    }

    public function addQueryParamFundAccountId($query, $params)
    {
      $query->join(
            $this->repo->fund_account->getTableName(),
            function ($join) use ($params)
            {
                $fundAccountIDParam =  Entity::stripSign($params[Entity::FUND_ACCOUNT_ID]);

                $fundAccountTableName = $this->repo->fund_account->getTableName();

                $contactId = $this->repo->contact->dbColumn(Entity::ID);

                $fundAccountId = $this->repo->fund_account->dbColumn(Entity::ID);

                $fundAccountContactId = $this->repo->fund_account->dbColumn(FundAccount\Entity::CONTACT_ID);

                $join->on($fundAccountContactId, '=', $contactId);

                $join->where($fundAccountId, '=', $fundAccountIDParam);
            }
       );

      $query->select($query->getModel()->getTable().'.*');
    }
}
