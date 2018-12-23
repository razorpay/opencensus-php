<?php

namespace RZP\Models\Payout;

use Illuminate\Database\Query\JoinClause;

use RZP\Base\BuilderEx;
use RZP\Models\Base;
use RZP\Constants;
use RZP\Exception;
use RZP\Models\FundAccount;
use RZP\Models\Payout;
use RZP\Models\Contact;

class Repository extends Base\Repository
{
    protected $entity = 'payout';

    // These are admin allowed params to search on.
    protected $appFetchParamRules = [
        Entity::MERCHANT_ID        => 'sometimes|alpha_num',
        Entity::CUSTOMER_ID        => 'sometimes|string|max:19',
        Entity::FUND_ACCOUNT_ID    => 'sometimes|string|max:17',
        Entity::DESTINATION        => 'sometimes|string|max:20',
        Entity::METHOD             => 'sometimes|string',
    ];

    public function fetchCreatedPayouts($timestamp, $method)
    {
        return $this->newQuery()
                    ->with('destination')
                    ->where(Entity::CREATED_AT, '<', $timestamp)
                    ->where(Entity::STATUS, '=', Status::CREATED)
                    ->where(Entity::METHOD, '=', $method)
                    ->orderBy(Entity::ID)
                    ->get();
    }

    public function fetchPayoutsWithUtrNotNull($from, $to, $merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::CREATED_AT, '>', $from)
                    ->where(Entity::CREATED_AT, '<', $to)
                    ->whereNotNull(Entity::UTR)
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->get();
    }

    public function updateStatus(Base\PublicCollection $payouts, string $status)
    {
        if ($payouts->count() === 0)
        {
            return 0;
        }

        $IdsToUpdate = $payouts->getIds();

        $updatedCount = $this->newQuery()
                             ->whereIn(Entity::ID, $IdsToUpdate)
                             ->update([
                                    Entity::STATUS  => $status
                                ]);

        $expectedCount = count($IdsToUpdate);

        if ($updatedCount !== $expectedCount)
        {
            throw new Exception\LogicException(
                'Failed to update expected number of payout records.',
                null,
                [
                    'expected' => $expectedCount,
                    'updated'  => $updatedCount,
                ]);
        }

        return $updatedCount;
    }

    public function addQueryParamDestination($query, $params)
    {
        $destinationId = $params[Entity::DESTINATION];

        Entity::stripSignWithoutValidation($destinationId);

        $query->where(Entity::DESTINATION_ID, $destinationId);
    }

    public function fetchReversedPayouts(array $ids)
    {
        return $this->newQuery()
                    ->with(['destination', 'fundAccount.account'])
                    ->whereIn(Entity::ID, $ids)
                    ->where(Entity::STATUS, Status::REVERSED)
                    ->get();
    }

    /**
    * select
    *   `payouts`.*
    * from
    *   `payouts`
    *   inner join `fund_accounts` on `fund_accounts`.`id` = `payouts`.`fund_account_id`
    * where
    *   `payouts`.`merchant_id` = '10000000000000'
    *   and `fund_accounts`.`source_id` = '1000010contact'
    *   and `fund_accounts`.`source_type` = 'contact'
    * order by
    *   `created_at` desc,
    *   `id` desc
    * limit
    *   10
    */
    protected function addQueryParamContactId(BuilderEx $query, array $params)
    {
        $contactId = $params[Entity::CONTACT_ID];

        $contactIdColumn = $this->repo->fund_account->dbColumn(FundAccount\Entity::SOURCE_ID);

        $sourceTypeColumn = $this->repo->fund_account->dbColumn(FundAccount\Entity::SOURCE_TYPE);

        $this->joinQueryFundAccount($query);

        $query->select($this->getTableName() . '.*');
        $query->where($contactIdColumn, $contactId);
        $query->where($sourceTypeColumn, Constants\Entity::CONTACT);

    }

    /**
    * select
    *   `payouts`.*
    * from
    *   `payouts`
    *   inner join `fund_accounts` on `fund_accounts`.`id` = `payouts`.`fund_account_id`
    *   inner join `contacts` on `contacts`.`id` = `fund_accounts`.`source_id`
    * where
    *   `payouts`.`merchant_id` = '10000000000000'
    *   and `fund_accounts`.`source_type` = 'contact'
    *   and `contacts`.`name` = 'test user'
    * order by
    *   `created_at` desc,
    *   `id` desc
    * limit
    *   10
    */
    protected function addQueryParamContactName(BuilderEx $query, array $params)
    {
        $contactName = $params[Entity::CONTACT_NAME];

        $contactNameColumn = $this->repo->contact->dbColumn(Contact\Entity::NAME);

        $this->joinQueryFundAccount($query);

        $this->joinQueryContact($query);

        $query->select($this->getTableName() . '.*');
        $query->where($contactNameColumn, $contactName);
    }

    /**
    * select
    *   `payouts`.*
    * from
    *   `payouts`
    *   inner join `fund_accounts` on `fund_accounts`.`id` = `payouts`.`fund_account_id`
    *   inner join `contacts` on `contacts`.`id` = `fund_accounts`.`source_id`
    * where
    *   `payouts`.`merchant_id` = '10000000000000'
    *   and `fund_accounts`.`source_type` = 'contact'
    *   and `contacts`.`contact` = '8888888888'
    * order by
    *   `created_at` desc,
    *   `id` desc
    * limit
    *   10
    */
    protected function addQueryParamContactPhone(BuilderEx $query, array $params)
    {
        $contactPhone = $params[Entity::CONTACT_PHONE];

        $contactPhoneColumn = $this->repo->contact->dbColumn(Contact\Entity::CONTACT);

        $this->joinQueryFundAccount($query);

        $this->joinQueryContact($query);

        $query->select($this->getTableName() . '.*');
        $query->where($contactPhoneColumn, $contactPhone);
    }

    /**
    * select
    *   `payouts`.*
    * from
    *   `payouts`
    *   inner join `fund_accounts` on `fund_accounts`.`id` = `payouts`.`fund_account_id`
    *   inner join `contacts` on `contacts`.`id` = `fund_accounts`.`source_id`
    * where
    *   `payouts`.`merchant_id` = '10000000000000'
    *   and `fund_accounts`.`source_type` = 'contact'
    *   and `contacts`.`email` = 'test@payout.com'
    * order by
    *   `created_at` desc,
    *   `id` desc
    * limit
    *   10
    */
    protected function addQueryParamContactEmail(BuilderEx $query, array $params)
    {
        $contactEmail = $params[Entity::CONTACT_EMAIL];

        $contactEmailColumn = $this->repo->contact->dbColumn(Contact\Entity::EMAIL);

        $this->joinQueryFundAccount($query);

        $this->joinQueryContact($query);

        $query->select($this->getTableName() . '.*');
        $query->where($contactEmailColumn, $contactEmail);
    }

    /*
    * select
    *   *
    * from
    *   `payouts`
    * where
    *   `payouts`.`merchant_id` = '10000000000000'
    *   and `payouts`.`fund_account_id` = '100000000000fa'
    * order by
    *   `created_at` desc,
    *   `id` desc
    * limit
    *   10
    */
    protected function addQueryParamFundAccountId(BuilderEx $query, array $params)
    {
        $faId = $params[Entity::FUND_ACCOUNT_ID];

        $faColumn = $this->repo->payout->dbColumn(Contact\Entity::FUND_ACCOUNT_ID);

        Entity::stripSignWithoutValidation($faId);

        $query->where($faColumn, $faId);
    }

    protected function joinQueryFundAccount(BuilderEx $query)
    {
        $faTable = $this->repo->fund_account->getTableName();

        if ($query->hasJoin($faTable) === true)
        {
            return;
        }

        $query->join(
            $faTable,
            function (JoinClause $join)
            {
                $faIdColumn = $this->repo->fund_account->dbColumn(FundAccount\Entity::ID);

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

        $query->join(
            $contactTable,
            function (JoinClause $join)
            {
                $contactIdColumn = $this->repo->contact->dbColumn(Contact\Entity::ID);
                $facontactIdColumn = $this->repo->fund_account->dbColumn(FundAccount\Entity::SOURCE_ID);

                $join->on($contactIdColumn, $facontactIdColumn);

            });

        $sourceTypeColumn = $this->repo->fund_account->dbColumn(FundAccount\Entity::SOURCE_TYPE);

        $query->where($sourceTypeColumn, Constants\Entity::CONTACT);
    }

}
