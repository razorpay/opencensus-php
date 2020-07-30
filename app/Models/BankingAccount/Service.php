<?php

namespace RZP\Models\BankingAccount;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Http\RequestHeader;
use RZP\Models\Admin\Admin;
use RZP\Exception\BadRequestException;
use RZP\Models\BankingAccount\Activation\Comment;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function create(array $input): array
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_CREATE,
            [
                'input' => $input,
            ]);

        $account = $this->core->createBankingAccount($input, $this->merchant);

        $this->core->notifyOpsAboutProActivation($account);

        $this->core->notifyMerchantAboutUpdatedStatus($account);

        return $account->toArrayPublic();
    }

    /**
     * This function to be used only for admin or internal routes since
     * we are not fetching banking_account by merchant_id.
     * @param string $id
     * @param array $input
     * @param Admin\Entity $admin
     * @return array
     */
    public function update(string $id, array $input, Admin\Entity $admin = null): array
    {
        /** @var Entity $bankingAccount */
        $bankingAccount = $this->repo->banking_account->findByPublicId($id);

        $previousStatus = $bankingAccount->getStatus();

        $channel = $bankingAccount->getChannel();

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_EDIT,
            [
                'id'      => $bankingAccount->getId(),
                'channel' => $channel,
                'input'   => $input,
            ]);

        (new Validator)->setStrictFalse()->validateInput(Validator::INTERNAL_EDIT, $input);

        // $admin can be passed if called by updateDetailsFromBatchService
        // Ideally, admin should be set in the middleware, but
        // it's currently not done for requests coming from batch service.
        // TODO: handle correctly in middleware layer.
        if ($admin === null)
        {
            $admin = $this->app['basicauth']->getAdmin();
        }

        $account = $this->core->updateBankingAccount($bankingAccount, $input, $admin);

        $currentStatus = $bankingAccount->getStatus();

        if ($previousStatus !== $currentStatus)
        {
            $this->core->notifyMerchantAboutUpdatedStatus($bankingAccount);
        }

        return $account->toArrayPublic();
    }

    public function activate(string $id, array $input)
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_ACTIVATION_REQUEST,
            [
                'id'=> $id
            ]);

        //
        // This route is to be used via Admin auth only.
        // Don't use this on proxy auth
        //
        if ($this->auth->isAdminAuth() === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_BANKING_ACCOUNT_ACTIVATION_PERMITTED_ONLY_ON_ADMIN_AUTH);
        }

        /** @var Entity $bankingAccount */
        $bankingAccount = $this->repo->banking_account->findByPublicId($id);

        // validating if user tries to add/change credentials
        // after his account gets activated successfully

        $this->checkIfAccountAlreadyActivated($bankingAccount);

        $admin = $this->app['basicauth']->getAdmin();

        $bankingAccount = $this->core->activate($bankingAccount, $input, $admin);

        $this->core->notifyMerchantAboutUpdatedStatus($bankingAccount);

        return $bankingAccount->toArrayPublic();
    }

    public function addOrRemoveServiceablePincodes(array $input, $channel)
    {
        $input[Entity::CHANNEL] = $channel;

        (new Validator)->validateInput(Validator::SERVICEABLE_PINCODE, $input);

        $coreMethod = $input[Entity::ACTION] . 'ServiceablePincodes';

        $this->core->$coreMethod($input[Entity::PINCODES], $channel);

        return ['success' => true];
    }

    public function fetchMultiple()
    {
        return $this->merchant->bankingAccounts->load(Entity::BALANCE)->toArrayPublic();
    }

    public function processAccountInfoWebhook(string $channel, array $input)
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_INFO_WEBHOOK_REQUEST,
            [
                'input'   => $input,
                'gateway' => $channel,
            ]);

        $response = $this->core->processAccountInfoWebhook($channel, $input);

        return $response;
    }

    public function bulkCreateBankingAccountsForYesbank(array $input)
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_YESBANK_BULK_CREATE_REQUEST,
            [
                'input'     => $input,
                'channel'   => Channel::YESBANK,
            ]);

        $response = $this->core->bulkCreateBankingAccountsForYesbank($input);

        return $response;
    }

    public function processGatewayBalanceUpdate(string $channel)
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_PROCESS_GATEWAY_BALANCE_UPDATE_REQUEST,
            [
               'channel' => $channel,
            ]);

        $response = $this->core->dispatchGatewayBalanceUpdateForMerchants($channel);

        return $response;
    }

    public function getActivationStatusChangeLog(string $bankingAccountId)
    {
        /** @var Entity $bankingAccount */
        $bankingAccount = $this->repo->banking_account->findByPublicId($bankingAccountId);

        $activationStatusChangeLog = $this->core->getActivationStatusChangeLog($bankingAccount);

        return $activationStatusChangeLog->toArrayPublic();
    }

    protected function checkIfAccountAlreadyActivated(Entity $bankingAccount)
    {
        if ($bankingAccount->getStatus() === Status::ACTIVATED)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_BANKING_ACCOUNT_ALREADY_ACTIVATED,
                null,
                ['id' => $bankingAccount->getId()]
            );
        }
    }

    public function bulkAssignReviewer(array $input)
    {
        (new Validator)->validateInput('bulk_assign_reviewer', $input);

        $bankingAccountIds  = $input[Entity::BANKING_ACCOUNT_IDS];

        $reviewerId = $input[Entity::REVIEWER_ID];

        return (new Core)->bulkAssignReviewer($reviewerId, $bankingAccountIds);
    }

    public function prepareInputForCommentCreate(array $input)
    {
        $requiredKeys = [
            Comment\Entity::COMMENT,
            Comment\Entity::SOURCE_TEAM_TYPE,
            Comment\Entity::SOURCE_TEAM,
            Comment\Entity::ADDED_AT
        ];

        $commentInput = array_intersect_key($input, array_fill_keys($requiredKeys, ''));

        $commentInput[Comment\Entity::COMMENT] = trim($commentInput[Comment\Entity::COMMENT]);

        return $commentInput;
    }

    public function prepareInputForUpdate(array $input)
    {
        $requiredKeys = [
            Entity::STATUS
        ];

        $updateInput = array_intersect_key($input, array_fill_keys($requiredKeys, ''));

        if (empty($updateInput[Entity::STATUS]) === false)
        {
            $updateInput[Entity::STATUS] = trim($updateInput[Entity::STATUS]);

            $updateInput[Entity::STATUS] = Status::transformFromExternalToInternal($updateInput[Entity::STATUS]);
        }

        return $updateInput;
    }

    public function updateDetailsFromBatchService(array $input)
    {
        $this->trace->info(TraceCode::BANKING_ACCOUNT_UPDATE_DETAILS_FROM_BATCH,
            [
                'input' => $input,
                'batch_id' => $this->app['request']->header(RequestHeader::X_Batch_Id, null),
                'creator_id' => $this->app['request']->header(RequestHeader::X_Creator_Id, null),
                'creator_type' => $this->app['request']->header(RequestHeader::X_Creator_Type, null)
            ]);

        try
        {
            $bankingAccount = $this->repo->banking_account->findByBankReferenceAndChannel(
                $input[Entity::CHANNEL],
                $input[Entity::BANK_REFERENCE_NUMBER]);

            $admin = $this->repo->admin->findOrFailPublic($input[Entity::ADMIN_ID]);
        }
        catch (\Throwable $e)
        {
            // TODO: throw different validation errors for both the find queries.
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_ID, null,
                [
                    Entity::BANK_REFERENCE_NUMBER => $input[Entity::BANK_REFERENCE_NUMBER],
                    Entity::CHANNEL => $input[Entity::CHANNEL],
                    Entity::ADMIN_ID => $input[Entity::ADMIN_ID]
                ]);
        }

        // Transaction because we want to eiher process the entire batch row, or nothing, so that it
        // is possible to retry.
        $this->repo->transaction(function() use ($input, $bankingAccount, $admin)
        {
            $commentCreateInput = $this->prepareInputForCommentCreate($input);

            if (empty($commentCreateInput[Comment\Entity::COMMENT]) === false)
            {
                (new Comment\Core)->create($bankingAccount, $admin, $commentCreateInput);
            }

            $updateInput = $this->prepareInputForUpdate($input);

            if (empty($updateInput[Entity::STATUS]) === false)
            {
                $this->update($bankingAccount->getPublicId(), $updateInput, $admin);
            }
        });

        return [
            'status' => 'success'
        ];
    }
}
