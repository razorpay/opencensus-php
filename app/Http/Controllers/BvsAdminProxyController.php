<?php

namespace RZP\Http\Controllers;

use RZP\Models\Admin\Permission\Name;

class BvsAdminProxyController extends BaseProxyController
{
    const LIST_VALIDATIONS_FOR_MERCHANT = 'ListValidationsForMerchant';
    const GET_VALIDATION_WITH_DETAILS = 'GetValidationWithDetails';

    const ROUTES_URL_MAP    = [
        self::LIST_VALIDATIONS_FOR_MERCHANT => "/twirp\/platform.bvs.ownerdetails.v1.VerificiationLifecycleAPI\/ListValidations/",
        self::GET_VALIDATION_WITH_DETAILS => "/twirp\/platform.bvs.validation.v2.ValidationAPI\/GetValidationWithDetails/"
    ];

    const ADMIN_ROUTES = [
        self::LIST_VALIDATIONS_FOR_MERCHANT,
        self::GET_VALIDATION_WITH_DETAILS
    ];

    const ADMIN_ROUTES_VS_PERMISSION   = [
        self::LIST_VALIDATIONS_FOR_MERCHANT   => Name::VIEW_ALL_ENTITY,
        self::GET_VALIDATION_WITH_DETAILS   => Name::VIEW_ACTIVATION_FORM,
    ];

    public function __construct()
    {
        parent::__construct("business_verification_service");
        $this->registerRoutesMap(self::ROUTES_URL_MAP);
        $this->registerAdminRoutes(self::ADMIN_ROUTES, self::ADMIN_ROUTES_VS_PERMISSION);
        $this->setDefaultTimeout(30);
    }

    protected function getAuthorizationHeader()
    {
        return 'Basic ' . base64_encode($this->serviceConfig['user'] . ':' . $this->serviceConfig['password']);
    }

    protected function getBaseUrl(): string
    {
        return $this->serviceConfig['host'];
    }
}
