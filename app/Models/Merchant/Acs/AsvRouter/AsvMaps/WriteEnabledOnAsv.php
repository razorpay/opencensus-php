<?php

namespace RZP\Models\Merchant\Acs\AsvRouter\AsvMaps;

use RZP\Models\Merchant\Email\Repository as MerchantEmailRepository;
use RZP\Models\Merchant\Website\Repository as MerchantWebsiteRepository;
use RZP\Models\Merchant\Document\Repository as MerchantDocumentRepository;
use RZP\Models\Merchant\BusinessDetail\Repository as BusinessDetailRepository;
use RZP\Models\Merchant\Detail\Repository as MerchantDetailRepository;
use RZP\Models\Merchant\Repository as MerchantRepository;
use RZP\Models\Address\Repository as AddressRepository;
use RZP\Models\Merchant\Stakeholder\Repository as StakeholderRepository;
use RZP\Models\Merchant\Account\Repository as AccountRepository;


final class WriteEnabledOnAsv {

    // this is a map that, maps repo class to SDK wrapper class and returns the wrapper class instance

    private array $MAP;

    public static array $SAVE_OR_FAIL = array(
        MerchantWebsiteRepository::class => true,
        MerchantEmailRepository::class => true,
        BusinessDetailRepository::class => true,
        MerchantDocumentRepository::class => true,
        MerchantDetailRepository::class => true,
        MerchantRepository::class => true,
        AddressRepository::class => true,
        StakeholderRepository::class => true,
        AccountRepository::class => true

    );

    public static array $DELETE_OR_FAIL = array(
        MerchantDocumentRepository::class => true,
    );

    public function __construct()
    {
        $this->MAP = array(
            FunctionConstant::SAVE_OR_FAIL => self::$SAVE_OR_FAIL,
            FunctionConstant::DELETE_OR_FAIL => self::$DELETE_OR_FAIL,
        );
    }

    /**
     * @throws \Exception
     */
    public function checkIfWriteEnabled($repoClass, $functionName): bool {

        if (array_key_exists($functionName, $this->MAP)) {
            $map = $this->MAP[$functionName];
            if (array_key_exists($repoClass, $map)) {
                return $map[$repoClass];
            }
        }

        return false;
    }
}
