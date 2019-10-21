<?php

namespace RZP\Models\BankingAccount;

use Mail;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Services\FTS;
use Razorpay\IFSC\Bank;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\BankAccount;
use RZP\Models\VirtualAccount;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Balance;
use RZP\Exception\LogicException;
use RZP\Models\Settlement\Channel;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\BadRequestException;
use RZP\Models\BankingAccount\Gateway;
use RZP\Exception\RecordAlreadyExists;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Mail\BankingAccount\UpdateNotifications\Factory as StatusUpdateMailerFactory;

class Core extends Base\Core
{
    const FTS_MAX_RETRIES = 1;

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

    public function statusHasChanged(string $previousStatus, string $newStatus): bool
    {
        return $previousStatus !== $newStatus;
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
                    'Banking Account ID' => $bankingAccount->getId(),
                    'Merchant ID'        => $bankingAccount->merchant->getId(),
                    'Status'             => $bankingAccount->getStatus(),
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
                    'Banking Account ID' => $bankingAccount->getId(),
                    'Merchant ID'        => $bankingAccount->merchant->getId(),
                    'Status'             => $bankingAccount->getStatus(),
                    'Error'              => $e->getMessage(),
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

        // we want to setStatus method to handle all the status validations
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

                $this->updateBankingAccount($bankingAccount, $attributes);
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

    public function updateBankingAccount(Entity $bankingAccount, array $input)
    {
        $channel = $bankingAccount->getChannel();

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_EDIT,
            [
                'id'      => $bankingAccount->getId(),
                'channel' => $channel,
                'input'   => $input,
            ]);

        $processor = $this->getProcessor($channel);

        $processor->validateAccountBeforeUpdating($input);

        $input = $processor->formatInputParametersIfRequired($input);

        $bankingAccount->edit($input);

        if (empty($input[Entity::STATUS]) === false)
        {
            $bankingAccount->setStatus($input[Entity::STATUS]);
        }

        $this->checkMerchantIsActivatedBeforeAccountActivation($bankingAccount, $input);

        $this->repo->saveOrFail($bankingAccount);

        return $bankingAccount;
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

    public function createOrFetchFtsFundAccountForMerchant(Entity $bankingAccount)
    {
        $fundAccountId = $bankingAccount->getFtsFundAccountId();

        if ($fundAccountId !== null)
        {
            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_FTS_MAPPING_ALREADY_PRESENT,
                ['fts_id' => $fundAccountId, 'id' => $bankingAccount->getId()]
            );

            return $fundAccountId;
        }

        $retryCount = 0;

        /** @var FTS\CreateAccount $ftsService */
        $ftsService = app('fts_create_account');

        $response = [];

        while (true)
        {
            try
            {
                $response = $ftsService->createFundAccount(
                                                    $bankingAccount->getId(),
                                                    Constants\Entity::BANKING_ACCOUNT,
                                                    'payout');
                break;
            }
            catch(\Throwable $e)
            {
                if (($e instanceof \Requests_Exception) and
                    (checkRequestTimeout($e) === true) and
                    ($retryCount < self::FTS_MAX_RETRIES))
                {
                    $this->trace->info(
                        TraceCode::FTS_SERVICE_RETRY,
                        [
                            'message' => $e->getMessage(),
                            'data'    => $e->getData(),
                        ]);

                    $retryCount++;
                }
                else
                {
                    throw $e;
                }
            }
        }

        if (empty($response[FTS\Constants::BODY][FTS\Constants::FUND_ACCOUNT_ID]) === true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR_BANKING_ACCOUNT_FUND_ACCOUNT_CREATION_FAILED,
                null,
                ['id' => $bankingAccount->getId(), 'response' => $response],
                'FTS fund Account Id could not stored, Please try again!'
            );
        }

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_FTS_MAPPING_CREATION_RESPONSE,
            ['id' => $bankingAccount->getId(), 'response' => $response]
        );

        return $response[FTS\Constants::BODY][FTS\Constants::FUND_ACCOUNT_ID];
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

    public function storeCredentialsAndActivateAccount(Entity $bankingAccount, array $input)
    {
        $this->trace->info(TraceCode::BANKING_ACCOUNT_SAVE_MERCHANT_CREDENTIALS_REQUEST,
            [
                'id'      => $bankingAccount->getId(),
                'channel' => $bankingAccount->getChannel(),
            ]);

        //
        // This is in a transaction because, BankingAccount updation
        // and Balance entity creation, both should succeed or fail
        //
        $this->repo->transaction(function () use ($bankingAccount, $input)
        {
            $channel = $bankingAccount->getChannel();

            $processor = $this->getProcessor($channel);

            $processor->storeCredentials($bankingAccount, $input);

            // merchant credentials are verified and saved. Now storing balance for the account and
            // activating the account.

            $merchant = $bankingAccount->merchant;

            $mode = $this->app['rzp.mode'];

            $balanceInfo = $processor->getBalanceAttributesToSave($bankingAccount);

            $balance = (new Balance\Core)->createBalanceForCurrentAccount($merchant, $balanceInfo, $mode);

            $input[Entity::STATUS] = Status::ACTIVATED;

            $this->checkMerchantIsActivatedBeforeAccountActivation($bankingAccount, $input);

            $bankingAccount->fill($input);

            $bankingAccount->balance()->associate($balance);

            $this->repo->saveOrFail($bankingAccount);
        });
    }

    public function createAccountMappingForFts(Entity $bankingAccount)
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_FTS_MAPPING_CREATION_REQUEST,
            ['id' => $bankingAccount->getId()]
        );

        // we do not want the merchant to get affected by failures in FTS service so handling
        // the same in try catch block
        try
        {
            $fundAccountId = $this->createOrFetchFtsFundAccountForMerchant($bankingAccount);

            $channel = $bankingAccount->getChannel();

            $processor = $this->getProcessor($channel);

            $content = $processor->generateRequestForSourceAccount($bankingAccount);

            $product = 'PAYOUT';

            $this->makeSourceAccountRequest(
                $bankingAccount->getId(),
                $fundAccountId,
                $content,
                $product,
                $channel);
        }
        catch (\Throwable $e)
        {
            $this->trace->info(
                TraceCode::FTS_FAILURE_EXCEPTION,
                [
                    'code'          => $e->getCode(),
                    'message'       => $e->getMessage(),
                ]);
        }

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

    public function makeSourceAccountRequest(string $id, string $ftsAccountId, array $content,
                                                string $product = 'PAYOUT', string $channel = 'ICICI')
    {
        $retryCount = 0;

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_SOURCE_ACCOUNT_CREATION_REQUEST,
            ['id' => $id, 'fts_id' => $ftsAccountId]
        );

        /** @var FTS\CreateAccount $ftsService */
        $ftsService = app('fts_create_account');

        while (true)
        {
            try
            {
                $response = $ftsService->createSourceAccount(
                                                    $id,
                                                    $ftsAccountId,
                                                    $content,
                                                    $product,
                                                    $channel);

                return $this->checkSourceAccountResponseForError($response);

            }
            catch (RecordAlreadyExists $e)
            {
                $this->trace->info(TraceCode::BANKING_ACCOUNT_SOURCE_ACCOUNT_ALREADY_PRESENT,
                    ['channel' => $channel, 'id' => $id, 'fts_id' => $ftsAccountId]
                );

                return null;
            }
            catch (\Throwable $e)
            {
                if (($e instanceof \Requests_Exception) and
                    (checkRequestTimeout($e) === true) and
                    ($retryCount < self::FTS_MAX_RETRIES))
                {
                    $this->trace->info(
                        TraceCode::FTS_SERVICE_RETRY,
                        [
                            'message' => $e->getMessage(),
                            'data'    => $e->getData(),
                        ]);

                        $retryCount++;
                }
                else
                {
                    throw $e;
                }
            }
        }
    }

    protected function checkSourceAccountResponseForError(array $response)
    {
        if (((isset($response[FTS\Constants::BODY][FTS\Constants::MESSAGE]) === true) and
            ($response[FTS\Constants::BODY][FTS\Constants::MESSAGE] === 'source account registered')))
        {
            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_SOURCE_ACCOUNT_CREATION_RESPONSE,
                ['response' => $response]
            );

            return null;
        }

        // in any other case source account creation failed. So we throw an exception here.
        throw new BadRequestException(
            ErrorCode::BAD_REQUEST_ERROR_SOURCE_ACCOUNT_CREATION_FAILED,
            null,
            null,
            'Source account creation failed, Try again'
        );
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
    protected function checkMerchantIsActivatedBeforeAccountActivation(Entity $bankingAccount, array $input)
    {
        $this->redactSecrets($input);

        $this->trace->info(TraceCode::BANKING_ACCOUNT_ACTIVATION_REQUEST,
            [
                'id'      => $bankingAccount->getId(),
                'channel' => $bankingAccount->getChannel(),
                'input'   => $input,
            ]);

        $merchant = $bankingAccount->merchant;

        if ((isset($input[Entity::STATUS]) === true) and
            ($input[Entity::STATUS] === Status::ACTIVATED))
        {

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

    protected function redactSecrets(array $input)
    {
        unset($input[Entity::PASSWORD]);
    }
}
