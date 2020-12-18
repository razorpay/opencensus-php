<?php

namespace RZP\Models\AppStore;

use RZP\Diag\EventCode;
use RZP\Trace\TraceCode;
use \RZP\Models\Merchant\Entity;
use \RZP\Models\AppStore\Entity as AppStoreEntity;

class Core extends Base\Core
{

    const SUCCESS = 'success';

    const REASON = 'reason';

    const MOBILE_NUMBER_NOT_EXISTS_REASON = 'No valid mobile number found for the merchant';

    const APP_ALREADY_INSTALLED_REASON    = 'App is already installed for the merchant';

    const APP_NOT_SUPPORTED_REASON        = 'App is not supported, cannot be installed';

    const APP_NAME_NOT_PRESENT            = 'app_name not present in the input';

    const PL_ON_WHATSAPP = 'pl_on_whatsapp';

    const CREATE_PL_REGEX_PATTERN = '/Create \d+/';

    const CREATE_PL_TEMPLATE = 'Create ';

    /**
     * @param array  $input
     * @param Entity $merchant
     *
     * @return array|false[]
     */
    public function installAppOnAppStoreForMerchant(array $input, Entity $merchant)
    {
        try
        {
            $this->trace->info(TraceCode::INSTALL_APP_ON_APPSTORE_REQUEST, $input);

            if (array_key_exists(AppStoreEntity::APP_NAME,$input) === false)
            {
                return [
                    self::SUCCESS => false,
                    self::REASON => self::APP_NAME_NOT_PRESENT
                ];
            }

            $appName = $input[AppStoreEntity::APP_NAME];

            if ($appName != Constant::PL_ON_WHATSAPP)
            {
                return [
                    self::SUCCESS => false,
                    self::REASON  => self::APP_NOT_SUPPORTED_REASON
                ];
            }

            $mobileNumber = $this->getPhone($merchant);

            if (empty($mobileNumber) === true)
            {
                return [
                    self::SUCCESS => false,
                    self::REASON  => self::MOBILE_NUMBER_NOT_EXISTS_REASON
                ];
            }

            //Check if app is already installed for the merchant or not
            $appStoreEntity = $this->repo->app_store->getAppStoreDetailsForMerchant($appName, $merchant->getId());

           if ($appStoreEntity != null)
           {
                return [
                    self::SUCCESS => false,
                    self::REASON => self::APP_ALREADY_INSTALLED_REASON
                ];
            }

            //Check if app is already installed for the merchant or not
            $appStoreEntity = $this->repo->app_store->getAppLinkedWithMobileNumber($appName, $mobileNumber);

            if ($appStoreEntity != null)
            {
                return [
                    self::SUCCESS => false,
                    self::REASON  => self::APP_ALREADY_INSTALLED_REASON
                ];
            }

            $this->repo->transactionOnLiveAndTest(function() use ($mobileNumber, $appName, $merchant) {
                switch ($appName)
                {
                    case Constant::PL_ON_WHATSAPP:
                        $plOnWhatsappCore = new PLOnWhatsapp\Core();

                        $plOnWhatsappCore->NotifyMerchant($mobileNumber, $merchant->getId());
                        break;
                    default:
                        return [
                            self::SUCCESS => false,
                            self::REASON  => self::APP_NOT_SUPPORTED_REASON
                        ];
                }

                $appStoreEntity = (new AppStoreEntity)->generateId();

                $appStoreEntity->setMerchantId($merchant->getId());

                $appStoreEntity->setAppName($appName);

                $appStoreEntity->setMobileNumber($mobileNumber);

                $this->repo->app_store->saveOrFail($appStoreEntity);
            });

            return [
                self::SUCCESS => true,
            ];

        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                                         null,
                                         TraceCode::INSTALL_APP_ON_APPSTORE_REQUEST_FAILURE);
            $result = [
                self::SUCCESS => false,
            ];

            return $result;
        }

    }

    /**
     * Lists all the installed Apps for the merchant
     *
     * @param Entity $merchant
     *
     * @return mixed (array or empty)
     */
    public function getInstallAppsForMerchant(Entity $merchant)
    {
        return $this->repo->app_store->getAllAppsForMerchantFromAppStore($merchant->getId());
    }

    /**
     * @param string $mobileNumber
     * @param string $message
     *
     * @return array|string
     */
    public function processGupShupMessages(string $mobileNumber, string $message)
    {
        $this->trace->info(TraceCode::APPSTORE_PROCESS_MESSAGE_REQUEST,
                           [
                               'message' => $message
                           ]);

        $mobileNumber = preg_replace("/^\+?91/", '', $mobileNumber);

        $appEntity = $this->repo
            ->app_store->getAppLinkedWithMobileNumber(Constant::PL_ON_WHATSAPP, $mobileNumber);

        $plOnWhatsappCore = new PLOnWhatsapp\Core();

        if (empty($appEntity) === true)
        {
            return [
                'success' => false
            ];
        }

        $merchantId = $appEntity->getMerchantId();

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        if (preg_match(self::CREATE_PL_REGEX_PATTERN, $message) != 1)
        {
            $plOnWhatsappCore->sendMessageForWrongTemplate($merchantId, $mobileNumber);

            $eventProperties = [
                Entity::MERCHANT_ID     => $merchantId,
            ];

            $this->app['diag']->trackOnboardingEvent(EventCode::PARTNERSHIPS_APPSTORE_WA_PL_WRONG_TEMPLATE,
                $merchant, null, $eventProperties);

            return [
                'success' => false
            ];;
        }

        $amount = str_replace(self::CREATE_PL_TEMPLATE, '', $message);

        $plOnWhatsappCore->sendPaymentLinkOnWhatsapp($merchant, $amount, $mobileNumber);

        return [
            'success' => true
        ];;
    }


    protected function getPhone(Entity $merchant)
    {
        if ($merchant->isLinkedAccount() === true)
        {
            return $merchant->parent->merchantDetail->getContactMobile();
        }

        return $merchant->merchantDetail->getContactMobile();
    }


}
