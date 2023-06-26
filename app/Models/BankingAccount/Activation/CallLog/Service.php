<?php


namespace RZP\Models\BankingAccount\Activation\CallLog;

use RZP\Error\ErrorCode;
use RZP\Mail\System\Trace;
use RZP\Models\BankingAccount;
use RZP\Models\BankingAccountService\BasDtoAdapter;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Exception;

class Service extends Base\Service
{
    /**
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\InvalidArgumentException
     */
    public function fetchMultiple(string $bankingAccountId, array $input): array
    {
        $bankingAccountService = new BankingAccount\Service();

        [$existsInApi, $bankingAccount] = $bankingAccountService->checkAndGetBankingAccountId($bankingAccountId);

        if ($existsInApi === false)
        {
            return (new BasDtoAdapter())->arrayAsPublicCollection([]); // Call logs are deprecated for RBL on BAS
        }

        $input[Entity::BANKING_ACCOUNT_ID] = $bankingAccount->getId();

        $callLogs = $this->repo->banking_account_call_log->fetch($input);

        return $callLogs->toArrayPublicWithExpand();
    }

    /**
     * @param string      $bankingAccountId
     * @param array       $input
     * @param string      $stateLogId
     * @param string|null $comment_id
     *
     * @return array
     */
    public function createForBankingAccount(string $bankingAccountId, array $input, string $stateLogId, string $comment_id = null): array
    {
        /** @var BankingAccount\Entity $bankingAccount */
        $bankingAccount = $this->repo->banking_account->findByPublicId($bankingAccountId);

        $stateLog = $this->repo->banking_account_state->findByPublicId($stateLogId);

        $comment = null;

        if (empty($comment_id) === false)
        {
            $comment = $this->repo->banking_account_comment->findByPublicId($comment_id);
        }

        $admin = $this->app['basicauth']->getAdmin();

        // Note: A lot of code flows use Core create instead of this service method for comments
        // because of requiring admin entity. In Batch service admin_id is sent via request body,
        // and not set in middleware.
        // Make any common changes in core method rather than here.
        $newComment = (new Core)->create($bankingAccount, $admin, $stateLog, $input, $comment);

        return $newComment->toArrayPublic();
    }

    /**
     * @param string $id
     * @param array  $input
     *
     * @return array
     */
    public function update(string $id, array $input): array
    {
        $callLog = $this->repo->banking_account_call_log->findOrFail($id);

        $callLog = (new Core)->update($callLog, $input);

        return $callLog->toArrayPublic();
    }
}
