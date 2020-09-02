<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs;

use App;

use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\AutoKyc\Processor;
use RZP\Models\Merchant\AutoKyc\Bvs\Poi\PoiProcessor;

class Factory
{
    /**
     * @param string $artefactType
     *
     * @param array  $input
     *
     * @return Processor
     * @throws LogicException
     */
    public function getProcessor(string $artefactType, array $input): Processor
    {
        $app = $app = App::getFacadeRoot();

        $mock = $app['config']['services.bvs.mock'];

        if ($mock === true)
        {
            $processorMock = new ProcessorMock($input);

            //
            // This config is not defined in application config , this is used in test case only
            //
            $mockStatus = $app['config']['services.bvs.pan_authentication'] ?? Constant::SUCCESS;

            $processorMock->setMockStatus($mockStatus);

            return $processorMock;
        }

        switch ($artefactType)
        {
            case Constant::POI :

                return new PoiProcessor($input);

            default:
                throw new LogicException(ErrorCode::SERVER_ERROR_UNSUPPORTED_ARTEFACT_TYPE, null, [
                    Constant::ARTEFACT_TYPE => $artefactType
                ]);
        }
    }
}
