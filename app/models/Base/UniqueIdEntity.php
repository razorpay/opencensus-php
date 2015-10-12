<?php

namespace Models\Base;

use EE\Exception;

class UniqueIdEntity extends Entity
{
    const ID = 'id';

    const ID_LENGTH = 14;

    protected $genereateIdOnCreate = false;

    //const UNIQUE_ID_CHECK_REGEX = '/^[0-9a-f]{'.self::ID_LENGTH.'}$/i';

    /**
     * Indicates if the IDs are Unique Id
     *
     * @var bool
     */
    protected $uniqueId = true;

    public $incrementing = false;

    protected $secureUid = false;

    public function getId()
    {
        return $this->getAttribute(self::ID);
    }

    public function generateId()
    {
        $this->setAttribute(self::ID, static::generateUniqueId());

        return $this;
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

    public function fillAndGenerateId($attributes)
    {
        $this->fill($attributes);

        $this->generateId();

        return $this;
    }

    public function validateOrGenerateUniqueId()
    {
        $key = $this->getKeyName();

        $value = $this->getAttribute($key);

        if ($value === null)
        {
            if ($this->genereateIdOnCreate)
            {
                $this->generateAndSetUniqueId();
            }
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
            $value = static::generateUniqueId($this->secureUid);

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
        // Timestmap of 1st Jan 2014!!
        // 1388534400
        $ts1stJan2014 = 1388534400;

        // Get current nanotime from 1st Jan 1970
        $nanotime = self::getNanotimeInteger();

        // Subtract nanotime of 1st Jan 2014
        $nanotime -= $ts1stJan2014*1000*1000*1000;

        // Convert to base 62
        $b62 = self::base62($nanotime);

        // Generate 3 random bytes, convert to hex and then to dec
        // @note: do not use bindec i.e. convert directly to dec
        //        because it overflows!
        $dec = hexdec(bin2hex(openssl_random_pseudo_bytes(5)));

        // Convert the random decimal generated to base 62
        $rand = self::base62($dec);

        // Only 4 base 62 digits are needed, so cutoff any more.
        if (strlen($rand) > 4)
            $rand = substr($rand, -4);

        // Combine the base 62 nanotime with 4 base 62 digits
        // and create a unique identifier
        $id = $b62 . $rand;

        assert(strlen($id) === 14);

        return $id;
    }

    protected static function getNanotimeInteger()
    {
        exec('date +%s%N', $nanotime, $status);
        return $nanotime[0];
    }

    protected static function base62($num)
    {
        $index = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        $res = '';
        do {
            $res = $index[$num % 62] . $res;
            $num = intval($num / 62);
        } while ($num);

        return $res;
    }

    protected static function uidToInteger($uid)
    {
        $nanotime = substr($uid, 0, 10);

        $nanotimeInt = self::base10($nanotime);

        $randInt = self::base10(substr($uid, 10));

        $str = $nanotimeInt . $randInt;
        return $str;
    }

    protected static function base10($str)
    {
        $index = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        $res = 0;

        $len = strlen($str);
        for ($i = 0; $i < $len; $i++)
        {
            $res = 62 * $res + strpos($index, $str[$i]);
        }

        return $res;
    }
}