<?php


namespace RZP\Models\Merchant\Detail\Upload\Processors;

use RZP\Exception;
use RZP\Models\Merchant\Detail\Upload\Constants;

class Factory
{
    public static function getInstance(string $format)
    {
        switch ($format)
        {
            case Constants::AXIS_MIQ:
                return new AxisMIQParser();
            default:
                throw new Exception\LogicException('invalid format: '. $format);
        }
    }
}
