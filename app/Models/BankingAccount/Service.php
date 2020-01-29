<?php

namespace RZP\Models\BankingAccount;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

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
     * @return array
     */
    public function update(string $id, array $input): array
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

        $admin = $this->app['basicauth']->getAdmin();

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
        return $this->merchant->bankingAccounts;
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
}
