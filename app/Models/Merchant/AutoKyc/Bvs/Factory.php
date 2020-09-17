<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs;

use App;

use RZP\Models\Merchant\AutoKyc\Processor;

class Factory
{
    /**
     * @param string $artefactType
     *
     * @param array  $input
     *
     * @return Processor
     * @throws \RZP\Exception\LogicException
     */
    public function getProcessor(string $artefactType, array $input): Processor
    {
        $app = $app = App::getFacadeRoot();

        $mock = $app['config']['services.bvs.mock'];

        if ($mock === true)
        {
            $processorMock = new DefaultProcessorMock($input, $artefactType);

            //
            // This config is not defined in application config , this is used in test case only
            //
            $mockStatus = $app['config']['services.bvs.response'] ?? Constant::SUCCESS;

            $processorMock->setMockStatus($mockStatus);

            return $processorMock;
        }

        return new DefaultProcessor($input, $artefactType);
    }
}
