<?php

namespace RZP\Models\BankingAccount\BankLms;

use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\BankingAccount;
use RZP\Models\BankingAccount\BankLms\Fetch;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestException;
use RZP\Exception\InvalidArgumentException;
use RZP\Exception\BadRequestValidationFailureException;

class Repository extends BankingAccount\Repository
{
    protected $entity = 'banking_account_bank_lms';

    public function __construct()
    {
        parent::__construct();

        $this->setMerchantIdRequiredForMultipleFetch(false);
    }

    /**
     * @throws BadRequestException
     */
    public function fetchPartnerMerchantId(): string
    {
        $merchantIds = (new Feature\Repository())->findMerchantIdsHavingFeatures([Feature\Constants::RBL_BANK_LMS_DASHBOARD]);

        if (count($merchantIds) > 1)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR, null, null, "More than one CA Bank Partner Merchant Found");
        }
        return $merchantIds[0];
    }

    /**
     * @throws BadRequestException
     */
    public function fetchPartnerMerchant(): Merchant\Entity
    {
        $partnerMerchantId = $this->fetchPartnerMerchantId();

        return $this->repo->merchant->find($partnerMerchantId);
    }

    /**
     * @throws BadRequestValidationFailureException
     * @throws InvalidArgumentException
     */
    public function fetchMultipleEntityForBank(array $params): PublicCollection
    {
        return $this->fetch($params);
    }

    /**
     * @param string $id
     * @param array  $params
     *
     * @return PublicEntity
     */
    public function fetchEntityByIdForBank(string $id, array $params): PublicEntity
    {
        return  $this->findOrFailByPublicIdWithParams($id, $params);
    }


    /**
     * @throws BadRequestException
     */
    public function fetchSubMerchantForPartnerBank(Merchant\Entity $partnerBank, array $params = []): PublicCollection
    {
        $appIds = (new Merchant\Core())->getPartnerApplicationIds($partnerBank);

        return $this->repo->merchant->fetchSubmerchantsByAppIds($appIds, $params);
    }

    /**
     * @throws BadRequestException
     */
    public function fetchSubMerchantIdsForPartnerBank(Merchant\Entity $partnerBank): array
    {
        $subMerchants = $this->fetchSubMerchantForPartnerBank($partnerBank);

        return $subMerchants->pluck(Merchant\Entity::ID)->toArray();
    }

    /**
     * @throws BadRequestException
     */
    public function fetchSubMerchantForPartnerAndSubMerchantId(Merchant\Entity $partnerBank, Merchant\Entity $subMerchant): PublicCollection
    {
        return $this->fetchSubMerchantForPartnerBank($partnerBank, [PublicEntity::MERCHANT_ID => [$subMerchant->getId()]]);
    }

    public function addQueryParamFilterMerchants($query, $params)
    {
        $merchantId = $this->repo->banking_account->dbColumn(PublicEntity::MERCHANT_ID);

        $this->joinQueryActivationDetail($query);

        $query->select($this->dbColumn('*'));

        $filterMerchantIds = $params[Entity::FILTER_MERCHANTS];

        $query->whereIn($merchantId, $filterMerchantIds);
    }

    public function addQueryParamBankPocUserId($query, $params)
    {
        $bankPocUserId = $this->repo->banking_account_activation_detail->dbColumn(BankingAccount\Activation\Detail\Entity::BANK_POC_USER_ID);

        $this->joinQueryActivationDetail($query);

        $query->select($this->dbColumn('*'));

        $filterBankPocUserId = $params[Entity::BANK_POC_USER_ID];

        $query->where($bankPocUserId, $filterBankPocUserId);
    }

    public function addQueryParamLeadReceivedFromDate($query, $params)
    {
        return $query->whereExists(function ($q) use ($params) {

            $filterFromDate = $params[Fetch::LEAD_RECEIVED_FROM_DATE];
            $bankingAccountStateTable = $this->repo->banking_account_state->getTableName();
            $bankingAccountIdForeignColumn = $this->repo->banking_account_state->dbColumn(BankingAccount\State\Entity::BANKING_ACCOUNT_ID);
            $bankingAccountIdColumn = $this->repo->banking_account->dbColumn(Entity::ID);
            $bankingAccountStateStatusColumn = $this->repo->banking_account_state->dbColumn(BankingAccount\State\Entity::STATUS);
            $bankingAccountStateCreatedAtColumn = $this->repo->banking_account_state->dbColumn(BankingAccount\State\Entity::CREATED_AT);

            $q->select('*')
                ->from($bankingAccountStateTable)
                ->where($bankingAccountStateStatusColumn, '=', BankingAccount\Status::INITIATED)
                ->where($bankingAccountStateCreatedAtColumn, '>=', $filterFromDate)
                ->whereRaw($bankingAccountIdColumn.' = '.$bankingAccountIdForeignColumn);
        });
    }

    public function addQueryParamLeadReceivedToDate($query, $params)
    {
        return $query->whereExists(function ($q) use ($params) {

            $filterToDate = $params[Fetch::LEAD_RECEIVED_TO_DATE];
            $bankingAccountStateTable = $this->repo->banking_account_state->getTableName();
            $bankingAccountIdForeignColumn = $this->repo->banking_account_state->dbColumn(BankingAccount\State\Entity::BANKING_ACCOUNT_ID);
            $bankingAccountIdColumn = $this->repo->banking_account->dbColumn(Entity::ID);
            $bankingAccountStateStatusColumn = $this->repo->banking_account_state->dbColumn(BankingAccount\State\Entity::STATUS);
            $bankingAccountStateCreatedAtColumn = $this->repo->banking_account_state->dbColumn(BankingAccount\State\Entity::CREATED_AT);

            $q->select('*')
                ->from($bankingAccountStateTable)
                ->where($bankingAccountStateStatusColumn, '=', BankingAccount\Status::INITIATED)
                ->where($bankingAccountStateCreatedAtColumn, '<=', $filterToDate)
                ->whereRaw($bankingAccountIdColumn.' = '.$bankingAccountIdForeignColumn);
        });
    }

    public function addQueryParamActivationAccountType($query, $params)
    {
        $bankingAccountStateStatusColumn = $this->repo->banking_account_activation_detail->dbColumn(BankingAccount\Activation\Detail\Entity::ACCOUNT_TYPE);
        $filterActivationAccountType = $params[Fetch::ACTIVATION_ACCOUNT_TYPE];
        
        $this->joinQueryActivationDetail($query);
        $query->select($this->dbColumn('*'));

        return $query->where($bankingAccountStateStatusColumn, $filterActivationAccountType);
    }
}
