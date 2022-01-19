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

        $configName = $input[Constant::CONFIG_NAME]??null;

        if ($mock === true)
        {
            return new DefaultProcessorMock($input, $configName);
        }

        return new DefaultProcessor($input, $configName);
    }
}
