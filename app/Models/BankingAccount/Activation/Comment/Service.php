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
use RZP\Exception\BadRequestException;

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

    /**
     * @throws Exception\BadRequestException
     */
    public function createCommentFromBatch(array $input) : array
    {
        // $input['date_time'] must be in Month/Day/Year Hour:Minute:Sec format
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
            Entity::ADDED_AT            => strtotime($input[Entity::DATE_TIME]),
            Entity::COMMENT             => $input[Entity::OPS_CALL_COMMENT],
            Entity::SOURCE_TEAM_TYPE    => 'internal',
            Entity::TYPE                => 'internal',
            Entity::NOTES               => [
                Entity::FIRST_DISPOSITION     => $input[Entity::FIRST_DISPOSITION],
                Entity::SECOND_DISPOSITION    => $input[Entity::SECOND_DISPOSITION],
                Entity::THIRD_DISPOSITION     => $input[Entity::THIRD_DISPOSITION],
            ]
        ];

        $bankingAccount = $this->repo->banking_account->findByPublicId('bacc_'.$input[Entity::BANKING_ACCOUNT_ID]);

        $admin = $this->repo->admin->findOrFailPublic($input[Entity::ADMIN_ID]);

        $newComment = (new Core)->create($bankingAccount, $admin, $commentPayload);

        return $newComment->toArrayPublic();
    }

    public function update(string $id, array $input): array
    {
        $comment = $this->repo->banking_account_comment->findOrFail($id);

        $comment = (new Core)->update($comment, $input);

        return $comment->toArrayPublic();
    }
}
