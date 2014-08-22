<?php

namespace Models\Base;

use EE\Exception;

class UniqueIdEntity extends Entity
{
    const ID = 'id';

    const ID_LENGTH = '24';

    //const UNIQUE_ID_CHECK_REGEX = '/^[0-9a-f]{'.self::ID_LENGTH.'}$/i';

    /**
     * Indicates if the IDs are Unique Id
     *
     * @var bool
     */
    protected $uniqueId = true;

    public $incrementing = false;

    protected $secureUid = false;

    public function generateId($input)
    {
        $this->setAttribute(self::ID, self::generateUniqueId());
    }

    /**
     * Generate id, before saving if it's not present
     *
     * @param  array  $options
     * @return bool
     */
    public function save(array $options = array())
    {
        $this->validateOrGenerateUniqueId();

        $saved = parent::save($options);

        return $saved;
    }

    public function validateOrGenerateUniqueId()
    {
        $key = $this->getKeyName();

        $value = $this->getAttribute($key);

        if ($value === null)
        {
            $this->generateAndSetUniqueId();
        }
        else
        {
            static::verifyUniqueId($value);
        }
    }

    public function generateAndSetUniqueId()
    {
        $key = $this->getKeyName();

        $value = $this->getAttribute($key);

        if ($value === null)
        {
            $value = self::generateUniqueId($this->secureUid);

            $this->setAttribute($key, $value);
        }
    }

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
            throw new Exception\InvalidArgumentException('invalid uid: ' . $id);
        }
    }

    public static function verifyArrayUid($id, $key = self::ID)
    {
        Assert(is_array($id) === true);

        if (! isset($id[$key]))
        {
            throw new Exception\InvalidArgumentException('id key not set');
        }

        return self::verifyUid($id[$key]);
    }

    public static function verifyUniqueId($id, $throw = true)
    {
        $uniqueIdCheckRegex = '/^[0-9a-f]{'.self::ID_LENGTH.'}$/i';

        $res = preg_match($uniqueIdCheckRegex, $id);

        if (($res === false) and ($throw))
        {
            throw new Exception\BadRequestException($id . ' is not a valid id');
        }

        return $res;
    }

    public static function generateUniqueId()
    {
        $len = self::ID_LENGTH;

        exec('date +%s%N', $nanotime, $status);
        $hextime = dechex($nanotime[0]);

        $id = $hextime . bin2hex(openssl_random_pseudo_bytes(($len - 16)/2));
        //$id = bin2hex(openssl_random_pseudo_bytes(($len)/2));

        return $id;
    }
}