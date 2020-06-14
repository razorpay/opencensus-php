<?php

namespace RZP\Models\Merchant\AutoKyc\KycService;

use App;

use RZP\Models\Merchant\AutoKyc\KycService\cin\CINProcessor;
use RZP\Models\Merchant\AutoKyc\KycService\cin\CINProcessorMock;
use RZP\Models\Merchant\Detail\Constants;
use RZP\Models\Merchant\AutoKyc\Processor;
use RZP\Models\Merchant\AutoKyc\ProcessorFactory;
use RZP\Models\Merchant\AutoKyc\KycService\poa\POAProcessor;
use RZP\Models\Merchant\AutoKyc\KycService\poi\POIProcessor;
use RZP\Models\Merchant\AutoKyc\KycService\poa\POAProcessorMock;
use RZP\Models\Merchant\AutoKyc\KycService\poi\POIProcessorMock;
use RZP\Models\Merchant\AutoKyc\KycService\gstin\GSTINProcessor;
use RZP\Models\Merchant\AutoKyc\KycService\gstin\GSTINProcessorMock;
use RZP\Models\Merchant\AutoKyc\KycService\kycDetails\KycDetailsProcessor;
use RZP\Models\Merchant\AutoKyc\KycService\register\RegistrationProcessor;
use RZP\Models\Merchant\AutoKyc\KycService\companyPan\CompanyPanProcessor;
use RZP\Models\Merchant\AutoKyc\KycService\kycDetails\KycDetailProcessorMock;
use RZP\Models\Merchant\AutoKyc\KycService\register\RegistrationProcessorMock;
use RZP\Models\Merchant\AutoKyc\KycService\companyPan\CompanyPanProcessorMock;

/**
 * Returns Processor to be used when doing auto kyc using kyc service
 *
 * Class ProcessorFactoryImpl
 *
 * @package RZP\Models\Merchant\Detail\KycService
 */
class ProcessorFactoryImpl implements ProcessorFactory
{
    public static function getPOIProcessor(array $input): Processor
    {
        $app = App::getFacadeRoot();

        $mock = $app['config']['applications.kyc.mock'];

        if ($mock === true)
        {
            $panVerifiedMock = new POIProcessorMock($input);

            // this config is not defined in application config , this is used in test case only
            $mockStatus = $app['config']['applications.kyc.pan_authentication'] ?? Constants::SUCCESS;

            $panVerifiedMock->setMockStatus($mockStatus);

            return $panVerifiedMock;
        }

        return new POIProcessor($input);
    }


    /**
     * @param array $input
     *
     * @return Processor
     */
    public static function getRegisterProcessor(array $input): Processor
    {
        $app = App::getFacadeRoot();

        $mock = $app['config']['applications.kyc.mock'];

        if ($mock === true)
        {
            $panVerifiedMock = new RegistrationProcessorMock($input);

            // this config is not defined in application config , this is used in test case only
            $mockStatus = $app['config']['applications.kyc.kyc_register'] ?? Constants::SUCCESS;

            $panVerifiedMock->setMockStatus($mockStatus);

            return $panVerifiedMock;
        }

        return new RegistrationProcessor($input);
    }

    public static function getPOAProcessor(array $input): Processor
    {
        $app = App::getFacadeRoot();

        $mock = $app['config']['applications.kyc.mock'];

        if ($mock === true)
        {
            $poaVerifiedMock = new POAProcessorMock($input);

            // this config is not defined in application config , this is used in test case only
            $documentType = $app['config']['applications.kyc.poa_ocr_response_type'] ?? Constants::DOCUMENT_TYPES['AADHAAR'];

            $poaVerifiedMock->setDocumentType($documentType);

            return $poaVerifiedMock;
        }

        return new POAProcessor($input);
    }

    /**
     * Returns processor for fetching kyc detail
     *
     * @param array $input
     *
     * @return Processor
     */
    public static function getKYCDetailProcessor(array $input): Processor
    {
        $app = App::getFacadeRoot();

        $mock = $app['config']['applications.kyc.mock'];

        if ($mock === true)
        {
            $kycDetailMock = new KycDetailProcessorMock($input);

            // this config is not defined in application config , this is used in test case only
            $mockStatus = $app['config']['applications.kyc.kyc_detail_status'] ?? Constants::SUCCESS;

            $documentTypes = $app['config']['applications.kyc.kyc_detail_document'] ?? [Constants::AADHAAR];

            $kycDetailMock->setMockStatus($mockStatus);

            $kycDetailMock->setDocumentTypes($documentTypes);

            return $kycDetailMock;
        }

        return new KycDetailsProcessor($input);
    }

    public static function getGSTINProcessor(array $input): Processor
    {
        $app = App::getFacadeRoot();

        $mock = $app['config']['applications.kyc.mock'];

        if ($mock === true)
        {
            $GSTINProcessorMock = new GSTINProcessorMock($input);

            // this config is not defined in application config , this is used in test case only
            $mockStatus = $app['config']['applications.kyc.gstin_authentication'] ?? Constants::SUCCESS;

            $GSTINProcessorMock->setMockStatus($mockStatus);

            return $GSTINProcessorMock;
        }

        return new GSTINProcessor($input);
    }

    public static function getCompanyPanProcessor(array $input): ?Processor
    {
        $app = App::getFacadeRoot();

        $mock = $app['config']['applications.kyc.mock'];

        if ($mock === true)
        {
            $companyPanProcessorMock = new CompanyPanProcessorMock($input);

            // this config is not defined in application config , this is used in test case only
            $mockStatus = $app['config']['applications.kyc.company_pan_authentication'] ?? Constants::SUCCESS;

            $companyPanProcessorMock->setMockStatus($mockStatus);

            return $companyPanProcessorMock;
        }

        return new CompanyPanProcessor($input);
    }

    /** Returns mock or kyc service CIN processor based on mock flag
     *
     * @param array $input
     *
     * @return null|Processor
     */
    public static function getCINProcessor(array $input): ?Processor
    {
        $app = App::getFacadeRoot();

        $mock = $app['config']['applications.kyc.mock'];

        if ($mock === true)
        {
            $mockStatus = $app['config']['applications.kyc.cin_authentication'] ?? Constants::SUCCESS;

            $CINProcessorMock = new CINProcessorMock($input);

            $CINProcessorMock->setMockStatus($mockStatus);

            return $CINProcessorMock;
        }

        return new CINProcessor($input);
    }
}
