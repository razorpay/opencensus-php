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
use RZP\Models\BankAccount\Fetch;
use RZP\Models\BankingAccount\Activation\Notification\Event;

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
        $input = $this->addMandatoryFilters($partnerBankMerchant, $input);

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
        return (new BankingAccount\Activation\Comment\Service())->fetchMultipleEntity($bankingAccount, $params);
    }

    /**
     * @param BankingAccount\Entity $bankingAccount
     * @param array                 $params
     *
     * @return PublicCollection
     * @throws BadRequestValidationFailureException
     * @throws InvalidArgumentException
     */
    public function createBankingAccountsActivationComment(BankingAccount\Entity $bankingAccount, array $input): array
    {
        return (new BankingAccount\Activation\Comment\Service())->createForBankingAccount($bankingAccount->getPublicId(), $input);
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
     * @param Merchant\Entity       $partnerBank
     * @param BankingAccount\Entity $bankingAccount
     *
     * @return array
     * @throws BadRequestException
     * @throws BadRequestValidationFailureException
     * @throws LogicException
     */
    public function attachCaApplicationMerchantToBankPartner(Merchant\Entity $partnerBank, BankingAccount\Entity $bankingAccount): array
    {
        $response =  (new Merchant\Core())->attachSubMerchantToBankCaPartner($partnerBank, $bankingAccount->merchant);

        // $this->notifier->notify($bankingAccount->toArray(), Event::BANK_PARTNER_ASSIGNED, Event::INFO, [Constants::PARTNER_MERCHANT_ID => $partnerBank->getId()]);

        return $response;
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
    private function addMandatoryFilters(Merchant\Entity $partnerBank, array $params): array
    {
        $subMerchantIds = $this->repository->fetchSubMerchantIdsForPartnerBank($partnerBank);

        $params[Entity::FILTER_MERCHANTS] = $subMerchantIds;

        $params[BankingAccount\Entity::ACCOUNT_TYPE] = BankingAccount\AccountType::CURRENT;

        $params[BankingAccount\Entity::CHANNEL] = BankingAccount\Channel::RBL;

        return $params;
    }

    /**
     * @param Merchant\Entity $partnerBank
     * @param array           $input
     *
     * @return array
     * @throws BadRequestException
     * @throws BadRequestValidationFailureException
     */
    public function downloadActivationMis(Merchant\Entity $partnerBank, array $input): array
    {
        $input =  $this->addMandatoryFilters($partnerBank, $input);

        $input[Fetch::COUNT] = 100;

        $misType = array_pull($input, 'mis_type');

        $misProcessor = BankingAccount\Activation\MIS\Factory::getProcessor($misType, $input, 'banking_account_bank_lms');

        return $misProcessor->generate();
    }

    /**
     * @param Merchant\Entity $partnerBank
     * @param array           $input
     *
     * @return array
     * @throws BadRequestException
     * @throws BadRequestValidationFailureException
     */
    public function sendActivationMisReport(Merchant\Entity $partnerBank, array $input)
    {
        $input =  $this->addMandatoryFilters($partnerBank, $input);

        $misProcessor = BankingAccount\Activation\MIS\Factory::getProcessor(BankingAccount\Activation\MIS\Factory::LEADS_REPORT, $input);

        return $misProcessor->generate();
    }

    public function assignBankPartnerPocToApplication(BankingAccount\Entity $bankingAccount, $bankPocUserId)
    {
        (new BankingAccount\Activation\Detail\Core())->assignBankPartnerPocToApplication($bankingAccount->bankingAccountActivationDetails, $bankPocUserId);

        if ($bankingAccount->getStatus() === BankingAccount\Status::INITIATED && $bankingAccount->usingNewStates())
        {
            $input = [
                BankingAccount\Entity::STATUS => BankingAccount\Status::VERIFICATION_CALL,
                BankingAccount\Entity::SUB_STATUS => BankingAccount\Status::IN_PROCESSING,
            ];

            $user = $this->app['basicauth']->getUser();

            // Will be used with experimentation changes
            (new BankingAccount\Core())->updateBankingAccount($bankingAccount, $input, $user, false, false, true);
        }

        $this->notifier->notify($bankingAccount->toArray(), Event::BANK_PARTNER_POC_ASSIGNED);

        return $bankingAccount->reload();
    }

}
