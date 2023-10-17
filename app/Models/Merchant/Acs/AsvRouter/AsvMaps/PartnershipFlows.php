<?php

namespace RZP\Models\Merchant\Acs\AsvRouter\AsvMaps;

use RZP\Models\Merchant\Email\Repository as MerchantEmailRepository;
use RZP\Models\Merchant\Website\Repository as MerchantWebsiteRepository;
use RZP\Models\Merchant\Document\Repository as MerchantDocumentRepository;
use RZP\Models\Merchant\BusinessDetail\Repository as BusinessDetailRepository;
use RZP\Models\Merchant\Detail\Repository as MerchantDetailRepository;
use RZP\Models\Merchant\Repository as MerchantRepository;
use RZP\Models\Address\Repository as AddressRepository;



final class PartnershipFlows {

    private array $MAP;

    public static array $PARTNERSHIP_FLOWS = array(
        "account_create_v2" => true,
        "stakeholder_create_v2" => true,
        "link_stakeholder_documents_v2" => true,
        "product_config_update_v2" => true,
        "stakeholder_update_v2" => true,
        "link_account_documents_v2" => true,
        "account_edit_v2" => true,
        "product_config_create_v2" => true,
        "account_delete_v2" => true,
        "account_edit" => true,
        "merchant_sub_create" => true,
        "Update_partner_type" => true,
        "partner_activation_save" => true,
    );

    public function __construct()
    {
        /*
         *  Implemented like this so that we can change/modify this during runtime for tests.
         */
        $this->MAP = self::$PARTNERSHIP_FLOWS;
    }

    /**
     * @throws \Exception
     */
    public function checkIfPartnerShipFlow($flow): bool {

        if (array_key_exists($flow, $this->MAP)) {
            return $this->MAP[$flow];
        }

        return false;
    }
}
