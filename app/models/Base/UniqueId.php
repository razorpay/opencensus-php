<?php

namespace Models\Base;

use EE\Exception\InvalidArgumentException;

class UniqueId
{
    const ID_LENGTH = \Constants\Fields::ID_LENGTH;

    const ID_KEY = \Constants\Field\Common::ID;

    //const UNIQUE_ID_CHECK_REGEX = '/^[0-9a-f]{'.self::ID_LENGTH.'}$/i';

    public static function verify($id)
    {
        if (is_array($id))
        {
            self::verifyArrayUid($id);
        }
        else if (is_string($id))
        {
            self::verifyStringUid($id);
        }
        else
        {
            throw new InvalidArgumentException('invalid uid: ' . $id);
        }
    }

    public static function verifyArrayUid($id, $key = self::ID_KEY)
    {
        Assert(is_array($id) === true);

        if (! isset($id[$key]))
        {
            throw new InvalidArgumentException('id key not set');
        }

        return self::verifyUid($id[$key]);
    }

    public static function verifyUid($id, $throw = true)
    {
        $uniqueIdCheckRegex = '/^[0-9a-f]{'.self::ID_LENGTH.'}$/i';

        $res = preg_match($uniqueIdCheckRegex, $id);

        if (($res === false) and ($throw))
        {
            throw new BadRequestException($id . ' is not a valid id');
        }

        return $res;
    }

    public static function generateId()
    {
        $len = \Constants\Fields::ID_LENGTH;

        $id = bin2hex(openssl_random_pseudo_bytes($len/2));

        return $id;
    }
}