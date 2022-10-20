<?php


namespace RZP\Models\BankingAccount\Activation\Comment;

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

    public function fetchLatestComment(string $bankingAccountId, string $commentType = null)
    {
        return $this->buildQueryToFetchExternalComment($bankingAccountId, $commentType,'desc')->first();
    }

    protected function buildQueryToFetchExternalComment(string $bankingAccountId = null, string $commentType = null, string $sortOrder = 'desc')
    {
        $baCommentsType = $this->repo->banking_account_comment->dbColumn(Entity::TYPE);
        $baCommentsAddedAt = $this->repo->banking_account_comment->dbColumn(Entity::ADDED_AT);

        $query = $this->newQuery()
            ->select($this->getTableName() . '.*');

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
}
