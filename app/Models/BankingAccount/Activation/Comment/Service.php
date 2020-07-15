<?php


namespace RZP\Models\BankingAccount\Activation\Comment;

use RZP\Mail\System\Trace;
use RZP\Models\BankingAccount;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    use Base\Traits\ServiceHasCrudMethods;

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

        $newComment = (new Core)->create($bankingAccount, $input);

        return $newComment->toArrayPublic();
    }
}
