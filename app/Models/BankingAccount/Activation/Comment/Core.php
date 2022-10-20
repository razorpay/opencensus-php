<?php


namespace RZP\Models\BankingAccount\Activation\Comment;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\BankingAccount as BA;
use RZP\Models\Base\PublicEntity;
use RZP\Exception\LogicException;

class Core extends Base\Core
{
    public function create(BA\Entity $bankingAccount, PublicEntity $maker, array $input): Entity
    {
        $makerEntityName = $maker->getEntity();

        if ($makerEntityName === Entity::ADMIN)
        {
            $input[Entity::ADMIN_ID] = $maker->getId();
        }
        else if ($makerEntityName === Entity::USER)
        {
            $input[Entity::USER_ID] = $maker->getId();
            $input[Entity::ADMIN_ID] = ""; // to address db constraint
        }
        else
        {
            $this->trace->error(
                TraceCode::BANKING_ACCOUNT_COMMENT_CREATE_ERROR,
                [
                    'banking_account_id'    => $bankingAccount->getId(),
                    'input' => $input,
                ]);

            throw new LogicException(
                'Maker must either be an admin or a user',
                null,
                ['banking_account_id' => $bankingAccount->getId()]);
        }

        $input[Entity::BANKING_ACCOUNT_ID] = $bankingAccount->getId();

        $this->trace->info(TraceCode::BANKING_ACCOUNT_COMMENT_CREATE,
            [
                'input' => $input,
            ]);

        $newCommentEntity = new Entity;

        $newComment = $newCommentEntity->build($input);

        $newComment->$makerEntityName()->associate($maker);

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
