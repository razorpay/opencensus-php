<?php

namespace RZP\Models\Merchant\Product\Config;

use File;
use Razorpay\Trace\Logger;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Trace\Tracer;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Settlement;
use RZP\Constants\HyperTrace;
use RZP\Models\Payment\Config;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Product;
use RZP\Models\Merchant\AccountV2;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Product\Util;
use RZP\Exception\BadRequestException;
use RZP\Jobs\ProductConfig\AutoUpdateMerchantProducts;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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

    protected function getBankDetails(Merchant\Entity $merchant)
    {
        $merchantDetails = $merchant->merchantDetail->toArrayPublic();

        $response[Util\Constants::ACCOUNT_NUMBER] = $merchantDetails[Merchant\Detail\Entity::BANK_ACCOUNT_NUMBER];

        $response[Util\Constants::IFSC_CODE] = $merchantDetails[Merchant\Detail\Entity::BANK_BRANCH_IFSC];

        $response[Util\Constants::BENEFICIARY_NAME] = $merchantDetails[Merchant\Detail\Entity::BANK_ACCOUNT_NAME];

        return $response;
    }

    private function getNotificationDetails(Merchant\Entity $merchant)
    {
        $merchantUser = $merchant->primaryOwner();

        $response = [];

        $input = ['source' => 'pg.settings.config'];

        try
        {
            $optInStatusResponse = $this->userService->optInStatusForWhatsapp($input, $merchantUser);
        }
        catch (\Exception $exception)
        {
            $this->trace->traceException($exception,
                                         Logger::ERROR,
                                         TraceCode::FAILED_TO_FETCH_WHATSAPP_STATUS
            );
        }

        $response[Util\Constants::WHATSAPP] = $optInStatusResponse['consent_status'] ?? false;

        $response[Util\Constants::SMS] = $this->settlementService->getSettlementSmsNotificationStatus($merchant)['enabled'];

        return $response;
    }

    public function createConfig(Merchant\Entity $merchant, array $configs): array
    {
//      todo: We can remove this code block once experiment is ramped to 100%.
//      Instead of creating config while creating product, creating it with sub-merchant account creation.
//      But it is under experimentation so creating config here whenever required.

        $config = $this->repo->config->fetchConfigByMerchantIdAndType($merchant->getId(), 'late_auth');

        if (isset($configs[Util\Constants::PAYMENT_CONFIG]) === false and count($config) === 0)
        {
            $this->merchantService->setDefaultLateAuthConfigForMerchant($merchant);
        }

        return Tracer::inspan(['name' => HyperTrace::UPDATE_CONFIG], function () use ($merchant, $configs) {

            return $this->updateConfig($merchant, $configs);
        });
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

        return Tracer::inspan(['name' => HyperTrace::GET_CONFIG], function () use ($merchant) {

            return $this->getConfig($merchant);
        });
    }

    private function updateNotifications(Merchant\Entity $merchant, array $configValue)
    {
        $merchantUser = $merchant->primaryOwner();

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
                $this->userService->optInForWhatsapp($payload, $merchantUser);
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

        $this->updateLogoInformation($input, $merchant->getId());

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

        $accountV2Validator->validateOptionalFieldSubmissionInActivatedKycPendingState($merchant, $input);

        $this->merchantDetailCore->saveMerchantDetails($input, $merchant);

        $accountCore->updateNCFieldsAcknowledgedIfApplicable($input, $merchant);

        AutoUpdateMerchantProducts::dispatch(Product\Status::PRODUCT_CONFIG_SOURCE, $merchant->getId());
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

    /**
     * If logo_url is passed in the input then fetch logo contents from it and store it in a file
     *
     * @param array $input
     * @param string $merchantId
     *
     * @return void
     */
    private function updateLogoInformation(array & $input, string $merchantId)
    {
        try
        {
            if (isset($input[Merchant\Entity::LOGO_URL]) === false)
            {
                return;
            }

            $url = $input[Merchant\Entity::LOGO_URL];

            $path_info = pathinfo($url);

            $this->trace->info(
                TraceCode::FETCHING_LOGO_FROM_URL,
                [
                    'url'         => $url,
                    'path_info'   => $path_info,
                    'merchant_id' => $merchantId
                ]
            );

            $contents = file_get_contents($url);

            $file = storage_path('files/logos') . '/' . $path_info['basename'];

            file_put_contents($file, $contents);

            $input[Util\Constants::LOGO] = $this->getUploadedFileInstance($file);

            unset($input[Merchant\Entity::LOGO_URL]);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::ERROR_WHILE_FETCHING_LOGO_FROM_URL,
                [
                    'error'       => $e->getMessage(),
                    'url'         => $url,
                    'merchant_id' => $merchantId
                ]
            );

            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_FETCH_LOGO_FROM_URL_FAILED,
                Merchant\Entity::LOGO_URL,
                ['url' => $url]
            );
        }
    }

    private function getUploadedFileInstance(string $path): UploadedFile
    {
        $name = File::name($path);

        $extension = File::extension($path);

        $originalName = $name . '.' . $extension;

        $mimeType = File::mimeType($path);

        $size = File::size($path);

        $error = null;

        // Setting as Test, because UploadedFile expects the file instance to be a temporary uploaded file, and
        // reads from Local Path only in test mode. As our requirement is to always read from local path, so
        // creating the UploadedFile instance in test mode.

        $test = true;

        $object = new UploadedFile($path, $originalName, $mimeType, $error, $test);

        return $object;
    }
}
