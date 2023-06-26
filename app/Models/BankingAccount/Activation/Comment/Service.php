<?php


namespace RZP\Models\BankingAccount\Activation\Comment;

use DateTime;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Mail\System\Trace;
use RZP\Http\RequestHeader;
use RZP\Models\BankingAccount;
use RZP\Models\BankingAccountService;
use RZP\Exception\BadRequestException;

class Service extends Base\Service
{
    public function fetchMultiple(string $bankingAccountId, array $input): array
    {
        $bankingAccountService = new BankingAccount\Service();
        $basService = new BankingAccountService\Service();

        [$existsInApi, $bankingAccount] = $bankingAccountService->checkAndGetBankingAccountId($bankingAccountId);

        if ($existsInApi == false)
        {
            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_SERVICE_RBL_ON_BAS_REQUEST,
                [
                    'banking_account_id' => $bankingAccountId,
                    'route'              => $this->app['router']->currentRouteName(),
                ]);

            return $basService->getCommentsForRblLms($bankingAccountId);
        }

        // Disable for now
        // In batch upload: we are adding comments with type: 'internal'
        // even with source_team_type: external which don't get reflected on LMS because of this filter
        // if ($this->app['basicauth']->isAdminAuth() === true)
        // {
        //     $input[Fetch::FOR_SOURCE_TEAM_TYPE] = 'internal';
        // }

        $input[Entity::BANKING_ACCOUNT_ID] = $bankingAccount->getId();

        $comments = $this->repo->banking_account_comment->fetch($input);

        return $comments->toArrayPublicWithExpand();
    }

    /**
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\InvalidArgumentException
     */
    public function fetchMultipleEntity(BankingAccount\Entity $bankingAccount, array $input): Base\PublicCollection
    {
        $input[Entity::BANKING_ACCOUNT_ID] = $bankingAccount->getId();

        $this->repo->banking_account_comment->setMerchantIdRequiredForMultipleFetch(false);

        return $this->repo->banking_account_comment->fetch($input);
    }

    public function createForBankingAccount(string $bankingAccountId, array $input)
    {
        $bankingAccountService = new BankingAccount\Service();
        $basService = new BankingAccountService\Service();

        [$existsInApi, $bankingAccount] = $bankingAccountService->checkAndGetBankingAccountId($bankingAccountId);

        if ($existsInApi == false)
        {
            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_SERVICE_RBL_ON_BAS_REQUEST,
                [
                    'banking_account_id' => $bankingAccountId,
                    'route'              => $this->app['router']->currentRouteName(),
                ]);

            return $basService->addCommentForRblLms($bankingAccountId, $input);
        }

        $maker = ($this->app['basicauth']->isAdminAuth() === true) ? $this->app['basicauth']->getAdmin() : $this->app['basicauth']->getUser();

        // Note: A lot of code flows use Core create instead of this service method for comments
        // because of requiring admin entity. In Batch service admin_id is sent via request body,
        // and not set in middleware.
        // Make any common changes in core method rather than here.
        $newComment = (new Core)->create($bankingAccount, $maker, $input);

        return $newComment->toArrayPublic();
    }

    /**
     * @throws Exception\BadRequestException
     */
    public function createCommentFromBatch(array $input) : array
    {
        // $input['date_time'] must be in Day/Month/Year Hour:Minute:Sec format
        // example: 05/12/2022 12:00:10

        $this->trace->info(TraceCode::BANKING_ACCOUNT_COMMENT_CREATE_BATCH,
            [
                'input' => $input,
                'batch_id' => $this->app['request']->header(RequestHeader::X_Batch_Id, null),
                'creator_id' => $this->app['request']->header(RequestHeader::X_Creator_Id, null),
                'creator_type' => $this->app['request']->header(RequestHeader::X_Creator_Type, null)
            ]);

        $commentPayload = [
            Entity::SOURCE_TEAM         => 'ops',
            Entity::ADDED_AT            => DateTime::createFromFormat("d/m/Y H:i:s", $input[Entity::DATE_TIME], new \DateTimeZone('Asia/Kolkata'))->getTimestamp(),
            Entity::COMMENT             => $input[Entity::OPS_CALL_COMMENT],
            Entity::SOURCE_TEAM_TYPE    => 'internal',
            Entity::TYPE                => 'internal',
            Entity::NOTES               => [
                Entity::FIRST_DISPOSITION     => $input[Entity::FIRST_DISPOSITION],
                Entity::SECOND_DISPOSITION    => $input[Entity::SECOND_DISPOSITION],
                Entity::THIRD_DISPOSITION     => $input[Entity::THIRD_DISPOSITION],
            ]
        ];

        $bankingAccounts = $this->repo->banking_account->fetchBankingAccountsByMerchantIdAccountTypeChannel($input[Entity::MERCHANT_ID], BankingAccount\Channel::RBL, BankingAccount\AccountType::CURRENT);

        // Fail batch call if number count($bankingAccounts) != 1. Feature requested by Ops
        if (count($bankingAccounts) != 1)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_ACTION_NOT_SUPPORTED, null, $input);
        }

        $admin = $this->repo->admin->findOrFailPublic($input[Entity::ADMIN_ID]);

        $newComment = (new Core)->create($bankingAccounts[0], $admin, $commentPayload);

        return $newComment->toArrayPublic();
    }

    public function update(string $id, array $input): array
    {
        $bankingAccountId = $input[Entity::BANKING_ACCOUNT_ID] ?? ''; // banking_account_id from query param

        $bankingAccountService = new BankingAccount\Service();
        $basService = new BankingAccountService\Service();

        // If banking_account_id is present in query param, then handle routing to BAS if needed
        if (!empty($bankingAccountId)) {

            [$existsInApi, $bankingAccount] = $bankingAccountService->checkAndGetBankingAccountId($bankingAccountId);

            if ($existsInApi == false)
            {
                $this->trace->info(
                    TraceCode::BANKING_ACCOUNT_SERVICE_RBL_ON_BAS_REQUEST,
                    [
                        'banking_account_id' => $bankingAccountId,
                        'route'              => $this->app['router']->currentRouteName(),
                    ]);

                return $basService->updateCommentForRbl($bankingAccountId, $id, $input);
            }

        }

        $comment = $this->repo->banking_account_comment->findOrFail($id);

        $comment = (new Core)->update($comment, $input);

        return $comment->toArrayPublic();
    }
}
