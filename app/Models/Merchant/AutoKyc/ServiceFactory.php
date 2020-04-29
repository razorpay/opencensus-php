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

    /**
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
        if (self::shouldRouteTrafficToKycService($input, $processorType) === true)
        {
            return new KycProcessorFactory();
        }

        return new MozartProcessorFactory();
    }

    /**
     * Checks if we should serve traffic from kyc service or not
     *
     * @param array  $input
     * @param string $processorType
     *
     * @return bool
     * @throws LogicException
     */
    public static function shouldRouteTrafficToKycService(array $input, string $processorType): bool
    {
        switch ($processorType)
        {
            case DEConstants::POI :
            case DEConstants::REGISTER :
            case DEConstants::GSTIN :
            case DEConstants::COMPANY_PAN:
                return true;

            case DEConstants::POA :

                return self::servePOAFromKycService($input);

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
     * @param array $input
     *
     * @return bool
     */
    private static function servePOAFromKycService(array $input)
    {
        $documentSource = $input[DEConstants::DOCUMENT_SOURCE] ?? Source::API;

        if ($documentSource === Source::API)
        {
            return false;
        }

        return true;
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
