<?php


namespace RZP\Models\BankingAccount\Activation\Comment;


use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\BankingAccount as BA;
use RZP\Models\Admin\Admin;

class Core extends Base\Core
{
    public function create(BA\Entity $bankingAccount, Admin\Entity $admin, array $input): Entity
    {
        $input[Entity::ADMIN_ID] = $admin->getId();

        $input[Entity::BANKING_ACCOUNT_ID] = $bankingAccount->getId();

        $this->trace->info(TraceCode::BANKING_ACCOUNT_COMMENT_CREATE,
            [
                'input' => $input,
            ]);

        $newCommentEntity = new Entity;

        $newComment = $newCommentEntity->build($input);

        $newComment->admin()->associate($admin);

        $newComment->bankingAccount()->associate($bankingAccount);

        $this->repo->saveOrFail($newComment);

        return $newComment;
    }

    public function update(Entity $comment, array $input)
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_COMMENT_UPDATE,
            [
                'banking_account_id'    => $comment->bankingAccount->getId(),
                'input' => $input,
            ]);

        $validator = new Validator;

        $validator->validateInput('edit', $input);

        $comment->edit($input);

        $this->repo->saveOrFail($comment);

        return $comment;
    }
}
