<?php


namespace RZP\Models\BankingAccount\Activation\Comment;


use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\BankingAccount as BA;

class Core extends Base\Core
{
    public function create(BA\Entity $bankingAccount, array $input): Entity
    {
        // TODO: handle this in middleware
        if (isset($input[Entity::ADMIN_ID]) === true)
        {
            $admin = $this->repo->admin->findorFail($input[Entity::ADMIN_ID]);
        }
        else
        {
            $admin = $this->app['basicauth']->getAdmin();

            $input[Entity::ADMIN_ID] = $admin->getId();
        }

        $input[Entity::BANKING_ACCOUNT_ID] = $bankingAccount->getId();

        $this->trace->info(TraceCode::BANKING_ACCOUNT_COMMENT_CREATE,
            [
                'input' => $input,
            ]);

        $newCommentEntity = new Entity;

        $newComment = $newCommentEntity->build($input);

        $newComment->admin()->associate($admin);

        $newComment->bankingAccount()->associate($bankingAccount);

        $newComment->saveOrFail();

        return $newComment;
    }
}
