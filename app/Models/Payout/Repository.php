<?php

namespace RZP\Models\Payout;

use Illuminate\Database\Query\JoinClause;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Models\Contact;
use RZP\Base\BuilderEx;
use RZP\Models\FundAccount;
use RZP\Constants\Entity as E;

class Repository extends Base\Repository
{
    protected $entity = 'payout';

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

    protected function addQueryParamId($query, $params)
    {
        $id = $params[Entity::ID];

        Entity::stripSignOrFail($id);

        $query->where(Entity::ID, $id);
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
     * SELECT payouts.*
     * FROM   payouts
     *        INNER JOIN fund_accounts
     *                ON fund_accounts.id = payouts.fund_account_id
     * WHERE  payouts.merchant_id = '10000000000000'
     *        AND fund_accounts.source_id = 'BXV5GAmaJEcGr1'
     *        AND fund_accounts.source_type = 'contact'
     * ORDER  BY created_at DESC,
     *           id DESC
     * LIMIT  10
     *
     * @param BuilderEx $query
     * @param array     $params
     */
    protected function addQueryParamContactId(BuilderEx $query, array $params)
    {
        $contactId          = $params[Entity::CONTACT_ID];
        $faSourceIdColumn   = $this->repo->fund_account->dbColumn(FundAccount\Entity::SOURCE_ID);
        $faSourceTypeColumn = $this->repo->fund_account->dbColumn(FundAccount\Entity::SOURCE_TYPE);

        $query->select($this->getTableName() . '.*');
        $this->joinQueryFundAccount($query);

        $query->where($faSourceIdColumn, $contactId);
        $query->where($faSourceTypeColumn, E::CONTACT);
    }

    /**
     * Refer: addQueryParamContactId()
     *
     * @param BuilderEx $query
     * @param array     $params
     */
    protected function addQueryParamContactType(BuilderEx $query, array $params)
    {
        $contactType       = $params[Entity::CONTACT_TYPE];
        $contactTypeColumn = $this->repo->contact->dbColumn(Contact\Entity::TYPE);

        $query->select($this->getTableName() . '.*');
        $this->joinQueryContact($query);

        $query->where($contactTypeColumn, $contactType);
    }


    /**
     * Refer: addQueryParamContactId()
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
     * Refer: addQueryParamContactId()
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
     * Refer: addQueryParamContactId()
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

        // Must join fund_account to join contact
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
