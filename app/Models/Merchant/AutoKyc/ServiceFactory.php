<?php

namespace RZP\Models\Merchant\AutoKyc;

use App;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\Document\Source;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Detail\Constants as DEConstants;
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


    const PROCESSOR_TYPE_RAZORX_EXPERIMENT = [
        DEConstants::POA      => RazorxTreatment::POA_KYC_SERVICE_VERIFICATION,
        DEConstants::POI      => RazorxTreatment::POI_KYC_SERVICE_VERIFICATION,
        DEConstants::REGISTER => RazorxTreatment::KYC_SERVICE_VERIFICATION,
    ];

    /**
     * @todo Ideally instead of using one razorx experiment for multiple api , we should split razorx flag based on processor type
     *       this will be useful when we add more document
     *
     * Returns service verifier factory based on razorx flag
     *
     * @param array       $input
     * @param string      $processorType
     * @param string|null $mode
     *
     * @return ProcessorFactory
     * @throws LogicException
     */
    public static function getVerifierServiceFactory(array $input,
                                                     string $processorType,
                                                     string $mode = null): ProcessorFactory
    {
        if (self::shouldRouteTrafficToKycService($input, $processorType, $mode) === true)
        {
            return new KycProcessorFactory();
        }

        return new MozartProcessorFactory();
    }

    /**
     * Checks if kyc_service_verification razorx experiment enabled for merchant id
     *
     * @param array  $input
     * @param string $processorType
     * @param null   $mode
     *
     * @return bool
     * @throws LogicException
     */
    public static function shouldRouteTrafficToKycService(array $input, string $processorType, $mode): bool
    {

        $merchantId = $input[DEConstants::ENTITY_ID];

        switch ($processorType)
        {
            case DEConstants::POI :
            case DEConstants::REGISTER :
                return self::isRazorxExperimentEnabled($merchantId, self::PROCESSOR_TYPE_RAZORX_EXPERIMENT[$processorType], $mode);

            case DEConstants::POA :
                return self::servePOAFromKycService($merchantId, $input, $mode);
                
            default :
                throw new LogicException(ErrorCode::UNHANDLED_KYC_PROCESSOR_TYPE, null, [
                    DEConstants::PROCESSOR_TYPE => $processorType
                ]);
        }

    }

    /**
     *  Kyc service only understands ufh file store ,
     * so if api source is api then don't route traffic to new kyc service
     *
     * @param string $merchantId
     * @param array  $input
     * @param        $mode
     *
     * @return bool
     */
    private static function servePOAFromKycService(string $merchantId, array $input, $mode)
    {
        $documentSource = $input[DEConstants::DOCUMENT_SOURCE] ?? Source::API;

        if ($documentSource === Source::API)
        {
            return false;
        }

        return self::isRazorxExperimentEnabled($merchantId, self::PROCESSOR_TYPE_RAZORX_EXPERIMENT[DEConstants::POA], $mode);
    }

    /**
     * @param string $merchantId
     * @param string $experimentName
     * @param        $mode
     *
     * @return bool
     */
    private static function isRazorxExperimentEnabled(string $merchantId, string $experimentName, $mode)
    {
        $app = App::getFacadeRoot();

        $mode = $mode ?? Mode::LIVE;

        $status = $app['razorx']->getTreatment($merchantId,
                                               $experimentName,
                                               $mode);

        return (strtolower($status) === 'on');
    }
}
