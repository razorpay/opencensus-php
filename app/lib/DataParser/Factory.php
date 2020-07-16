<?php

namespace RZP\lib\DataParser;

use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;

class Factory
{
    const DATA_PARSER_MAPPING = [
        Base::TYPEFORM => TypeformParser::class,
    ];

    /**
     * @param $dataParserName
     * @param $dataToBeParsed
     *
     * @return DataParserInterface
     * @throws LogicException
     */
    public static function getDataParserImpl($dataParserName, $dataToBeParsed): DataParserInterface
    {
        if (key_exists($dataParserName, self::DATA_PARSER_MAPPING) === true)
        {
            $class = self::DATA_PARSER_MAPPING[$dataParserName];

            return new $class($dataToBeParsed);
        }

        throw new LogicException(
            null,
            ErrorCode::INVALID_DATA_PARSER,
            ['data_parser' => $dataParserName]);
    }
}
