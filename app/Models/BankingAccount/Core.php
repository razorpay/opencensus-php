<?php

namespace RZP\Models\BankingAccount;

use Mail;
use Razorpay\IFSC\Bank;
use Razorpay\Trace\Logger as Trace;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Admin;
use RZP\Models\BankAccount;
use RZP\Models\VirtualAccount;
use RZP\Models\Merchant\Detail;
use RZP\Exception\LogicException;
use RZP\Models\Settlement\Channel;
use RZP\Exception\BadRequestException;
use RZP\Models\BankingAccount\Gateway;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\BankingAccount\Detail as BankingAccountDetail;
use RZP\Mail\BankingAccount\StatusNotifications\Factory as StatusUpdateMailerFactory;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();

        $this->config = $this->app['config']->get('banking_account');
    }

    public function createOrFetchSharedBankingAccountFromVA(VirtualAccount\Entity $virtualAccount): Entity
    {
        // Virtual account has to be with receiver_type bank account
        if ($virtualAccount->hasBankAccount() === false)
        {
            throw new LogicException(
                'Banking accounts can only be create on bank type virtual accounts',
                null,
                ['virtual_account_id' => $virtualAccount->getId()]);
        }

        $bankAccount = $virtualAccount->bankAccount;
        $bankCode    = $bankAccount->getBankCode();

        // Only Yesbank bank accounts are allowed as shared banking accounts, for now
        if ($bankCode !== Bank::YESB)
        {
            throw new LogicException(
                'Only YesBank virtual accounts are supported', // for now 🤑
                null,
                ['bank_code' => $bankCode]);
        }

        $balanceId = $virtualAccount->getBalanceId();

        //
        // If a banking_account already exists for a balance_id, return that instead
        // of creating a new one.
        //
        $existingBankingAcc = $this->repo->banking_account->getFromBalanceId($balanceId);

        if ($existingBankingAcc !== null)
        {
            return $existingBankingAcc;
        }

        $bankingAccountInput = [
            Entity::ACCOUNT_IFSC              => $bankAccount->getIfscCode(),
            Entity::ACCOUNT_NUMBER            => $bankAccount->getAccountNumber(),
            Entity::FTS_FUND_ACCOUNT_ID       => $bankAccount->getFtsFundAccountId(),
            Entity::ACCOUNT_TYPE              => AccountType::NODAL,
            Entity::STATUS                    => Status::CREATED,
            Entity::BENEFICIARY_EMAIL         => $bankAccount->getBeneficiaryEmail(),
            Entity::BENEFICIARY_MOBILE        => $bankAccount->getBeneficiaryMobile(),
            Entity::BENEFICIARY_CITY          => $bankAccount->getBeneficiaryCity(),
            Entity::BENEFICIARY_STATE         => $bankAccount->getBeneficiaryState(),
            Entity::BENEFICIARY_COUNTRY       => $bankAccount->getBeneficiaryCountry(),
            Entity::BENEFICIARY_NAME          => $bankAccount->getBeneficiaryName(),
            Entity::BENEFICIARY_ADDRESS1      => $bankAccount->getBeneficiaryAddress1(),
            Entity::BENEFICIARY_ADDRESS2      => $bankAccount->getBeneficiaryAddress2(),
            Entity::BENEFICIARY_PIN           => $bankAccount->getBeneficiaryPin(),
            Entity::BENEFICIARY_ADDRESS3      => $bankAccount->getBeneficiaryAddress3() . ' ' .
                                                 $bankAccount->getBeneficiaryAddress4(),
        ];

        return $this->createYesbankBankingAccount(
            $bankingAccountInput,
            $virtualAccount->merchant,
            $virtualAccount->balance);
    }

    public function notifyMerchantAboutUpdatedStatus(Entity $bankingAccount)
    {
        try
        {
            $mailer = StatusUpdateMailerFactory::getMailer($bankingAccount);

            Mail::queue($mailer);

            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_UPDATE_NOTIFICATION,
                [
                    'banking_account_id' => $bankingAccount->getId(),
                    'merchant_id'        => $bankingAccount->merchant->getId(),
                    'status'             => $bankingAccount->getStatus(),
                    'message'            => 'Mail Sent'
                ]);
        }
        catch(\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::BANKING_ACCOUNT_UPDATE_NOTIFICATION_FAILED,
                [
                    'banking_account_id' => $bankingAccount->getId(),
                    'merchant_id'        => $bankingAccount->merchant->getId(),
                    'status'             => $bankingAccount->getStatus(),
                    'error'              => $e->getMessage(),
                ]);
        }
    }

    public function createBankingAccount(array $input, Merchant\Entity $merchant): Entity
    {
        (new Validator)->setStrictFalse()->validateInput(Validator::PRE_PROCESS, $input);

        $channel = $input[Entity::CHANNEL];

        // Currently we are just checking if there exists even one account of the merchant for the selected
        // channel. If we find any such account we will just return the account and wont create a new one.
        // But later when a merchant will start having more than one current account in the same channel
        // this logic will have to be handled.

        $bankingAccount = $this->repo->banking_account->getBankingAccountOfMerchant($merchant, $channel);

        if ($bankingAccount !== null)
        {
            return $bankingAccount;
        }

        $bankingAccount = new Entity;

        $processor = $this->getProcessor($channel);

        $bankContent = $processor->validateAndPreProcessInputForAccountCreation($input);

        $input = array_merge($input, $bankContent);

        // we want the setStatus method to handle all the status validations
        // also we might add logic around updating other columns based on
        // change of status. So moving status out of input and explicitly
        // calling setStatus

        array_pull($input, Entity::STATUS);

        (new Validator)->validateInput($channel . 'Create', $input);

        $input[Entity::ACCOUNT_TYPE] = AccountType::CURRENT;

        $bankingAccount->build($input);

        $bankingAccount->setStatus($bankContent[Entity::STATUS]);

        $bankingAccount->merchant()->associate($merchant);

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_ENTITY_CREATED,
            [
                $bankingAccount->toArray(),
            ]);

        $this->repo->saveOrFail($bankingAccount);

        $stateCore = new State\Core;

        $content = [Entity::STATUS => $bankContent[Entity::STATUS]];

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_UPDATE_ACTIVATION_STATUS,
            [
                'id'    => $bankingAccount->getId(),
                'input' => $content,
            ]);

        $stateCore->createForMakerAndEntity($content, $merchant, $bankingAccount);

        return $bankingAccount;
    }

    public function processAccountInfoWebhook(string $channel, array $input)
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_INFO_WEBHOOK_REQUEST,
            [
                'input'         => $input,
                'gateway'       => $channel,
            ]);

        Channel::validate($channel);

        $processor = $this->getProcessor($channel);

        try
        {
            $processor->preProcessAccountInfoNotification($input);

            $attributes = $processor->processAccountInfoNotification($input);

            $bankingAccount = $this->repo
                                   ->banking_account
                                   ->findByBankReferenceAndChannel($channel,
                                                                   $attributes[Entity::BANK_REFERENCE_NUMBER]);

            if ($bankingAccount->isAlreadyActivated() === false)
            {
                $this->trace->info(
                    TraceCode::DUPLICATE_ACCOUNT_INFO_WEBHOOK,
                    [
                        'input'     => $input,
                        'channel'   => $channel,
                    ]);

                $this->updateBankingAccount($bankingAccount, $attributes, $bankingAccount->merchant);
            }

            $response = $processor->postProcessAccountInfoNotificationResponse($input, Status::PROCESSED);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);

            $response = $processor->postProcessAccountInfoNotificationResponse($input, Status::CANCELLED);
        }

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_INFO_WEBHOOK_RESPONSE,
            [
                'response'  => $response,
                'gateway'   => $channel,
            ]);

        return $response;
    }

    public function updateBankingAccount(Entity $bankingAccount, array $input, Base\PublicEntity $entity = null)
    {
        $channel = $bankingAccount->getChannel();

        $traceRequest = $input;

        // details array may contain sensitive information
        // like merchant password and other gateway specific
        // fields. These will be handled by the gateway module
        // hence redacting from here.
        unset($traceRequest[Entity::DETAILS]);
        unset($traceRequest[Entity::PASSWORD]);

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_EDIT,
            [
                'id'      => $bankingAccount->getId(),
                'channel' => $channel,
                'input'   => $traceRequest,
            ]);

        $processor = $this->getProcessor($channel);

        $processor->validateAccountBeforeUpdating($input);

        $input = $processor->formatInputParametersIfRequired($input);

        $oldStatus = $bankingAccount->getStatus();

        $bankingAccount->edit($input);

        // we need to store change log only when the
        // status has changed.
        $bankInternalStatusChanged = $bankingAccount->isDirty(Entity::BANK_INTERNAL_STATUS);

        $bankingAccountStatusChanged = $bankingAccount->isDirty(Entity::STATUS);

        if (empty($input[Entity::STATUS]) === false)
        {
            $bankingAccount->setStatus($input[Entity::STATUS]);
        }

        $this->repo->transaction(function() use ($bankingAccount, $input, $processor)
        {
            $this->repo->saveOrFail($bankingAccount);

            if ((isset($input[Entity::DETAILS]) === true) and
                (empty($input[Entity::DETAILS])) === false)
            {
                if (in_array($bankingAccount->getStatus(), Status::$allowedStatusForDetails, true) === false)
                {
                    throw new BadRequestException(
                        ErrorCode::BAD_REQUEST_BANKING_ACCOUNT_DETAILS_UPDATE_NOT_ALLOWED_ON_CURRENT_STATUS,
                        null,
                        [
                            BankingAccountDetail\Entity::BANKING_ACCOUNT_ID => $bankingAccount->getId(),
                            Entity::STATUS                                  => $bankingAccount->getStatus(),
                        ],
                        'Account Details cannot be saved for given account status');
                }

                (new BankingAccountDetail\Core)->updateBankingAccountDetails($input[Entity::DETAILS],
                                                                             $bankingAccount,
                                                                             $processor);
            }
        });

        // We need to populate banking account details using
        // toArrayPublic, which populates only the pre fetched
        // relations. So explicitly fetching this relation here
        $bankingAccount->load('bankingAccountDetails');

        if (($bankInternalStatusChanged === true) or
            ($bankingAccountStatusChanged === true))
        {
            $stateCore = new State\Core;

            $content = [
                Entity::STATUS              => $bankingAccount->getStatus(),
                State\Entity::BANK_STATUS   => $bankingAccount->getBankInternalStatus()
            ];

            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_UPDATE_ACTIVATION_STATUS,
                [
                    'id'    => $bankingAccount->getId(),
                    'input' => $content,
                ]);

            $stateCore->createForMakerAndEntity($content, $entity, $bankingAccount);
        }

        return $bankingAccount;
    }

    public function updateBankingAccountWithFtsId(Entity $bankingAccount, $ftsFundAccountId)
    {
        $bankingAccount->setFtsFundAccountId($ftsFundAccountId);

        $this->repo->saveOrFail($bankingAccount);
    }

    public function getBankingAccountEntity(string $id)
    {
        return $this->repo->banking_account->findOrFailPublic($id);
    }

    public function addServiceablePincodes(array $pincodes, string $channel)
    {
        $this->trace->info(
            TraceCode::ADD_SERVICEABLE_PINCODES,
            [
                'pincodes' => $pincodes,
                'channel'  => $channel,
            ]);

        $processor = $this->getProcessor($channel);

        $processor->addServiceablePincodes($pincodes);
    }

    public function deleteServiceablePincodes(array $pincodes, string $channel)
    {
        $this->trace->info(
            TraceCode::REMOVE_SERVICEABLE_PINCODES,
            [
                'pincodes' => $pincodes,
                'channel'  => $channel,
            ]);

        $processor = $this->getProcessor($channel);

        $processor->deleteServiceablePincodes($pincodes);
    }

    public function activate(Entity $bankingAccount, array $input, Admin\Entity $admin)
    {
        $this->checkMerchantIsActivatedBeforeAccountActivation($bankingAccount);

        //
        // This is in a transaction because, BankingAccount entity update
        // and Balance entity creation, both should succeed or fail
        //
        $bankingAccount = $this->repo->transaction(function () use ($bankingAccount, $input, $admin)
        {
            $channel = $bankingAccount->getChannel();

            $processor = $this->getProcessor($channel);

            $bankingAccount = $processor->activate($bankingAccount, $input);

            $merchant = $bankingAccount->merchant;

            $mode = $this->app['rzp.mode'];

            $balanceInfo = $this->getBalanceAttributesToSave($bankingAccount);

            $balance = (new Merchant\Balance\Core)->createBalanceForCurrentAccount($merchant, $balanceInfo, $mode);

            $bankingAccount->balance()->associate($balance);

            $this->repo->saveOrFail($bankingAccount);

            $stateCore = new State\Core;

            $content = [
                Entity::STATUS => Status::ACTIVATED,
            ];

            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_UPDATE_ACTIVATION_STATUS,
                [
                    'id'    => $bankingAccount->getId(),
                    'input' => $content,
                ]);

            $stateCore->createForMakerAndEntity($content, $admin, $bankingAccount);

            return $bankingAccount;
        });

        return $bankingAccount;
    }

    public function bulkCreateBankingAccountsForYesbank(array $input)
    {
        $limit = $input['limit'];

        unset($input['limit']);

        $bankAccounts = $this->repo->bank_account->fetchAccountsNotPresentInBankingAccountsForYesbank($limit);

        $this->trace->info(
            TraceCode::BULK_CREATE_BANKING_ACCOUNTS_REQUEST,
            [
                'input' => $input,
                'bank_account_ids' => $bankAccounts->pluck(Entity::ID)
            ]);

        $successCount = $failedCount = 0;

        $failedIds = [];

        if (count($bankAccounts) !== 0)
        {
            foreach ($bankAccounts as $bankAccount)
            {
                try
                {
                    /** @var Merchant\Entity $merchant */
                    $merchant = $bankAccount->virtualAccount->merchant;

                    $balance = $bankAccount->virtualAccount->balance;

                    $attributes = $this->getYesbankAccountAttributes($bankAccount);

                    $this->createYesbankBankingAccount($attributes, $merchant, $balance);

                    $successCount++;
                }
                catch (\Throwable $e)
                {
                    $this->trace->traceException(
                        $e,
                        Trace::INFO,
                        TraceCode::BANKING_ACCOUNT_YESBANK_CREATE_FAILED,
                        [
                            'bank_account_id' => $bankAccount->getId()
                        ]);

                    $failedIds[] = $bankAccount->getId();

                    $failedCount++;
                }
            }
        }

        $response = [
            'total_count'       => count($bankAccounts),
            'success_count'     => $successCount,
            'failed_count'      => $failedCount,
            'failed_ids'        => $failedIds,
        ];

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_YESBANK_BULK_CREATE_RESPONSE,
            [
                'response'  => $response,
                'range'     => $limit,
                'input'     => $input,
                'channel'   => Channel::YESBANK,
            ]);

        return $response;
    }

    public function getActivationStatusChangeLog(Entity $bankingAccount)
    {
        return $bankingAccount->getActivationStatusChangeLog();
    }

    protected function getBalanceAttributesToSave(Entity $bankingAccount)
    {
        $attributes = [
            Merchant\Balance\Entity::ACCOUNT_TYPE        => Merchant\Balance\AccountType::DIRECT,
            Merchant\Balance\Entity::CHANNEL             => $bankingAccount->getChannel(),
            Merchant\Balance\Entity::ACCOUNT_NUMBER      => $bankingAccount->getAccountNumber(),
        ];

        return $attributes;
    }

    /**
     * This method is responsible for checking that unless the merchant is L2 activated, no one can update
     * the status of current account to activated. This to avoid cases of manual error by Bizops.
     *
     * @param Entity $bankingAccount
     * @param array $input
     *
     * @throws BadRequestValidationFailureException
     */
    protected function checkMerchantIsActivatedBeforeAccountActivation(Entity $bankingAccount)
    {
        $merchant = $bankingAccount->merchant;

        $merchantActivationStatus = $merchant->merchantDetail->getActivationStatus();

        if ($merchantActivationStatus !== Detail\Status::ACTIVATED)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_BANKING_ACCOUNT_ACTIVATION_NOT_PERMITTED,
                Entity::STATUS,
                [
                    'merchant_activation_status' => $merchant->merchantDetail->getActivationStatus(),
                    'banking_account'            => $bankingAccount->getId(),
                ]);
        }
    }

    protected function createYesbankBankingAccount(
        array $input,
        Merchant\Entity $merchant,
        Merchant\Balance\Entity $balance): Entity
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_CREATE,
            [
                'channel' => Channel::YESBANK,
                'input'   => $input,
            ]);

        (new Validator)->validateInput(Validator::YESBANK_CREATE, $input);

        $input[Entity::CHANNEL] = Channel::YESBANK;

        $bankingAccount = new Entity;

        $bankingAccount->build($input);

        $bankingAccount->merchant()->associate($merchant);

        $bankingAccount->balance()->associate($balance);

        // Yesbank accounts are always created in the processed state
        $bankingAccount->setStatus(Status::ACTIVATED);

        $this->repo->saveOrFail($bankingAccount);

        return $bankingAccount;
    }

    protected function getProcessor(string $channel): Gateway\Processor
    {
        $processor = __NAMESPACE__ . '\\' . 'Gateway';

        $processor .= '\\' . studly_case($channel) . '\\' . 'Processor';

        return new $processor();
    }

    protected function getYesbankAccountAttributes(BankAccount\Entity $bankAccount)
    {
        $attributes = [
            Entity::ACCOUNT_NUMBER            => $bankAccount->getAccountNumber(),
            Entity::ACCOUNT_IFSC              => $bankAccount->getIfscCode(),
            Entity::BENEFICIARY_EMAIL         => $bankAccount->getBeneficiaryEmail(),
            Entity::BENEFICIARY_MOBILE        => $bankAccount->getBeneficiaryMobile(),
            Entity::BENEFICIARY_CITY          => $bankAccount->getBeneficiaryCity(),
            Entity::BENEFICIARY_STATE         => $bankAccount->getBeneficiaryState(),
            Entity::BENEFICIARY_COUNTRY       => $bankAccount->getBeneficiaryCountry(),
            Entity::BENEFICIARY_NAME          => $bankAccount->getBeneficiaryName(),
            Entity::BENEFICIARY_ADDRESS1      => $bankAccount->getBeneficiaryAddress1(),
            Entity::BENEFICIARY_ADDRESS2      => $bankAccount->getBeneficiaryAddress2(),
            Entity::BENEFICIARY_PIN           => $bankAccount->getBeneficiaryPin(),
            Entity::ACCOUNT_TYPE              => AccountType::NODAL,
            Entity::STATUS                    => Status::CREATED,
            Entity::BENEFICIARY_ADDRESS3      => $bankAccount->getBeneficiaryAddress3() . ' ' .
                                                 $bankAccount->getBeneficiaryAddress4(),
        ];

        return $attributes;
    }
}
