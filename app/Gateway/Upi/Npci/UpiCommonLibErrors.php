<?php

namespace RZP\Gateway\Upi\Npci;

class UpiCommonLibErrors
{
    /**
     * This class contains upi service layer errors
     */

    const DEFAULT_ERROR = 'Random error';

    protected static $map = [
        'L05' => 'Technical Issue, please try after some time',
        'L06' => 'Key Code has not been provided in input',
        'L07' => 'Error while parsing Key Code from input',
        'L08' => 'XML Payload has not been provided in input',
        'L09' => 'Error while parsing XML Payload from input',
        'L05' => 'Technical Issue, please try after some time',
        'L06' => 'Key Code has not been provided in input',
        'L07' => 'Error while parsing Key Code from input',
        'L08' => 'XML Payload has not been provided in input',
        'L09' => 'Error while parsing XML Payload from input',
        'L10' => 'Error while parsing Controls from input',
        'L11' => 'Error while parsing Configuration from input',
        'L12' => 'Salt has not been provided in input',
        'L13' => 'Error while parsing Salt from input',
        'L14' => 'Error while parsing Pay Info from input',
        'L15' => 'Error while parsing Locale from input',
        'L16' => 'Unknown error occurred',
        'L17' => 'Trust has not been provided',
        'L18' => 'Mandatory salt values have not been provided',
        'L19' => 'Error while parsing mandatory salt values',
        'L20' => 'Trust is not valid',
    ];

    public static function getErrorMessage($code)
    {
        if (isset(self::$map[$code]) === true)
        {
            return self::$map[$code];
        }

        return self::DEFAULT_ERROR;
    }
}
