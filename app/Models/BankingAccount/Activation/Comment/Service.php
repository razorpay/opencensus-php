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

    public function createFromBatchService(array $input)
    {
        $this->trace->info(TraceCode::BANKING_ACCOUNT_BATCH_COMMENT_CREATE,
            [
                'input' => $input
            ]);

        try
        {
            $bankingAccount = $this->repo->banking_account->findByBankReferenceAndChannel(
                $input[BankingAccount\Entity::CHANNEL],
                $input[BankingAccount\Entity::BANK_REFERENCE_NUMBER]);
        }
        catch (\Throwable $e)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_ID, null,
                [
                    BankingAccount\Entity::BANK_REFERENCE_NUMBER => $input[BankingAccount\Entity::BANK_REFERENCE_NUMBER],
                    BankingAccount\Entity::CHANNEL => $input[BankingAccount\Entity::CHANNEL]
                ]);
        }

        unset($input[BankingAccount\Entity::CHANNEL]);

        unset($input[BankingAccount\Entity::BANK_REFERENCE_NUMBER]);

        $newComment = (new Core)->create($bankingAccount, $input);

        return $newComment->toArrayPublic();
    }
}
