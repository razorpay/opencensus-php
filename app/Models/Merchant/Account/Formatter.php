<?php

namespace RZP\Models\Merchant\Account;

use RZP\Models\Merchant\Detail as MerchantDetail;
use RZP\Models\Merchant\BvsValidation\Constants as BvsConstants;

/**
 * Class Formatter
 *
 * @todo: Remove the implementation of this class
 * Core functions should not be called from Entity or any other helper class
 * used through the entity. Currently, it has been implemented, as some of the
 * attributes are computed on the fly, using the Core class.
 *
 * @package RZP\Models\Merchant\Account
 */
class Formatter
{
    protected static $instance;

    protected $core;

    public  function  __construct()
    {
        $this->core = new MerchantDetail\Core;
    }

    /**
     * Helps in maintaining the class as Singleton
     *
     * @return Formatter
     */
    public static function get(): Formatter
    {
        if (self::$instance !== null)
        {
            return self::$instance;
        }

        self::$instance = new Formatter();

        return self::$instance;
    }

    /**
     * Returns custom public attributes.
     * This cannot be handled in the Entity class as some of the params are
     * computed on the fly, using the Core class.
     *
     * @param Entity $entity
     * @param        $response
     *
     * @return array
     */
    public function computeAndSetAdditionalPublicAttributes(Entity $entity, array & $response)
    {
        $merchantDetails = $entity->merchantDetail;

        $activationDetails = Entity::ACTIVATION_DETAILS;

        if ($entity->isSuspended() === true)
        {
            $response[$activationDetails][Entity::CAN_SUBMIT] = false;

            $response[$activationDetails][BvsConstants::BANK_DETAILS_VERIFICATION_ERROR] = null;

            $response[$activationDetails][Entity::FIELDS_PENDING] = [];

            return $response;
        }

        $detailsResponse = $this->core->createResponse($merchantDetails);

        $response[$activationDetails][Entity::CAN_SUBMIT] = $detailsResponse[MerchantDetail\Entity::CAN_SUBMIT];

        $verificationDetails = $detailsResponse[MerchantDetail\Entity::VERIFICATION];

        $fieldsPending = [];

        if (isset($verificationDetails[MerchantDetail\Entity::REQUIRED_FIELDS]) === true)
        {
            $fieldsPending = $verificationDetails[MerchantDetail\Entity::REQUIRED_FIELDS];
        }

        $bankDetailsVerificationError = $this->core->getBankDetailsVerificationError($merchantDetails);

        $response[$activationDetails][BvsConstants::BANK_DETAILS_VERIFICATION_ERROR] = $bankDetailsVerificationError;

        $response[$activationDetails][Entity::FIELDS_PENDING] = $fieldsPending;

        return $response;
    }

}
