<?php

namespace RZP\Models\Merchant\Product\Config;

use RZP\Constants\HyperTrace;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Models\Settlement;
use RZP\Models\Payment\Config;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Product;
use RZP\Models\Merchant\AccountV2;
use RZP\Models\Merchant\Product\Util;
use RZP\Models\Merchant\Product\Requirements;
use RZP\Jobs\ProductConfig\AutoUpdateMerchantProducts;
use RZP\Trace\Tracer;

class PaymentsGeneralConfig extends Base\Service
{
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


    public function __construct()
    {
        $this->paymentConfigService = new Config\Service();

        $this->merchantService = new Merchant\Service();

        $this->userService = new User\Service;

        $this->settlementService = new Settlement\Service();

        $this->merchantDetailCore = new Detail\Core();

        parent::__construct();
    }

    public function getConfig(Merchant\Entity $merchant)
    {
        $response = [];

        $accountConfig = $this->getAccountConfig($merchant);

        $refundConfig = [];

        $response[Util\Constants::PAYMENT_CAPTURE] = $this->getPaymentConfig($merchant);

        $response[Util\Constants::BANK_DETAILS] = $this->getBankDetails($merchant);

        $notificationsConfig = $this->getNotificationDetails($merchant);;

        if(isset($accountConfig[Util\Constants::NOTIFICATIONS]) === true)
        {
            $notificationsConfig = array_merge($notificationsConfig, $accountConfig[Util\Constants::NOTIFICATIONS]);

            unset($accountConfig[Util\Constants::NOTIFICATIONS]);
        }

        if(isset($accountConfig[Util\Constants::REFUND]) === true)
        {
            $refundConfig = $accountConfig[Util\Constants::REFUND];

            unset($accountConfig[Util\Constants::REFUND]);
        }

        $response[Util\Constants::ACCOUNT_CONFIG] = $accountConfig;

        $response[Util\Constants::REFUND] = $refundConfig;

        $response[Util\Constants::NOTIFICATIONS] = $notificationsConfig;

        return $response;

    }

    private function getPaymentConfig(Merchant\Entity $merchant): array
    {
        $response = [];

        foreach (Util\Constants::PAYMENT_CAPTURE_CONFIGS as $configToFetch)
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

        $noFlashCheckoutValue = $this->getNoFlashCheckoutValue($merchant);

        $response[Util\Constants::FLASH_CHECKOUT] = !$noFlashCheckoutValue;

        if( isset($response[Merchant\Entity::DEFAULT_REFUND_SPEED]) === true)
        {
            $response[Util\Constants::REFUND] = [
                Merchant\Entity::DEFAULT_REFUND_SPEED => $response[Merchant\Entity::DEFAULT_REFUND_SPEED]
            ];

            unset($response[Merchant\Entity::DEFAULT_REFUND_SPEED]);
        }

        if( isset($response[Merchant\Entity::TRANSACTION_REPORT_EMAIL]) === true)
        {
            $response[Util\Constants::NOTIFICATIONS] = [
                Util\Constants::EMAIL => $response[Merchant\Entity::TRANSACTION_REPORT_EMAIL]
            ];

            unset($response[Merchant\Entity::TRANSACTION_REPORT_EMAIL]);
        }

        return $response;
    }

    private function getBankDetails(Merchant\Entity $merchant)
    {
        $merchantDetails = $merchant->merchantDetail->toArrayPublic();

        $response[Util\Constants::ACCOUNT_NUMBER] = $merchantDetails[Merchant\Detail\Entity::BANK_ACCOUNT_NUMBER];

        $response[Util\Constants::IFSC_CODE] = $merchantDetails[Merchant\Detail\Entity::BANK_BRANCH_IFSC];

        $response[Util\Constants::BENEFICIARY_NAME] = $merchantDetails[Merchant\Detail\Entity::BANK_ACCOUNT_NAME];

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

        if (isset($configValue[Merchant\Entity::TRANSACTION_REPORT_EMAIL]) === true)
        {
            $input = [
                Merchant\Entity::TRANSACTION_REPORT_EMAIL => $configValue[Merchant\Entity::TRANSACTION_REPORT_EMAIL]
            ];

            $this->merchantService->editConfig($input);
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

            $noFlashCheckoutFeatureValue = $flashCheckoutPayload[Util\Constants::FEATURES][Feature\Constants::NOFLASHCHECKOUT];

            unset($input[Util\Constants::FLASH_CHECKOUT]);

            $existingNoFlashCheckoutFeatureValue = $this->getNoFlashCheckoutValue($merchant);

            if ($existingNoFlashCheckoutFeatureValue !== $noFlashCheckoutFeatureValue)
            {
                $this->merchantService->addOrRemoveMerchantFeatures($flashCheckoutPayload);
            }
        }

        $this->merchantService->editConfig($input);
    }

    private function updatePaymentConfig(Merchant\Entity $merchant, array $input)
    {
        $this->paymentConfigService->update($input);
    }

    private function updateRefund(Merchant\Entity $merchant, array $input)
    {
        if(empty($input) === true)
        {
            return;
        }

        $this->merchantService->editConfig($input);
    }

    private function updateBankDetails(Merchant\Entity $merchant, array $input)
    {
        if(empty($input) === true)
        {
            return;
        }

        $accountCore = (new AccountV2\Core());

        $accountV2Validator = (new AccountV2\Validator());

        Tracer::inspan(['name' => HyperTrace::VALIDATE_NC_RESPONDED_IF_APPLICABLE], function () use ($accountV2Validator, $merchant, $input) {

            $accountV2Validator->validateNeedsClarificationRespondedIfApplicable($merchant, $input);
        });

        $this->merchantDetailCore->saveMerchantDetails($input, $merchant);

        $merchantDetails = $merchant->merchantDetail;

        $accountCore->updateNCFieldsAcknowledgedIfApplicable($input, $merchant);

        AutoUpdateMerchantProducts::dispatch(Product\Status::PRODUCT_CONFIG_SOURCE, $merchant, $merchantDetails);
    }

    private function getNoFlashCheckoutValue(Merchant\Entity $merchant): bool
    {
        $existingNoFlashCheckoutFeatureValue = false;

        $featuresEnabled = $this->repo->feature->findMerchantWithFeatures($merchant->getId(), [Feature\Constants::NOFLASHCHECKOUT]);

        if (count($featuresEnabled) > 0)
        {
            $existingNoFlashCheckoutFeatureValue = true;
        }

        return $existingNoFlashCheckoutFeatureValue;
    }
}
