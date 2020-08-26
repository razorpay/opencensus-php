<?php


namespace RZP\Models\BankingAccount\Activation\Comment;

use RZP\Error\ErrorCode;
use RZP\Mail\System\Trace;
use RZP\Models\BankingAccount;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Exception;

class Service extends Base\Service
{
    public function fetchMultiple(string $bankingAccountId, array $input): array
    {
        /** @var BankingAccount\Entity $bankingAccount */
        $bankingAccount = $this->repo->banking_account->findByPublicId($bankingAccountId);

        $input[Entity::BANKING_ACCOUNT_ID] = $bankingAccount->getId();

        $comments = $this->repo->banking_account_comment->fetch($input);

        return $comments->toArrayPublicWithExpand();
    }

    public function createForBankingAccount(string $bankingAccountId, array $input)
    {
        /** @var BankingAccount\Entity $bankingAccount */
        $bankingAccount = $this->repo->banking_account->findByPublicId($bankingAccountId);

        $admin = $this->app['basicauth']->getAdmin();

        // Note: A lot of code flows use Core create instead of this service method for comments
        // because of requiring admin entity. In Batch service admin_id is sent via request body,
        // and not set in middleware.
        // Make any common changes in core method rather than here.
        $newComment = (new Core)->create($bankingAccount, $admin, $input);

        return $newComment->toArrayPublic();
    }

    public function update(string $id, array $input): array
    {
        $comment = $this->repo->banking_account_comment->findOrFail($id);

        $comment = (new Core)->update($comment, $input);

        return $comment->toArrayPublic();
    }
}
