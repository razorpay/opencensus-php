<?php

namespace RZP\Models\BankingAccount;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Services\FTS;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Constants\Product;
use RZP\Services\CardVault;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Balance;
use RZP\Models\Settlement\Channel;
use RZP\Models\BankingAccount\Gateway;
use RZP\Exception\BadRequestException;
use RZP\Exception\RecordAlreadyExists;
use RZP\Exception\BadRequestValidationFailureException;

class Core extends Base\Core
{
    const FTS_MAX_RETRY = 1;

    protected $processor;

    public function __construct()
    {
        parent::__construct();

        $this->config = $this->app['config']->get('banking_account');
    }

    public function createBankingAccount(array $input, Merchant\Entity $merchant): Entity
    {
        (new Validator)->setStrictFalse()->validateInput('pre_process', $input);

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

        $processor = $this->getProcessor($channel);

        $bankContent = $processor->validateAndPreProcessInputForAccountCreation($input);

        $input = array_merge($input, $bankContent);

        $bankingAccount = new Entity;

        $bankingAccount->build($input);

        $bankingAccount->merchant()->associate($merchant);

        $this->repo->saveOrFail($bankingAccount);

        return $bankingAccount;
    }

    public function processAccountInfoWebhook(string $channel, array $input)
    {
        Channel::validate($channel);

        $processor = $this->getProcessor($channel);

        try
        {
            $processor->preProcessAccountInfoNotification($input);

            $attributes = $processor->processAccountInfoNotification($input);

            $bankingAccount = $this->repo->banking_account->findByBankReferenceAndChannel(
                                                                $channel, $attributes[Entity::BANK_REFERENCE_NUMBER]);

            $this->updateBankingAccount($bankingAccount, $attributes);

            $response = $processor->postProcessAccountInfoNotificationResponse($input, Status::PROCESSED);
        }
        catch (\Throwable $e)
        {
            $response = $processor->postProcessAccountInfoNotificationResponse($input, Status::CANCELLED);
        }

        return $response;
    }

    public function updateBankingAccount(Entity $bankingAccount, array $input)
    {
        $channel = $bankingAccount->getChannel();

        $processor = $this->getProcessor($channel);

        $processor->validateAccountBeforeUpdating($input);

        $this->checkMerchantIsActivatedBeforeAccountActivation($bankingAccount, $input);

        $bankingAccount = $bankingAccount->edit($input);

        $this->repo->saveOrFail($bankingAccount);

        return $bankingAccount;
    }

    public function createOrFetchFtsFundAccountForMerchant(Entity $bankingAccount)
    {
        $fundAccountId = $bankingAccount->getFtsFundAccountId();

        if ($fundAccountId !== null)
        {
            return $fundAccountId;
        }

        $retryCount = 0;

        while (true)
        {
            try
            {
                $response = $this->app['fts_create_account']->createFundAccount($bankingAccount->getId(),
                                                                                Constants\Entity::BANKING_ACCOUNT,
                                                                                'payout');
                break;

            }
            catch(\Throwable $e)
            {
                $errorCode = $e->getCode();

                if ($errorCode === ErrorCode::SERVER_ERROR_FTS_SERVICE_TIMEOUT)
                {
                    if ($retryCount < self::FTS_MAX_RETRY)
                    {
                        $this->trace-info(
                            TraceCode::FTS_SERVICE_RETRY,
                            [
                                'message' => $e->getMessage(),
                                'data'    => $e->getData(),
                            ]
                        );

                        $retryCount++;
                    }
                    else
                    {
                        throw $e;
                    }
                }
                else
                {
                    throw $e;
                }
            }
        }

        if (isset($response[FTS\Constants::BODY][FTS\Constants::FUND_ACCOUNT_ID]) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR_FUND_ACCOUNT_CREATION_FAILED,
                null,
                [
                    'id' => $bankingAccount->getId()
                ],
                'FTS fund Account Id could not stored, Please try again!'
            );
        }

