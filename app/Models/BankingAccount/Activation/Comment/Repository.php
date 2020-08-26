<?php


namespace RZP\Models\BankingAccount\Activation\Comment;

use Illuminate\Support\Facades\DB;
use RZP\Constants\Table;
use RZP\Mail\Merchant\Activation;
use RZP\Models\BankingAccount\Activation\Detail as ActivationDetail;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'banking_account_comment';

    public function addQueryOrder($query)
    {
        $query->orderBy($this->dbColumn(Entity::ADDED_AT), 'desc');
    }

    public function fetchExternalCommentsAssignedToTeam(string $team = 'bank')
    {
        $baActivationDetailsTable = $this->repo->banking_account_activation_detail->getTableName();

        $baCommentsBaId = $this->repo->banking_account_comment->dbColumn(Entity::BANKING_ACCOUNT_ID);

        $baCommentsType = $this->repo->banking_account_comment->dbColumn(Entity::TYPE);

        $baCommentsCreatedAt = $this->repo->banking_account_comment->dbColumn(Entity::CREATED_AT);

        $baActivationDetailsBaId = $this->repo->banking_account_activation_detail->dbColumn(ActivationDetail\Entity::BANKING_ACCOUNT_ID);

        $baActivationDetailsAssigneeTeam = $this->repo->banking_account_activation_detail->dbColumn(ActivationDetail\Entity::ASSIGNEE_TEAM);

        return $this->newQuery()
                    ->select($this->getTableName() . '.*')
                    ->join($baActivationDetailsTable, $baCommentsBaId, '=', $baActivationDetailsBaId)
                    ->where($baActivationDetailsAssigneeTeam, '=', $team)
                    ->where($baCommentsType, '=', 'external')
                    ->orderBy($baCommentsCreatedAt, 'asc')
                    ->get();

    }
}
