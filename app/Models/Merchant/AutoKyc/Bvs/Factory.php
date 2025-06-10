<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs;

use App;

use RZP\Exception\LogicException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\AutoKyc\Processor;
use RZP\Models\Merchant\AutoKyc\Bvs\Processors\DefaultProcessorMock;
use RZP\Models\Merchant\AutoKyc\Bvs\Processors\DefaultProcessor;
use RZP\Models\Merchant\AutoKyc\OcrService\WebsitePolicyProcessor;
use RZP\Models\Merchant\AutoKyc\OcrService\MccCategorisationProcessor;
use RZP\Models\Merchant\AutoKyc\OcrService\BvsDocumentManagerProcessor;

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

        $mock = $app['config']['services.bvs.mock'];

        $configName = $input[Constant::CONFIG_NAME]??null;

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
            $processor = new DefaultProcessor($input, $configName, $merchant);
        }

        return $processor;
    }
}
