<?php

namespace RZP\Models\BankingAccount\BankLms;

use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\BankingAccount;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestException;
use RZP\Exception\InvalidArgumentException;
use RZP\Models\BankingAccount\BankLms\Fetch;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;
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
        if ((new AsvRouter())->shouldRouteFilterToAsv(__FUNCTION__))
        {
            $appIds = (new Merchant\Core())->getPartnerApplicationIds($partnerBank);

            return $this->repo->merchant_access_map->fetchSubmerchantIdsFromAppIds($appIds);
        }

        $subMerchants = $this->fetchSubMerchantForPartnerBank($partnerBank);

        return $subMerchants->pluck(Merchant\Entity::ID)->toArray();
    }

    /**
     * @throws BadRequestException
     */
    public function fetchSubMerchantForPartnerAndSubMerchantId(Merchant\Entity $partnerBank, Merchant\Entity $subMerchant): array
    {
        if ((new AsvRouter())->shouldRouteFilterToAsv(__FUNCTION__))
        {
            $appIds = (new Merchant\Core())->getPartnerApplicationIds($partnerBank);
            return $this->repo->merchant_access_map->filterSubMerchantsIdsMappedToAppId($appIds, [$subMerchant->getId()]);
        }

        $merchants = $this->fetchSubMerchantForPartnerBank(
            $partnerBank,
            [PublicEntity::MERCHANT_ID => [$subMerchant->getId()]]
        );

        return $merchants->pluck(Merchant\Entity::ID)->toArray();
    }
}
