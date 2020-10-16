<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs;

use App;

use RZP\Models\Merchant\AutoKyc\Processor;

class Factory
{
    /**
     * @param array $input
     *
     * @return Processor
     * @throws \RZP\Exception\LogicException
     */
    public function getProcessor(array $input): Processor
    {
        $app = $app = App::getFacadeRoot();

        $mock = $app['config']['services.bvs.mock'];

        $configName = $input[Constant::CONFIG_NAME];

        if ($mock === true)
        {
            $processorMock = new DefaultProcessorMock($input, $configName);

            //
            // This config is not defined in application config , this is used in test case only
            //
            $mockStatus = $app['config']['services.bvs.response'] ?? Constant::SUCCESS;

            $processorMock->setMockStatus($mockStatus);

            return $processorMock;
        }

        return new DefaultProcessor($input, $configName);
    }
}
