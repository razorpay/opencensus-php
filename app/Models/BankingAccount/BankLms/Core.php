<?php

namespace RZP\Models\BankingAccount\BankLms;

use RZP\Models\Merchant;
use RZP\Models\BankingAccount;
use RZP\Exception\LogicException;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestException;
use RZP\Exception\InvalidArgumentException;
use RZP\Exception\BadRequestValidationFailureException;

class Core extends BankingAccount\Core
{
    protected $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new Repository();
    }

    /**
     * @throws BadRequestValidationFailureException
     * @throws InvalidArgumentException
     * @throws BadRequestException
     */
    public function fetchMultipleBankingAccountEntity(array $input, merchant\Entity $partnerBankMerchant): PublicCollection
    {
        $input = $this->AddMandatoryFilters($partnerBankMerchant, $input);

        return $this->repository->fetchMultipleEntityForBank($input);
    }

    public function fetchBankingAccountById(string $bankingAccountId, array $input): PublicEntity
    {
        return $this->repository->fetchEntityByIdForBank($bankingAccountId, $input);
    }

    /**
     * @param BankingAccount\Entity $bankingAccount
     * @param array                 $params
     *
     * @return PublicCollection
     * @throws BadRequestValidationFailureException
     * @throws InvalidArgumentException
     */
    public function fetchBankingAccountsActivationCommentById(BankingAccount\Entity $bankingAccount, array $params): PublicCollection
    {
        // Todo: Add necessary Filters

        return (new BankingAccount\Activation\Comment\Service())->fetchMultipleEntity($bankingAccount, $params);
    }

    /**
     * @param $input
     *
     * @return array
     */
    public function transformNormalMerchantToBankPartner($input): array
    {
        return (new Merchant\Service())->makeMerchantAsBankCaOnboardingPartnerType($input);
    }


    /**
     * @param Merchant\Entity $partnerBank
     * @param Merchant\Entity $subMerchant
     *
     * @return array
     * @throws LogicException
     * @throws BadRequestException
     * @throws BadRequestValidationFailureException
     */
    public function attachCaApplicationMerchantToBankPartner(Merchant\Entity $partnerBank, Merchant\Entity $subMerchant): array
    {
        return (new Merchant\Core())->attachSubMerchantToBankCaPartner($partnerBank, $subMerchant);
    }

    /**
     * @throws BadRequestException
     */
    public function detachCaApplicationMerchantFromBankPartner(Merchant\Entity $partnerBank, Merchant\Entity $subMerchant)
    {
        (new Merchant\Core())->submerchantDelink($partnerBank, $subMerchant);
    }

    /**
     * @param Merchant\Entity $partnerBank
     * @param array           $params
     *
     * @return array
     * @throws BadRequestException
     */
    private function AddMandatoryFilters(Merchant\Entity $partnerBank, array $params): array
    {
        $subMerchantIds = $this->repository->fetchSubMerchantIdsForPartnerBank($partnerBank);

        $params[Entity::FILTER_MERCHANTS] = $subMerchantIds;

        $params[BankingAccount\Entity::ACCOUNT_TYPE] = BankingAccount\AccountType::CURRENT;

        $params[BankingAccount\Entity::CHANNEL] = BankingAccount\Channel::RBL;

        return $params;
    }

    public function assignBankPartnerPocToApplication($bankingAccount, $bankPocUserId)
    {
        (new BankingAccount\Activation\Detail\Core())->assignBankPartnerPocToApplication($bankingAccount->bankingAccountActivationDetails, $bankPocUserId);

        return $bankingAccount->reload();
    }

}
