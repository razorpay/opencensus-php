<?php

namespace RZP\Models\Transaction\Statement;

use Illuminate\Database\Query\JoinClause;

use RZP\Models\Payout;
use RZP\Models\Contact;
use RZP\Base\BuilderEx;
use RZP\Models\Merchant;
use RZP\Models\Transaction;
use RZP\Models\FundAccount;
use RZP\Constants\Entity as E;
use RZP\Models\Base\PublicCollection;

/**
 * Class Repository
 *
 * @package RZP\Models\Transaction\Statement
 */
class Repository extends Transaction\Repository
{
    /**
     * {@inheritDoc}
     */
    protected $entity = 'statement';

    /**
     * {@inheritDoc}
     */
    protected $expands = [
        Entity::SOURCE,
        Entity::ACCOUNT_BALANCE,
    ];

    /**
     * {@inheritDoc}
     */
    public function findByPublicIdAndMerchantForBankingBalance(
        string $id,
        Merchant\Entity $merchant,
        array $params = []): Entity
    {
        Entity::verifyIdAndStripSign($id);

        return $this->getQueryForFindWithParams($params)
                    ->merchantId($merchant->getId())
                    ->where(Entity::BALANCE_ID, $merchant->bankingBalance->getId())
                    ->findOrFailPublic($id);
    }

    /**
     * {@inheritDoc}
     */
    public function fetch(array $input, string $merchantId = null): PublicCollection
    {
        $statements = parent::fetch($input, $merchantId);

        // Todo: update these after 'payout-on-fa' branch is merged.
        // After fetching settlement collection, we lazy load source relations for payout.
        // $statements->where(Entity::TYPE, E::PAYOUT)
        //            ->load(
        //                 [
        //                     'source.customer',
        //                     'source.destination',
        //                 ]);

        return $statements;
    }

    /**
     * SELECT transactions.*
     * FROM   transactions
     *        INNER JOIN payouts
     *                ON payouts.id = transactions.entity_id
     *                   AND transactions.type = 'payout'
     *        INNER JOIN fund_accounts
     *                ON fund_accounts.id = payouts.fund_account_id
     * WHERE  transactions.merchant_id = '10000000000000'
     *        AND fund_accounts.source_id = 'BXV5GAmaJEcGr1'
     *        AND fund_accounts.source_type = 'contact'
     *        AND transactions.balance_id = 'xbalance000000'
     * ORDER  BY created_at DESC,
     *           id DESC
     * LIMIT  10
     *
     * @param BuilderEx $query
     * @param array     $params
    */
    protected function addQueryParamContactId(BuilderEx $query, array $params)
    {
        $contactId        = $params[Entity::CONTACT_ID];
        $sourceIdColumn   = $this->repo->fund_account->dbColumn(FundAccount\Entity::SOURCE_ID);
        $sourceTypeColumn = $this->repo->fund_account->dbColumn(FundAccount\Entity::SOURCE_TYPE);

        $query->select($this->getTableName() . '.*');
        $this->joinQueryFundAccount($query);

        $query->where($sourceIdColumn, $contactId);
        $query->where($sourceTypeColumn, E::CONTACT);
    }

    protected function addQueryParamPayoutId(BuilderEx $query, array $params)
    {
        $payoutId                  = $params[Entity::PAYOUT_ID];
        $transactionTypeColumn     = $this->dbColumn(Entity::TYPE);
        $transactionEntityIdColumn = $this->dbColumn(Entity::ENTITY_ID);

        $query->where($transactionEntityIdColumn, $payoutId);
        $query->where($transactionTypeColumn, E::PAYOUT);
    }

    /**
     * SELECT transactions.*
     * FROM   transactions
     *        INNER JOIN payouts
     *                ON payouts.id = transactions.entity_id
     *                   AND transactions.type = 'payout'
     *        INNER JOIN fund_accounts
     *                ON fund_accounts.id = payouts.fund_account_id
     *        INNER JOIN contacts
     *                ON contacts.id = fund_accounts.source_id
     *                   AND fund_accounts.source_type = 'contact'
     * WHERE  transactions.merchant_id = '10000000000000'
     *        AND contacts.name = 'jitendra kumar ojha'
     *        AND transactions.balance_id = 'xbalance000000'
     * ORDER  BY created_at DESC,
     *           id DESC
     * LIMIT  10
     *
     * @param BuilderEx $query
     * @param array     $params
     */
    protected function addQueryParamContactName(BuilderEx $query, array $params)
    {
        $contactName       = $params[Entity::CONTACT_NAME];
        $contactNameColumn = $this->repo->contact->dbColumn(Contact\Entity::NAME);

        $query->select($this->getTableName() . '.*');
        $this->joinQueryContact($query);

        $query->where($contactNameColumn, $contactName);
    }

