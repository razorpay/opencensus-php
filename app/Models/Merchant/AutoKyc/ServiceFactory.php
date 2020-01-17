<?php

namespace RZP\Models\Merchant\AutoKyc;

use App;

use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\AutoKyc\KycService\ProcessorFactoryImpl as KycProcessorFactory;
use RZP\Models\Merchant\AutoKyc\MozartService\ProcessorFactoryImpl as MozartProcessorFactory;

/**
 * Returns service factory to be used for performing KYC
 *
 * Class ServiceFactory
 *
 * @package RZP\Models\Merchant\Detail\autoKyc
 */
class ServiceFactory
{
    /**
     * Returns service verifier factory based on razorx flag
     *
     * @param string      $merchantId
     * @param string|null $mode
     *
     * @return ProcessorFactory
     */
    public static function getVerifierServiceFactory(string $merchantId,
                                                     string $mode = null): ProcessorFactory
    {
        if (self::shouldRouteTrafficToKycService($merchantId, $mode) === true)
        {
            return new KycProcessorFactory();
        }

        return new MozartProcessorFactory();
    }

    /**
     * Checks if kyc_service_verification razorx experiment enabled for merchant id
     *
     * @param string $merchantId
     * @param null   $mode
     *
     * @return bool
     */
    public static function shouldRouteTrafficToKycService(string $merchantId, $mode): bool
    {
        $app = App::getFacadeRoot();

        $mode = $mode ?? Mode::LIVE;

        $status = $app['razorx']->getTreatment($merchantId,
                                               Merchant\RazorxTreatment::KYC_SERVICE_VERIFICATION,
                                               $mode);

        return (strtolower($status) === 'on');
    }
}
