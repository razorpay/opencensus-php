<?php

namespace Models\Manager;

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
            throw new \InvalidArgumentException('invalid uid: ' . $id);
        }
    }

    public static function verifyArrayUid($id, $key = self::ID_KEY)
    {
        Assert(is_array($id) === true);

        if (! isset($id[$key]))
        {
            throw new \InvalidArgumentException('id key not set');
        }

        return self::verifyUid($id[$key]);
    }

    public static function verifyUid($id, $throw = false)
    {
        $uniqueIdCheckRegex = '/^[0-9a-f]{'.self::ID_LENGTH.'}$/i';

        return preg_match($uniqueIdCheckRegex, $id);

        if ($throw)
        {
            throw new \InvalidArgumentException($id . ' is not a valid id');
        }
    }
}