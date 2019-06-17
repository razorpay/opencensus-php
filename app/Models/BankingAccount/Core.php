<?php

namespace RZP\Models\BankingAccount;

use Redis;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Services\FTS;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Services\CardVault;
use RZP\Models\Merchant\Detail;
use RZP\Models\Settlement\Channel;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;

class Core extends Base\Core
{
    const RBL_PINCODES_REDIS_KEY = 'rbl_pincode_set';

    const VAULT_NAMESPACE = 'banking_accounts_creds';

    public function __construct()
    {
        parent::__construct();

        $this->config = $this->app['config']->get('banking_account');
    }

    public function createRblBankingAccount(array $input, Merchant\Entity $merchant): Entity
    {
        // TODO: Validate if account does not already exist for the merchant

        $status = $this->getRblAvailabilityStatus($input);

        $bankingAccount = new Entity;

        $bankingAccount->build($input);

        $bankingAccount->merchant()->associate($merchant);

        $bankingAccount->setStatus($status);

        $this->repo->saveOrFail($bankingAccount);

        return $bankingAccount;
    }

    public function updateRblBankingAccount(Entity $bankingAccount, array $input): Entity
    {
        (new Validator)->validateInput('rbl_update', $input);

        $this->checkRblToInternalStatusMapping($input);

        $this->checkMerchantIsActivated($bankingAccount);

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

    public function createFtsFundAccountForMerchant(Entity $bankingAccount)
    {
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
            'namespace' => self::VAULT_NAMESPACE,
            'secret'    => $element
        ];

        $response = $this->app['card.cardVault']->createVaultToken($request);

        $this->checkForVaultResponseErrors($response);

        return $response[CardVault::TOKEN];
    }

    public function updateAccountToProcessed(Entity $bankingAccount, string $channel)
    {
        switch ($channel)
        {
            case Channel::RBL:
                {
                    $attributes = [
                        Entity::STATUS                  => Status::PROCESSED,
                        Entity::BANK_INTERNAL_STATUS    => RblStatus::CLOSED
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

    protected function getRblAvailabilityStatus(array $input): string
    {
        (new Validator)->validateInput('rbl_availability', $input);

        $isServiceable = $this->isPincodeRblServiceable($input[Entity::PINCODE]);

        $status = ($isServiceable === true) ? Status::CREATED : Status::UNSERVICEABLE;

        return $status;
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

    protected function isPincodeRblServiceable(string $pincode): bool
    {
        $redis = Redis::connection();

        $isAvailable = $redis->sismember(self::RBL_PINCODES_REDIS_KEY, $pincode);

        return (bool) $isAvailable;
    }

    protected function checkRblToInternalStatusMapping(array $input)
    {
        if (isset($input[Entity::BANK_INTERNAL_STATUS]) === false)
        {
            return;
        }

        $bankInternalStatus = $input[Entity::BANK_INTERNAL_STATUS];
        $status             = $input[Entity::STATUS];

        RblStatus::validate($bankInternalStatus);
        RblStatus::validateInternalBankStatusToStatus($bankInternalStatus, $status);
    }

    /**
     * This method is responsible for checking that unless the merchant is L2 activated, no one can update
     * the status of RBL current account to processed. This to avoid cases of manual error by Bizops.
     *
     * @param Entity $bankingAccount
     *
     * @throws BadRequestValidationFailureException
     */
    protected function checkMerchantIsActivated(Entity $bankingAccount)
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

    public function getBankingAccountEntity(string $id)
    {
        return $this->repo->banking_account->findOrFailPublic($id);
    }
}