        return $response[FTS\Constants::BODY][FTS\Constants::FUND_ACCOUNT_ID];
    }

    public function updateAccountToProcessed(Entity $bankingAccount)
    {
        $channel = $bankingAccount->getChannel();

        switch ($channel)
        {
            case Channel::RBL:
                {
                    $attributes = [
                        Entity::STATUS                  => Status::PROCESSED,
                        Entity::BANK_INTERNAL_STATUS    => Gateway\Rbl\Status::CLOSED
                    ];

                    $this->updateRblBankingAccount($bankingAccount, $attributes);

                    break;
                }

            default:
                // not throwing any exception here, since this statement will be executed for valid channels only
                return;
        }
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
        $processor = $this->getProcessor($channel);

        $processor->addServiceablePincodes($pincodes);
    }

    public function deleteServiceablePincodes(array $pincodes, string $channel)
    {
        $processor = $this->getProcessor($channel);

        $processor->deleteServiceablePincodes($pincodes);
    }

    public function storeCredentials(Entity $bankingAccount, array $input)
    {
        $channel = $bankingAccount->getChannel();

        $processor = $this->getProcessor($channel);

        $attributes = $processor->storeCredentials($bankingAccount, $input);

        return $attributes;
    }

    public function activateAccount(Entity $bankingAccount, array $input)
    {
        $merchant = $bankingAccount->merchant;

        $mode = $this->app['rzp.mode'];

        // Create Banking Balance.
        $balance = (new Balance\Core)->createBalanceForCurrentAccount($merchant,
                                                                     Product::BANKING,
                                                                      $input,
                                                                      $mode);

        $this->checkMerchantIsActivatedBeforeAccountActivation($bankingAccount);

        $bankingAccount->setStatus(Status::ACTIVATED);

        $bankingAccount->balance()->associate($balance);

        $this->repo->saveOrFail($bankingAccount);

        // we do not want the merchant to get affected by failures in FTS service so handling
        // the same in try catch block
        try
        {
            $fundAccountId = $this->createOrFetchFtsFundAccountForMerchant($bankingAccount);

            $channel = $bankingAccount->getChannel();

            $processor = $this->getProcessor($channel);

            $content = $processor->generateRequestForSourceAccount($bankingAccount);

            $this->makeSourceAccountRequest($bankingAccount->getId(), $fundAccountId, $content);
        }
        catch (\Throwable $e)
        {
            $this->trace->info(
                TraceCode::FTS_FAILURE_EXCEPTION,
                [
                    'code'          => $e->getCode(),
                    'message'       => $e->getMessage(),
                    'type'          => optional($e->getType()),
                    'data'          => optional($e->getData()),
                ]);
        }

        return $bankingAccount;
    }

    public function makeSourceAccountRequest(string $id, string $ftsAccountId, array $content,
                                                string $product = 'PAYOUT', string $channel = 'ICICI')
    {
        $retryCount = 0;

        while (true)
        {
            try
            {
                $response = $this->app['fts_create_account']->createSourceAccount($id, $ftsAccountId, $content,
                    $product, $channel);

                return $this->checkSourceAccountResponseForError($response);

            }
            catch (RecordAlreadyExists $e)
            {
                $this->trace->info(TraceCode::FTS_DUPLICATE_TRANSFER_REQUEST_SENT,
                    [
                        'content' => $content,
                        'channel' => $channel,
                    ]);

                return null;
            }
            catch (\Throwable $e)
            {
                $errorCode = $e->getCode();

                if ($errorCode === ErrorCode::SERVER_ERROR_FTS_SERVICE_TIMEOUT)
                {
                    if ($retryCount < self::FTS_MAX_RETRY)
                    {
                        $this->trace->info(
                            TraceCode::FTS_SERVICE_RETRY,
                            [
                                'message' => $e->getMessage(),
                                'data'    => $e->getData(),
                            ]
                        );

                        $retryCount++;
                    }
                    else
                    {
                        throw $e;
                    }
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
        if (((isset($response[FTS\Constants::MESSAGE]) === true) and
            ($response[FTS\Constants::MESSAGE] === 'source account registered')))
        {
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
        $merchant = $bankingAccount->merchant;

        if ((isset($input[Entity::STATUS]) === true) and
            ($input[Entity::STATUS] === Status::ACTIVATED))
        {

            $merchantActivationStatus = $merchant->merchantDetail->getActivationStatus();

            if ($merchantActivationStatus !== Detail\Status::ACTIVATED)
            {
                throw new BadRequestValidationFailureException(
                    'Operation not allowed, merchant is not L2 activated',
                    null,
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

    protected function checkForVaultResponseErrors(array $response)
    {
        if ($response[CardVault::SUCCESS] === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR_VAULT_TOKENIZE_FAILED,
                null,
                [
                    'response' => $response
                ],
                'Merchant credentials could not be stored, Please try again!'
            );
        }
    }
}
