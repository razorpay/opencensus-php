<?php


namespace RZP\Models\BankingAccount\Activation\Comment;


use RZP\Models\BankingAccount\Activation\Detail as ActivationDetail;
use RZP\Models\Base;
use Illuminate\Database\Query\JoinClause;

class Repository extends Base\Repository
{
    protected $entity = 'banking_account_comment';

    public function addQueryOrder($query)
    {
        $query->orderBy($this->dbColumn(Entity::ADDED_AT), 'desc');
    }

    public function fetchExternalComments()
    {
        $baActivationDetailsTable = $this->repo->banking_account_activation_detail->getTableName();

        $baCommentsBaId = $this->repo->banking_account_comment->dbColumn(Entity::BANKING_ACCOUNT_ID);

        $baCommentsType = $this->repo->banking_account_comment->dbColumn(Entity::TYPE);

        $baCommentsCreatedAt = $this->repo->banking_account_comment->dbColumn(Entity::CREATED_AT);

        $baActivationDetailsBaId = $this->repo->banking_account_activation_detail->dbColumn(ActivationDetail\Entity::BANKING_ACCOUNT_ID);

        return $this->newQuery()
                    ->select($this->getTableName() . '.*')
                    ->join($baActivationDetailsTable, $baCommentsBaId, '=', $baActivationDetailsBaId)
                    ->where($baCommentsType, '=', 'external')
                    ->orderBy($baCommentsCreatedAt, 'asc')
                    ->get();
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
}
