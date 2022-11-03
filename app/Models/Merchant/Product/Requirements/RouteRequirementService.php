<?php

namespace RZP\Models\Merchant\Product\Requirements;

use RZP\Models\Merchant;
use RZP\Constants\Entity;
use RZP\Models\Merchant\Product;
use RZP\Exception\LogicException;

class RouteRequirementService extends PaymentProductsBaseService
{
    public function __construct()
    {
        parent::__construct();
    }

    //Override requirements if any
    /**
     * This is the public function exposed to fetch all the requirements for a merchant
     * Requirements are calculated based on [business_type, business_category, business_subcategory]
     * If these basic requirements are not provided by the customer, we ask for them directly through requirements
     * If basic requirements are provided, calculate and provide requirements as per these basic requirements.
     *
     * @param Merchant\Entity $merchant
     *
     * @param Product\Entity  $merchantProduct
     *
     * @return array
     * @throws LogicException
     */
    public function fetchRequirements(Merchant\Entity $merchant, Product\Entity $merchantProduct)
    {
        $merchantDetails = $merchant->merchantDetail;

        $baseRequirements = [];

        foreach (Constants::LINKED_ACCOUNT_BUSINESS_REQUIREMENT_FIELDS as $field)
        {
            if (empty($merchantDetails->getAttribute($field)) === true)
            {
                $requirement = [];

                $requirement[Constants::FIELD_REFERENCE] = $this->getFieldReference($field, Entity::MERCHANT);

                $requirement[Constants::RESOLUTION_URL] = Constants::ENTITY_RESOLUTION_URL_MAPPING[Entity::MERCHANT][Constants::FIELD];

                $requirement[Constants::STATUS] = Constants::REQUIRED;

                $requirement[Constants::REASON_CODE] = Constants::FIELD_MISSING;

                $baseRequirements[] = $requirement;
            }
        }

        if (count($baseRequirements) > 0)
        {
            $baseRequirements = $this->updateResolutionUrl($merchantDetails, $merchantProduct, $baseRequirements);

            return $baseRequirements;
        }

        else
        {
            [$requirements, $optionalRequirements] = $this->getRequirements($merchant, $merchantDetails, $merchantProduct);

            $allRequirements = array_merge($requirements, $optionalRequirements);

            $allRequirements = $this->updateResolutionUrl($merchantDetails, $merchantProduct, $allRequirements);

            $this->addInstantActivationAlertInRequirementIfApplicable($merchant, $allRequirements);

            return $allRequirements;
        }

    }
}
