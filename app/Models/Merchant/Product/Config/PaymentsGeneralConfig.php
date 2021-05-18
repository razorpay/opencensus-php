<?php

namespace RZP\Models\Merchant\Product\Config;

use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Merchant;
use RZP\Models\Settlement;
use RZP\Models\Payment\Config;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Product;
use RZP\Models\Merchant\Product\Util;
use RZP\Models\Merchant\Product\Requirements;

class PaymentsGeneralConfig extends Base\Service
{
    const PAYMENT_CAPTURE_CONFIGS = ['late_auth'];

    /**
     * @var Config\Service
     */
    private $paymentConfigService;

    /**
     * @var Merchant\Service
     */
    private $merchantService;

    /**
     * @var User\Service
     */
    private $userService;

    /**
     * @var Settlement\Service
     */
    private $settlementService;

    /**
     * @var Detail\Core
     */
    private $merchantDetailCore;

    /**
     * @var Requirements\BaseProcessor
     */
    private $requirementProcessor;


    public function __construct()
    {
        $this->paymentConfigService = new Config\Service();

        $this->merchantService = new Merchant\Service();

        $this->userService = new User\Service;

        $this->settlementService = new Settlement\Service();

        $this->merchantDetailCore = new Detail\Core();

        $this->requirementProcessor = new Requirements\BaseProcessor();

        parent::__construct();
    }

    public function getConfig(Merchant\Entity $merchant)
    {
        $response = [];

        $response[Util\Constants::ACCOUNT_CONFIG] = $this->getAccountConfig($merchant);

        $response[Util\Constants::PAYMENT_CAPTURE] = $this->getPaymentConfig($merchant);

        $response[Util\Constants::BANK_DETAILS] = $this->getBankDetails($merchant);

        $response[Util\Constants::NOTIFICATIONS] = $this->getNotificationDetails($merchant);

        return $response;

    }

    public function getRequirements(Merchant\Entity $merchant, Product\Entity $merchantProduct)
    {
        return $this->requirementProcessor->fetchRequirements($merchant, $merchantProduct);
    }

    private function getPaymentConfig(Merchant\Entity $merchant): array
    {
        $response = [];

        foreach (self::PAYMENT_CAPTURE_CONFIGS as $configToFetch)
        {
            $config = $this->paymentConfigService->fetch($configToFetch, []);

            if ($config['count'] > 0)
            {
                $response[$configToFetch] = $config['items'][0]['config'];
            }
        }

        return $response;
    }

    private function getAccountConfig(Merchant\Entity $merchant): array
    {
        $response = $this->merchantService->fetchConfig();

        $featuresData = $this->merchantService->getMerchantFeatures();

        $flashCheckout = array_filter($featuresData['features'], function($feature) {
            return ($feature['feature'] === Util\Constants::NOFLASHCHECKOUT);
        });

        $noFlashCheckoutValue = empty($flashCheckout) === true ? true : $flashCheckout[0]['value'];

        $response[Util\Constants::FLASH_CHECKOUT] = !$noFlashCheckoutValue;

        return $response;
    }

    private function getBankDetails(Merchant\Entity $merchant)
    {
        $merchantDetails = $merchant->merchantDetail->toArrayPublic();

        $response[Util\Constants::ACCOUNT_NUMBER] = $merchantDetails[Merchant\Detail\Entity::BANK_ACCOUNT_NUMBER];

        $response[Util\Constants::IFSC_CODE] = $merchantDetails[Merchant\Detail\Entity::BANK_BRANCH_IFSC];

        $response[Util\Constants::NAME] = $merchantDetails[Merchant\Detail\Entity::BANK_ACCOUNT_NAME];

        return $response;
    }

    private function getNotificationDetails(Merchant\Entity $merchant)
    {
        $merchantUser = $this->repo->user->getUserFromEmail($merchant->getEmail());

        $response = [];

        $input = ['source' => 'pg.settings.config'];

        $response[Util\Constants::WHATSAPP] = empty($this->userService->optInStatusForWhatsapp($input, $merchantUser)) === false;

        $response[Util\Constants::SMS] = $this->settlementService->getSettlementSmsNotificationStatus($merchant)['enabled'];

        return $response;
    }

    public function createConfig(Merchant\Entity $merchant, array $configs): array
    {
        if (isset($configs[Util\Constants::PAYMENT_CONFIG]) === false)
        {
            $this->merchantService->setDefaultLateAuthConfigForMerchant($merchant);
        }

        return $this->updateConfig($merchant, $configs);
    }

    /**
     * @param Merchant\Entity $merchant
     * @param array           $configs
     *
     * @return array
     */
    public function updateConfig(Merchant\Entity $merchant, array $configs): array
    {
        foreach ($configs as $configKey => $configValue)
        {
            $function = 'update' . studly_case($configKey);

            $this->$function($merchant, $configValue);
        }

        return $this->getConfig($merchant);
    }

    private function updateNotifications(Merchant\Entity $merchant, array $configValue)
    {
        $merchantUser = $this->repo->user->getUserFromEmail($merchant->getEmail());

        if (isset($configValue[Util\Constants::SMS]) === true)
        {
            $payload = $configValue[Util\Constants::SMS];

            $this->settlementService->toggleSettlementSmsNotification($payload);
        }
        if (isset($configValue[Util\Constants::WHATSAPP]) === true)
        {
            $value = $configValue[Util\Constants::WHATSAPP];

            $payload = ['source' => 'pg.settings.config'];

            if ($value === true)
            {
                $this->userService->optInStatusForWhatsapp($payload, $merchantUser);
            }
            else
            {
                $this->userService->optOutForWhatsapp($payload, $merchantUser);
            }
        }
    }

    private function updateAccountConfig(Merchant\Entity $merchant, array $input)
    {
        if(empty($input) === true)
        {
            return;
        }

        if (isset($input[Util\Constants::FLASH_CHECKOUT]) === true)
        {
            $flashCheckoutPayload = $input[Util\Constants::FLASH_CHECKOUT];

            unset($input[Util\Constants::FLASH_CHECKOUT]);

            $this->merchantService->addOrRemoveMerchantFeatures($flashCheckoutPayload);
        }

        $this->merchantService->editConfig($input);
    }

    private function updatePaymentConfig(Merchant\Entity $merchant, array $input)
    {
        $this->paymentConfigService->update($input);
    }

    private function updateBankDetails(Merchant\Entity $merchant, array $input)
    {
        if(empty($input) === true)
        {
            return;
        }

        $this->merchantDetailCore->saveMerchantDetails($input, $merchant);
    }
}
