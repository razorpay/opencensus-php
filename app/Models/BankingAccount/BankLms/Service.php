<?php

namespace RZP\Models\BankingAccount\BankLms;

use RZP\Exception\LogicException;
use RZP\Models\BankingAccount;
use RZP\Exception\BadRequestException;
use RZP\Exception\InvalidArgumentException;
use RZP\Exception\BadRequestValidationFailureException;

class Service extends BankingAccount\Service
{
    protected $core;

    protected $validator;

    protected $repository;

    protected $partnerBankMerchant;

    /**
     * @throws BadRequestException
     */
    public function __construct($pincodeSearch = null, $core = null)
    {
        parent::__construct($pincodeSearch, $core);

        $this->core = new Core();

        $this->validator = new Validator();

        $partnerBankMerchantId = $this->validator->validateOnlyOneCaBankPartnerAndReturn();

        $this->partnerBankMerchant = is_null($partnerBankMerchantId) ? null : $this->repo->merchant->findByPublicId($partnerBankMerchantId);

        $this->repository = new Repository();
    }

    /**
     * @param $input
     *
     * @return array
     */
    public function transformNormalMerchantToBankPartner($input): array
    {
        $this->validator->validateInput(Validator::CREATE_BANK_CA_ONBOARDING_PARTNER_TYPE, $input);

        return $this->core->transformNormalMerchantToBankPartner($input);
    }

    /**
     * @throws BadRequestValidationFailureException
     * @throws LogicException
     * @throws BadRequestException
     * To be used by Admin
     */
    public function attachCaApplicationMerchantToBankPartnerBulk(array $input): array
    {
        foreach ($input['banking_account_ids'] as $banking_account_id)
        {
            $this->attachCaApplicationMerchantToBankPartner([BankingAccount\Entity::BANKING_ACCOUNT_ID => $banking_account_id]);
        }
        return ['success' => true];
    }

    /**
     * @throws LogicException
     * @throws BadRequestException
     * @throws BadRequestValidationFailureException
     */
    public function attachCaApplicationMerchantToBankPartner(array $input): array
    {
        if ($this->partnerBankMerchant === null)
        {
            return ['success' => false];
        }

        $this->validator->validateInput(Validator::ATTACH_CA_MERCHANT_TO_BANK_PARTNER, $input);

        $bankingAccount = $this->repo->banking_account->findByPublicId($input[BankingAccount\Entity::BANKING_ACCOUNT_ID]);

        return $this->core->attachCaApplicationMerchantToBankPartner($this->partnerBankMerchant, $bankingAccount);
    }

    /**
     * @param array $input
     *
     * @return void
     * @throws BadRequestException
     */
    public function detachCaApplicationMerchantFromBankPartner(array $input)
    {
        if ($this->partnerBankMerchant === null)
        {
            return;
        }

        $this->validator->validateInput(Validator::DETACH_CA_MERCHANT_FROM_BANK_PARTNER, $input);

        $bankingAccount = $this->repo->banking_account->findByPublicId($input[BankingAccount\Entity::BANKING_ACCOUNT_ID]);

        //Todo: Check if Bank user needs to be removed too.

        $this->core->detachCaApplicationMerchantFromBankPartner($this->partnerBankMerchant, $bankingAccount->merchant);
    }


    /**
     * @param string $bankingAccountId
     * @param array  $input
     *
     * @return mixed
     * @throws BadRequestException
     */
    public function assignBankPartnerPocToApplication(string $bankingAccountId, array $input)
    {
        $this->validator->validateInput(Validator::ASSIGN_BANK_PARTNER_POC_TO_APPLICATION, $input);

        $bankingAccount = $this->repo->banking_account->findByPublicId($bankingAccountId);

        $this->validator->validateMerchantIsAttachedToPartner($bankingAccount->merchant, $this->partnerBankMerchant);

        $bankPocUserId = $input[BankingAccount\Activation\Detail\Entity::BANK_POC_USER_ID];

        $this->validator->validateUserBelongsToPartnerBankMerchant($bankPocUserId, $this->partnerBankMerchant);

        $bankingAccount = $this->core->assignBankPartnerPocToApplication($bankingAccount, $bankPocUserId);

        return $bankingAccount->toArrayCaPartnerBankPoc();
    }

    /**
     * @throws BadRequestValidationFailureException
     * @throws InvalidArgumentException
     * @throws BadRequestException
     */
    public function fetchMultipleBankingAccountEntity(array $input): array
    {
        $entities = $this->core->fetchMultipleBankingAccountEntity($input, $this->partnerBankMerchant);

        // No Requirement from product returning Bank Poc response
        return $entities->toArrayCaPartnerBankPoc();
    }

    /**
     * @param string $bankingAccountId
     * @param array  $input
     *
     * @return array
     * @throws BadRequestException
     */
    public function fetchBankingAccountById(string $bankingAccountId, array $input): array
    {
        $bankingAccount = $this->repo->banking_account->findByPublicId($bankingAccountId);

        $this->validator->validateMerchantIsAttachedToPartner($bankingAccount->merchant, $this->partnerBankMerchant);

        $entity = $this->core->fetchBankingAccountById($bankingAccountId, $input);

        return $entity->toArrayCaPartnerBankPoc();
    }

    /**
     * @throws BadRequestException
     * @throws InvalidArgumentException|BadRequestValidationFailureException
     */
    public function fetchBankingAccountsActivationCommentById(string $bankingAccountId, array $input): array
    {
        $bankingAccount = $this->repo->banking_account->findByPublicId($bankingAccountId);

        $this->validator->validateMerchantIsAttachedToPartner($bankingAccount->merchant, $this->partnerBankMerchant);

        $entities = $this->core->fetchBankingAccountsActivationCommentById($bankingAccount, $input);

        return $entities->toArrayCaPartnerBankPoc();
    }

    /**
     * @param array $input
     *
     * @return array
     * @throws BadRequestException|BadRequestValidationFailureException
     */
    public function downloadActivationMis(array $input): array
    {
        $this->validator->validateInput(Validator::DOWNLOAD_MIS_FROM_PARTNER_BANK, $input);

        return $this->core->downloadActivationMis($this->partnerBankMerchant, $input);
    }

}
