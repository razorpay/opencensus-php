<?php
namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\EntityToProtoConverter;


use Google\Protobuf\StringValue;

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

    static function convertToStringValueOrDefault($attributes , $key, string $default): ?\Google\Protobuf\StringValue
    {
        $stringValue = new \Google\Protobuf\StringValue();
        if (!array_key_exists($key, $attributes)) {
            $stringValue->setValue($default);
            return $stringValue;
        }

        if(is_null($attributes[$key])) {
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

    static function convertToInt32ValueFromBoolOrDefault($attributes , $key, $default = 0): ?\Google\Protobuf\Int32Value
    {
        $intValue = new \Google\Protobuf\Int32Value();
        if (!array_key_exists($key, $attributes)) {
            $intValue->setValue($default);
            return $intValue;
        }

        if(is_null($attributes[$key])) {
            return null;
        }

        $intValue = new \Google\Protobuf\Int32Value();
        $intValue->setValue($attributes[$key] ? 1 : 0);
        return $intValue;
    }

    static function convertToInt32ValueOrDefault($attributes , $key, $default = 0): ?\Google\Protobuf\Int32Value
    {
        $intValue = new \Google\Protobuf\Int32Value();
        if (!array_key_exists($key, $attributes)) {
            $intValue->setValue($default);
            return $intValue;
        }

        if(is_null($attributes[$key])) {
            return null;
        }

        $intValue = new \Google\Protobuf\Int32Value();
        $intValue->setValue($attributes[$key]);
        return $intValue;
    }

    static function convertToUInt32ValueOrDefault($attributes , $key, $default = 0): ?\Google\Protobuf\UInt32Value
    {
        $intValue = new \Google\Protobuf\UInt32Value();
        if (!array_key_exists($key, $attributes)) {
            $intValue->setValue($default);
            return $intValue;
        }

        if(is_null($attributes[$key])) {
            return null;
        }

        $intValue = new \Google\Protobuf\UInt32Value();
        $intValue->setValue($attributes[$key]);
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

    static function convertToInt32ValueOrZero($attributes , $key, $default = 0): ?\Google\Protobuf\Int32Value
    {
        $intValue = new \Google\Protobuf\Int32Value();
        $intValue->setValue($attributes[$key] ?? $default);
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


    public static function convertToInt32ValueOrValue(array $rawAttributes, string $key, int $value = 0)
    {
        $intValue = new \Google\Protobuf\Int32Value();
        $intValue->setValue($attributes[$key] ?? $value);
        return $intValue;
    }

    static function convertToUInt64Value($attributes , $key): ?\Google\Protobuf\UInt64Value
    {
        if (self::shouldSetNull($attributes, $key)) {
            return null;
        }
        $intValue = new \Google\Protobuf\UInt64Value();
        $intValue->setValue($attributes[$key]);
        return $intValue;
    }

    static function convertToInt64Value($attributes , $key): ?\Google\Protobuf\Int64Value
    {
        if (self::shouldSetNull($attributes, $key)) {
            return null;
        }
        $intValue = new \Google\Protobuf\Int64Value();
        $intValue->setValue($attributes[$key]);
        return $intValue;
    }

    static function convertBoolToInt($attribute, $key): int {
        if(self::shouldSetNull($attribute, $key)) {
            return 0;
        }

        // 1 -> 1, true -> 1.
        if($attribute[$key] == true) {
            return 1;
        }

        return 0;
    }


    /**
     * @throws \Exception
     */
    static function convertBoolToNotNullableInt($attribute, $key, $default = 0): int {

        if(!array_key_exists($key, $attribute)) {
            return $default;
        }

        self::notNullCheck($attribute, $key);

        // 1 -> 1, true -> 1.
        if($attribute[$key] == true) {
            return 1;
        }

        return 0;
    }

    /**
     * @throws \Exception
     */
    static function convertToNotNullableInt($attribute, $key, int $default = 0): int {

        if(!array_key_exists($key, $attribute)) {
            return $default;
        }

        self::notNullCheck($attribute, $key);

       return $attribute[$key];
    }

    static function convertToNotNullableString($attribute, $key, string $default = ""): string {

        if(!array_key_exists($key, $attribute)) {
            return $default;
        }

        self::notNullCheck($attribute, $key);

        return $attribute[$key];
    }

    /**
     * @throws \Exception
     */
    static function convertBoolToNotNullableString($attribute, $key, string $default): string {

        if(!array_key_exists($key, $attribute)) {
            return $default;
        }

        self::notNullCheck($attribute, $key);

        return $attribute[$key];
    }
 }
