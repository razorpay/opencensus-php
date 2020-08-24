<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs;

use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\AutoKyc\Processor;
use RZP\Models\Merchant\AutoKyc\ProcessorFactory;
use RZP\Models\Merchant\AutoKyc\Bvs\poi\PoiProcessor;

class Factory implements ProcessorFactory
{
    public static function getRegisterProcessor(array $input): ?Processor
    {
        return null;
    }

    public static function getCompanyPanProcessor(array $input): ?Processor
    {
        // TODO: Implement getCompanyPanProcessor() method.
    }

    public static function getPOAProcessor(array $input): Processor
    {
        // TODO: Implement getPOAProcessor() method.
    }

    public static function getGSTINProcessor(array $input): ?Processor
    {
        // TODO: Implement getGSTINProcessor() method.
    }

    public static function getCINProcessor(array $input): ?Processor
    {
        // TODO: Implement getCINProcessor() method.
    }

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
        switch ($artefactType)
        {
            case Constant::POI :

                return self::getPOIProcessor($input);

            default:
                throw new LogicException(ErrorCode::UNSUPPORTED_ARTEFACT_TYPE, null, [
                    Constant::ARTEFACT_TYPE => $artefactType
                ]);
        }
    }

    public static function getPOIProcessor(array $input): Processor
    {
        // need to handle mockPoiProcessor
        return new PoiProcessor($input);
    }
}
