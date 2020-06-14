<?php

namespace RZP\Models\Merchant\AutoKyc\MozartService;

use App;

use RZP\Models\Merchant\Detail\Constants;
use RZP\Models\Merchant\AutoKyc\Processor;
use RZP\Models\Merchant\AutoKyc\ProcessorFactory;

/**
 * Returns Verifier to be used when doing auto kyc using kyc service
 *
 * Class ProcessorFactoryImpl
 *
 * @package RZP\Models\Merchant\Detail\Verifiers
 */
class ProcessorFactoryImpl implements ProcessorFactory
{
    public static function getPOIProcessor(array $input) : Processor
    {
        $app = App::getFacadeRoot();

        $mock = ($app['config']['applications.kyc.mock']
                 or $app['config']['applications.mozart.mock']);

        if ($mock === true)
        {
            $panVerifiedMock = new PanProcessorMock($input);

            // this config is not defined in application config , this is used in test case only
            $mockStatus = $app['config']['applications.mozart.pan_authentication'] ?? Constants::SUCCESS;

            $panVerifiedMock->setMockStatus($mockStatus);

            return $panVerifiedMock;
        }

        return new PanProcessor($input);
    }

    public static function getPOAProcessor(array $input): Processor
    {
        $app = App::getFacadeRoot();

        $mock = ($app['config']['applications.kyc.mock'] or
                 $app['config']['applications.mozart.mock']);

        if ($mock === true)
        {
            $poaVerifiedMock = new POAProcessorMock($input);

            // this config is not defined in application config , this is used in test case only
            $documentType = $app['config']['applications.mozart.poa_ocr_response_type'] ?? Constants::AADHAR_FRONT;

            $poaVerifiedMock->setDocumentType($documentType);

            return $poaVerifiedMock;
        }

        return new POAProcessor($input);
    }


    /**
     * @param array $input
     *
     * @return null|Processor
     */
    public static function getRegisterProcessor(array $input): ?Processor
    {
        return null;
    }

    /**
     * @param array $input
     *
     * @return null|Processor
     */
    public static function getGSTINProcessor(array $input): ?Processor
    {
        return null;
    }

    public static function getCompanyPanProcessor(array $input): ?Processor
    {
        return null;
    }

    public static function getCINProcessor(array $input): ?Processor
    {
        return null;
    }
}
