<?php

namespace RZP\Models\Merchant\Account;

use RZP\Models\Merchant\Detail as MerchantDetail;

class Helper
{
    protected static $instance;

    protected  $core;

    public  function  __construct()
    {
        $this->core = New MerchantDetail\Core;
    }

    /**
     * Helps in maintaining the class as Singleton
     *
     * @return Helper
     */
    public static function get(): Helper
    {
        if (self::$instance !== null)
        {
            return self::$instance;
        }

        self::$instance = new Helper();

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
    public function computePublicArrayAttributes(Entity $entity, array & $response)
    {
        $merchantDetails = $entity->merchantDetail;

        $detailsResponse = (new MerchantDetail\Core)->createResponse($merchantDetails);

        $activationDetails = Entity::ACTIVATION_DETAILS;

        $response[$activationDetails][Entity::CAN_SUBMIT] = $detailsResponse[MerchantDetail\Entity::CAN_SUBMIT];

        $verificationDetails = $detailsResponse[MerchantDetail\Entity::VERIFICATION];

        $fieldsPending = [];

        if (isset($verificationDetails[MerchantDetail\Entity::FIELDS_PENDING]) === true)
        {
            $fieldsPending = $verificationDetails[MerchantDetail\Entity::FIELDS_PENDING];
        }

        $response[$activationDetails][Entity::FIELDS_PENDING] = $fieldsPending;

        return $response;
    }

}