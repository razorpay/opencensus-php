<?php
namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\EntityToProtoConverter;


Class Helper {


    static function notNullCheck($attributes, $key) {
        if(!array_key_exists($key, $attributes) || is_null($attributes[$key])) {
            throw new \Exception("Required Key: $key, not found in attributes. This is a not null attribute and should be present.");
        }

        return $attributes[$key];
    }
    static function shouldSetNull($attributes , $key): bool
    {
        if(!array_key_exists($key, $attributes)) {
            return true;
        }

        if (is_null($attributes[$key])) {
            return true;
        }

        return false;
    }

    static function converToStringValue($attributes , $key): ?\Google\Protobuf\StringValue
    {

        if (self::shouldSetNull($attributes, $key)) {
            return null;
        }
        $stringValue = new \Google\Protobuf\StringValue();
        $stringValue->setValue($attributes[$key]);
        return $stringValue;
    }

    static function convertToInt32ValueFromBool($attributes , $key): ?\Google\Protobuf\Int32Value
    {
        if (self::shouldSetNull($attributes, $key)) {
            return null;
        }
        $intValue = new \Google\Protobuf\Int32Value();
        $intValue->setValue($attributes[$key] ? 1 : 0);
        return $intValue;
    }

    static function convertToInt32Value($attributes , $key): ?\Google\Protobuf\Int32Value
    {
        if (self::shouldSetNull($attributes, $key)) {
            return null;
        }
        $intValue = new \Google\Protobuf\Int32Value();
        $intValue->setValue($attributes[$key]);
        return $intValue;
    }

    static function convertToUInt32Value($attributes , $key): ?\Google\Protobuf\UInt32Value
    {
        if (self::shouldSetNull($attributes, $key)) {
            return null;
        }
        $intValue = new \Google\Protobuf\UInt32Value();
        $intValue->setValue($attributes[$key]);
        return $intValue;
    }
}