    /**
     * SELECT transactions.*
     * FROM   transactions
     *        INNER JOIN payouts
     *                ON payouts.id = transactions.entity_id
     *                   AND transactions.type = 'payout'
     *        INNER JOIN fund_accounts
     *                ON fund_accounts.id = payouts.fund_account_id
     *        INNER JOIN contacts
     *                ON contacts.id = fund_accounts.source_id
     *                   AND fund_accounts.source_type = 'contact'
     * WHERE  transactions.merchant_id = '10000000000000'
     *        AND contacts.email = 'test@razorpay.com'
     *        AND transactions.balance_id = 'xbalance000000'
     * ORDER  BY created_at DESC,
     *           id DESC
     * LIMIT  10
     *
     * @param BuilderEx $query
     * @param array     $params
     */
    protected function addQueryParamContactEmail(BuilderEx $query, array $params)
    {
        $contactEmail       = $params[Entity::CONTACT_EMAIL];
        $contactEmailColumn = $this->repo->contact->dbColumn(Contact\Entity::EMAIL);

        $query->select($this->getTableName() . '.*');
        $this->joinQueryContact($query);

        $query->where($contactEmailColumn, $contactEmail);
    }

    /**
     * SELECT transactions.*
     * FROM   transactions
     *        INNER JOIN payouts
     *                ON payouts.id = transactions.entity_id
     *                   AND transactions.type = 'payout'
     *        INNER JOIN fund_accounts
     *                ON fund_accounts.id = payouts.fund_account_id
     *        INNER JOIN contacts
     *                ON contacts.id = fund_accounts.source_id
     *                   AND fund_accounts.source_type = 'contact'
     * WHERE  transactions.merchant_id = '10000000000000'
     *        AND contacts.contact = '9931864792'
     *        AND transactions.balance_id = 'xbalance000000'
     * ORDER  BY created_at DESC,
     *           id DESC
     * LIMIT  10
     *
     * @param BuilderEx $query
     * @param array     $params
     */
    protected function addQueryParamContactPhone(BuilderEx $query, array $params)
    {
        $contactPhone       = $params[Entity::CONTACT_PHONE];
        $contactPhoneColumn = $this->repo->contact->dbColumn(Contact\Entity::CONTACT);

        $query->select($this->getTableName() . '.*');
        $this->joinQueryContact($query);

        $query->where($contactPhoneColumn, $contactPhone);
    }

    /**
     * SELECT transactions.*
     * FROM   transactions
     *        INNER JOIN payouts
     *                ON payouts.id = transactions.entity_id
     *                   AND transactions.type = 'payout'
     * WHERE  transactions.merchant_id = '10000000000000'
     *        AND payouts.fund_account_id = 'BXV5GAmaJEcGr1'
     *        AND transactions.balance_id = 'xbalance000000'
     * ORDER  BY created_at DESC,
     *           id DESC
     * LIMIT  10
     *
     * @param BuilderEx $query
     * @param array     $params
     */
    protected function addQueryParamFundAccountId(BuilderEx $query, array $params)
    {
        $faId              = $params[Entity::FUND_ACCOUNT_ID];
        $fundAccountColumn = $this->repo->payout->dbColumn(Payout\Entity::FUND_ACCOUNT_ID);

        $query->select($this->getTableName() . '.*');
        $this->joinQueryPayout($query);

        $query->where($fundAccountColumn, $faId);
    }

    protected function joinQueryPayout(BuilderEx $query)
    {
        $payoutTable = $this->repo->payout->getTableName();

        if ($query->hasJoin($payoutTable) === true)
        {
            return;
        }

        $query->join(
            $payoutTable,
            function(JoinClause $join)
            {
                $payoutIdColumn            = $this->repo->payout->dbColumn(Payout\Entity::ID);
                $transactionEntityIdColumn = $this->dbColumn(Entity::ENTITY_ID);
                $transactionTypeColumn     = $this->dbColumn(Entity::TYPE);

                $join->on($payoutIdColumn, $transactionEntityIdColumn);
                $join->where($transactionTypeColumn, Transaction\Type::PAYOUT);
            });
    }

    protected function joinQueryFundAccount(BuilderEx $query)
    {
        $faTable = $this->repo->fund_account->getTableName();

        if ($query->hasJoin($faTable) === true)
        {
            return;
        }

        // Must join payout for joining fund_account.
        $this->joinQueryPayout($query);

        $query->join(
            $faTable,
            function(JoinClause $join)
            {
                $faIdColumn       = $this->repo->fund_account->dbColumn(FundAccount\Entity::ID);
                $payoutFaIdColumn = $this->repo->payout->dbColumn(Payout\Entity::FUND_ACCOUNT_ID);

                $join->on($faIdColumn, $payoutFaIdColumn);
            });
    }

    protected function joinQueryContact(BuilderEx $query)
    {
         $contactTable = $this->repo->contact->getTableName();

         if ($query->hasJoin($contactTable) === true)
         {
             return;
         }

         // Must join fund_account for joining contact
         $this->joinQueryFundAccount($query);

         $query->join(
             $contactTable,
             function (JoinClause $join)
             {
                 $contactIdColumn    = $this->repo->contact->dbColumn(Contact\Entity::ID);
                 $faSourceIdColumn   = $this->repo->fund_account->dbColumn(FundAccount\Entity::SOURCE_ID);
                 $faSourceTypeColumn = $this->repo->fund_account->dbColumn(FundAccount\Entity::SOURCE_TYPE);

                 $join->on($contactIdColumn, $faSourceIdColumn);
                 $join->where($faSourceTypeColumn, E::CONTACT);
             });
    }
}
