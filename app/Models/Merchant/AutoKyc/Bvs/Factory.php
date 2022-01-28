<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs;

use App;

use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\AutoKyc\Processor;
use RZP\Models\Merchant\AutoKyc\Bvs\Processors\DefaultProcessorMock;
use RZP\Models\Merchant\AutoKyc\Bvs\Processors\DefaultProcessor;

class Factory
{
    /**
     * @param array $input
     *
     * @param       $merchant
     *
     * @return Processor
     * @throws \RZP\Exception\LogicException
     */
    public function getProcessor(array $input, $merchant): Processor
    {
        $app = $app = App::getFacadeRoot();

        $mock = $app['config']['services.bvs.mock'];

        $configName = $input[Constant::CONFIG_NAME]??null;

        if ($mock === true)
        {
            $processor = new DefaultProcessorMock($input, $configName, $merchant);
        }
        else
        {
            $processor = new DefaultProcessor($input, $configName, $merchant);
        }

        return $processor;
    }
}
