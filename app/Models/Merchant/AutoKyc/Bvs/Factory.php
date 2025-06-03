<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs;

use App;

use RZP\Exception\LogicException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\AutoKyc\Processor;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\AutoKyc\Bvs\Processors\DefaultProcessorMock;
use RZP\Models\Merchant\AutoKyc\Bvs\Processors\DefaultProcessor;
use RZP\Models\Merchant\AutoKyc\OcrService\WebsitePolicyProcessor;
use RZP\Models\Merchant\AutoKyc\OcrService\MccCategorisationProcessor;
use RZP\Models\Merchant\AutoKyc\OcrService\BvsDocumentManagerProcessor;
use RZP\Models\Merchant\Document\Type;

class Factory
{
    /**
     * @param array $input
     *
     * @param       $merchant
     *
     * @param null  $ocrServiceName
     *
     * @return Processor
     * @throws LogicException
     */
    public function getProcessor(array $input, $merchant, $ocrServiceName = null): Processor
    {
        $app = App::getFacadeRoot();
        $trace = $app['trace'];

        $mock = $app['config']['services.bvs.mock'];

        $configName = $input[Constant::CONFIG_NAME]??null;
        $currentRoute = $this->app['api.route']->getCurrentRouteName();

        if ($mock === true)
        {
            $processor = new DefaultProcessorMock($input, $configName, $merchant);
        }
        else if (empty($ocrServiceName) === false)
        {
            $processor = match ($ocrServiceName)
            {
                Constant::MCC_CATEGORISATION => new MccCategorisationProcessor($input, $merchant),
                Constant::WEBSITE_POLICY     => new WebsitePolicyProcessor($input, $merchant),
                Constant::NEGATIVE_KEYWORDS  => new BvsDocumentManagerProcessor($input, $merchant),
                default                      => throw new LogicException('Unexpected OCR service name: ' . $ocrServiceName),
            };
        }
        else
        {
            // For Constant::ROUTE_MERCHANT_DOCUMENT_ADMIN_UPLOAD route, force Aadhaar config for Aadhaar documents
            if ($currentRoute === Constant::ROUTE_MERCHANT_DOCUMENT_ADMIN_UPLOAD && 
                isset($input['artefact']['details']['document_type']) && 
                Type::isAadhaarDocument($input['artefact']['details']['document_type']))
            {
                $configName = Constant::CONFIG_AADHAAR;
                if (isset($input[Constant::ARTEFACT_TYPE])) {
                    $input[Constant::ARTEFACT_TYPE] = Constant::AADHAAR;
                }
                
                $trace->info(TraceCode::BVS_CONFIG_OVERRIDE, [
                    'route' => $currentRoute,
                    'document_type' => $input['artefact']['details']['document_type'] ?? null,
                    'new_config' => $configName,
                    'artefact_type' => $input[Constant::ARTEFACT_TYPE] ?? null
                ]);
            }
            
            $processor = new DefaultProcessor($input, $configName, $merchant);
        }

        return $processor;
    }
}
