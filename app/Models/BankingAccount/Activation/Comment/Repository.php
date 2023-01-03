<?php


namespace RZP\Models\BankingAccount\Activation\Comment;

use Closure;
use Illuminate\Support\Facades\DB;
use RZP\Base;
use RZP\Models\Base as BaseModels;

class Repository extends BaseModels\Repository
{
    protected $entity = 'banking_account_comment';

    public function addQueryOrder($query)
    {
        $query->orderBy($this->dbColumn(Entity::ADDED_AT), 'desc');
    }

    public function fetchExternalComments()
    {
        return $this->buildQueryToFetchExternalComment(null,'external','desc')->get();
    }

    public function fetchComments(string $bankingAccountId, string $commentType = null)
    {
        return $this->buildQueryToFetchExternalComment($bankingAccountId, $commentType,'desc')->get();
    }

    public function fetchLatestComment(string $bankingAccountId, string $commentType = null, string $sourceTeam = null)
    {
        return $this->buildQueryToFetchExternalComment($bankingAccountId, $commentType, 'desc', $sourceTeam)->first();
    }

    protected function buildQueryToFetchExternalComment(string $bankingAccountId = null, string $commentType = null, string $sortOrder = 'desc', string $sourceTeam = null)
    {
        $baCommentsType = $this->repo->banking_account_comment->dbColumn(Entity::TYPE);
        $baCommentsAddedAt = $this->repo->banking_account_comment->dbColumn(Entity::ADDED_AT);

        $query = $this->newQuery()
            ->select($this->getTableName() . '.*');

        if ($sourceTeam !== null)
        {
            $baSourceTeam = $this->repo->banking_account_comment->dbColumn(Entity::SOURCE_TEAM);
            $query->where($baSourceTeam, $sourceTeam);
        }

        if ($commentType === 'external')
        {
            $query->whereIn($baCommentsType, array('external', 'external_resolved'));
        }

        if ($commentType === 'internal')
        {
            $query->where($baCommentsType, '=', 'internal');
        }

        if ($bankingAccountId !== null)
        {
            $query->where(Entity::BANKING_ACCOUNT_ID, '=', $bankingAccountId);
        }
        // Since comments can be added with different added date, sortiny comments by Added at date
        return $query->orderBy($baCommentsAddedAt, $sortOrder);
    }

    public function fetchCommentsMadeBetweenForSpoc(int $fromTs, int $toTs)
    {
        $baCommentsCreatedAt = $this->repo->banking_account_comment->dbColumn(Entity::CREATED_AT);

        $data = $this->newQuery()
                    ->select($this->getTableName() . '.*')
                    ->with(['bankingAccount', 'bankingAccount.merchant.merchantDetail', 'bankingAccount.spocs'])
                    ->where($baCommentsCreatedAt, '>', $fromTs)
                    ->where($baCommentsCreatedAt, '<', $toTs)
                    ->get();

        $spocGroupedData = $data->groupBy(
            function ($item, $key)
            {
                return $item->bankingAccount->spocs()->first()['email'] ?? null;
            }
        );

        return $spocGroupedData;
    }

    /**
     * Filter to fetch all internal comments for a source team (eg, external for bank)
     * and comments shared with that source team by other team (eg, comments added by RZP to share with bank)
     *
     * SQL:
     *
     * selet * from banking_account_comments
     * where banking_account_id = 'bankingAccountId' AND
     * (source_team_type = 'external' OR (source_team_type = 'internal' AND 'type' = 'external' ))
     *
     *
     */
    public function addQueryParamForSourceTeamType(Base\BuilderEx $query, $params)
    {
        $commentSourceTeamTypeCol = $this->repo->banking_account_comment->dbColumn(Entity::SOURCE_TEAM_TYPE);
        $commentTypeCol = $this->repo->banking_account_comment->dbColumn(Entity::TYPE);

        $sourceTeamType = $params[Fetch::FOR_SOURCE_TEAM_TYPE];

        $otherSourceTeamType = 'external';

        if ($sourceTeamType === 'external')
        {
            $otherSourceTeamType = 'internal';
        }

        $query->where(
            function ($query) use ($commentSourceTeamTypeCol, $sourceTeamType, $otherSourceTeamType, $commentTypeCol) {
                $query->where($commentSourceTeamTypeCol, $sourceTeamType)
                ->orWhereRaw('( '.$commentSourceTeamTypeCol.' = \''.$otherSourceTeamType.'\' AND '.$commentTypeCol.' IN (\'external\', \'external_resolved\') )');
            }
        );
    }

    /**
     * Given an array for bankingAccountIds  
     * Aggregate using created_at in a specific order grouping by banking_account_id  
     * Join with the same table with Subquery to filter a specific comment
     * 
     * @param $bankingAccountIds
     * @param $order
     * @param $attributes
     * @param $modifyQuery
     */
    public function getCommentForMultipleBankingAccounts($bankingAccountIds, string $order = 'last', $attributes = [], Closure $modifyQuery = null)
    {
        $aggregationFunc = 'MAX';

        if ($order === 'first') {
            $aggregationFunc = 'MIN';
        }

        $subquery = $this->newQuery()
            ->select(DB::raw(Entity::BANKING_ACCOUNT_ID.' as baid, '.$aggregationFunc.'(created_at) as SubQueryDate'))
            ->whereIn(Entity::BANKING_ACCOUNT_ID, $bankingAccountIds);

        foreach ($attributes as $key => $value)
        {
            $subquery->where($key, '=', $value);
        }

        $subquery->groupBy(Entity::BANKING_ACCOUNT_ID);

        $query = $this->newQuery()
                ->joinSub($subquery, 'SubQuery', function ($join) {
                    $join
                        ->on($this->getTableName().'.'.Entity::BANKING_ACCOUNT_ID, '=', 'SubQuery.baid')
                        ->on($this->getTableName().'.'.Entity::CREATED_AT, '=', 'SubQuery.SubQueryDate');
                })
                ->whereIn(Entity::BANKING_ACCOUNT_ID, $bankingAccountIds);

        if ($modifyQuery != null && $modifyQuery instanceof Closure)
        {
            $modifyQuery($query);
        }
    
        return $query->get();
    }

}
