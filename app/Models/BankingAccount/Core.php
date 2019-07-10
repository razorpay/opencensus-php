<?php

namespace RZP\Models\BankingAccount;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Services\FTS;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Services\CardVault;
use RZP\Models\Merchant\Detail;
use RZP\Exception\LogicException;
use RZP\Models\Settlement\Channel;
use RZP\Exception\BadRequestException;
use RZP\Models\BankingAccount\Gateway;
use RZP\Exception\BadRequestValidationFailureException;

class Core extends Base\Core
{
    protected $processor;

    public function __construct()
    {
        parent::__construct();

        $this->config = $this->app['config']->get('banking_account');
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

        $processor = $this->getProcessor($channel);

        $bankContent = $processor->validateAndPreProcessInputForAccountCreation($input);

        $input = array_merge($input, $bankContent);

        $bankingAccount = new Entity;

        $bankingAccount->build($input);

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

        $this->checkMerchantIsActivatedBeforeAccountActivation($bankingAccount, $input);

        $bankingAccount = $bankingAccount->edit($input);

        $this->repo->saveOrFail($bankingAccount);

        return $bankingAccount;
    }

    public function createMerchantTokenForRbl(Entity $bankingAccount, array $input)
    {
        (new Validator)->validateInput('rbl_create_merchant_token', $input);

        $attributesToSave = RblFields::getRblAttributesToSave($input);

        $bankingAccount->edit($attributesToSave);

        $this->repo->saveOrFail($bankingAccount);

        return null;
    }

    public function createOrFetchFtsFundAccountForMerchant(Entity $bankingAccount)
    {
        $fundAccountId = $bankingAccount->getFtsFundAccountId();

        if ($fundAccountId !== null)
        {
            return $fundAccountId;
        }

        $response = $this->app['fts_create_account']->createFundAccount($bankingAccount->getId(),
                                                                        Constants\Entity::BANKING_ACCOUNT,
                                                                        'payout');

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

    public function createMerchantSourceAccountForRbl(Entity $bankingAccount, string $ftsFundAccountId)
    {
        $rbl = $this->config['rbl'];

        $credentials = [
            RblFields::USERNAME                  => $rbl[RblFields::USERNAME],
            RblFields::PASSWORD                  => $rbl[RblFields::PASSWORD],
            RblFields::CLIENT_ID                 => $rbl[RblFields::CLIENT_ID],
            RblFields::CLIENT_SECRET             => $rbl[RblFields::CLIENT_SECRET],
            RblFields::SUBCORP_ID                => $bankingAccount->getUsername(),
            RblFields::SUBCORP_USER_ID           => $bankingAccount->getPassword(),
            RblFields::SUBCORP_USER_PASSWORD     => $bankingAccount->getReference1(),
       ];

       $mozartIdentifier = $rbl[RblFields::MOZART_IDENTIFIER];

       $body = [
           FTS\Constants::CREDENTIALS       => $credentials,
           FTS\Constants::MOZART_IDENTIFIER => $mozartIdentifier
       ];

       $this->makeSourceAccountRequest($bankingAccount->getId(), $ftsFundAccountId, $body);
    }

    public function tokenizeBankingAccountCredentials(string $element)
    {
        $request = [
            'namespace' => Entity::VAULT_NAMESPACE,
            'secret'    => $element
        ];

        $response = $this->app['card.cardVault']->createVaultToken($request);

        $this->checkForVaultResponseErrors($response);

        return $response[CardVault::TOKEN];
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

                    // TODO: Fix this undefined function!
                    $this->updateRblBankingAccount($bankingAccount, $attributes);

                    break;
                }

            default:
                throw new LogicException(
                    'Attempt to update account to processed for an Invalid channel',
                    null,
                    [
                        'channel'               => $channel,
                        'banking_account_id'    => $bankingAccount->getId(),
                    ]);
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

    protected function makeSourceAccountRequest(string $id, string $ftsAccountId, array $content,
                                                string $product = 'PAYOUT', string $channel = 'ICICI')
    {
        $response = $this->app['fts_create_account']->createSourceAccount($id, $ftsAccountId, $content,
                                                                          $product, $channel);

       $this->checkSourceAccountResponseForError($response);
    }

    protected function checkSourceAccountResponseForError(array $response)
    {
        if (((isset($response[FTS\Constants::MESSAGE]) === true) and
            ($response[FTS\Constants::MESSAGE] === 'source account registered')) or
            (isset($response[FTS\Constants::INTERNAL_ERROR][FTS\Constants::CODE]) === true) and
            ($response[FTS\Constants::INTERNAL_ERROR][FTS\Constants::CODE] === 'RECORD_ALREADY_EXIST'))
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
     * the status of current account to processed. This to avoid cases of manual error by Bizops.
     *
     * @param Entity $bankingAccount
     * @param array $input
     *
     * @throws BadRequestValidationFailureException
     */
    protected function checkMerchantIsActivatedBeforeAccountActivation(Entity $bankingAccount, array $input)
    {
        if ((isset($input[Entity::STATUS]) === true) and
            ($input[Entity::STATUS] === Status::ACTIVATED))
        {
            $merchant = $bankingAccount->merchant;

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
