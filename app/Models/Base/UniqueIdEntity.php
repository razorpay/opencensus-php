<?php

namespace RZP\Models\Base;

use RZP\Exception;
use RZP\Base\Luhn;

class UniqueIdEntity extends Entity
{
    const ID = 'id';

    const ID_LENGTH = 14;

    const UNSIGNED_ID_REGEX = '/[a-zA-Z0-9]{14}\b/';

    const MAC_OS = 'Darwin';

    /**
     * This should be set to true if you expect a unique id to be
     * generated when the entity is being saved. Note that if a unique id
     * is present then it won't be created.
     *
     * Also, if the entity is synced between test and live then it needs
     * to be created before save is called and this cannot be true
     * in those cases.
     *
     * @var boolean
     */
    protected $generateIdOnCreate = false;

    const BASE = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    public static $baseValues = [
        '0' => 0,
        '1' => 1,
        '2' => 2,
        '3' => 3,
        '4' => 4,
        '5' => 5,
        '6' => 6,
        '7' => 7,
        '8' => 8,
        '9' => 9,
        'A' => 10,
        'B' => 11,
        'C' => 12,
        'D' => 13,
        'E' => 14,
        'F' => 15,
        'G' => 16,
        'H' => 17,
        'I' => 18,
        'J' => 19,
        'K' => 20,
        'L' => 21,
        'M' => 22,
        'N' => 23,
        'O' => 24,
        'P' => 25,
        'Q' => 26,
        'R' => 27,
        'S' => 28,
        'T' => 29,
        'U' => 30,
        'V' => 31,
        'W' => 32,
        'X' => 33,
        'Y' => 34,
        'Z' => 35,
        'a' => 36,
        'b' => 37,
        'c' => 38,
        'd' => 39,
        'e' => 40,
        'f' => 41,
        'g' => 42,
        'h' => 43,
        'i' => 44,
        'j' => 45,
        'k' => 46,
        'l' => 47,
        'm' => 48,
        'n' => 49,
        'o' => 50,
        'p' => 51,
        'q' => 52,
        'r' => 53,
        's' => 54,
        't' => 55,
        'u' => 56,
        'v' => 57,
        'w' => 58,
        'x' => 59,
        'y' => 60,
        'z' => 61,
    ];

    //const UNIQUE_ID_CHECK_REGEX = '/^[0-9a-f]{'.self::ID_LENGTH.'}$/i';

    /**
     * Indicates if the IDs are Unique Id
     *
     * @var bool
     */
    protected $uniqueId = true;

    public $incrementing = false;

    protected $secureUid = false;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

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

        if ($this->getIncrementing() === false)
        {
            if ($value === null)
            {
                if ($this->getGenerateIdOnCreate() === true)
                {
                    $this->generateAndSetUniqueId();
                }
            }
            else
            {
                static::verifyUniqueId($value);
            }
        }
    }

    public function generateAndSetUniqueId()
    {
        $key = $this->getKeyName();

        $value = $this->getAttribute($key);

        if ($value === null)
        {
            $value = static::generateUniqueId();

            $this->setAttribute($key, $value);
        }
    }

    public static function verifyArrayUid($id, $key = self::ID)
    {
        assertTrue(is_array($id) === true);

        if (isset($id[$key]) === false)
        {
            throw new Exception\InvalidArgumentException('id key not set');
        }

        return self::verifyUid($id[$key]);
    }

    public static function verifyUniqueId($id, $throw = true)
    {
        $uniqueIdCheckRegex = '/^[0-9a-z]{'. static::ID_LENGTH .'}$/i';

        $res = preg_match($uniqueIdCheckRegex, $id);

        // preg_match() returns int 0 when the pattern does not match
        // and int 1 if a match is found. false (boolean) is returned
        // whenever any error happens.
        $res = (bool) $res;

        if (($res === false) and ($throw === true))
        {
            throw new Exception\BadRequestValidationFailureException(
                $id . ' is not a valid id');
        }

        return $res;
    }

    public static function verifyCapsId($id, $throw = true)
    {
        $uniqueIdCheckRegex = '/^[0-9A-Z]{'. static::ID_LENGTH .'}$/';

        $res = preg_match($uniqueIdCheckRegex, $id);

        // preg_match() returns int 0 when the pattern does not match
        // and int 1 if a match is found. false (boolean) is returned
        // whenever any error happens.
        $res = (bool) $res;

        if (($res === false) and ($throw === true))
        {
            throw new Exception\BadRequestValidationFailureException(
                $id . ' is not a valid id');
        }

        return $res;
    }

    public static function generateUniqueId()
    {
        // Get current nanotime from 1st Jan 1970
        $nanotime = self::getNanotimeInteger();
        return self::generateUniqueIdFromNanoTime($nanotime);
    }

    public static function generateUniqueIdFromTimestamp($timestamp)
    {
        $nanotime = $timestamp * 1000 * 1000 * 1000;

        return self::generateUniqueIdFromNanoTime($nanotime);
    }

    public static function generateUniqueIdFromNanoTime($nanotime)
    {
        $b62 = self::nanotimeToBase62($nanotime);

        // Generate 3 random bytes, convert to hex and then to dec
        // @note: do not use bindec i.e. convert directly to dec
        //        because it overflows!
        $dec = hexdec(bin2hex(random_bytes(5)));

        // Convert the random decimal generated to base 62
        $rand = self::base62($dec);

        // Only 4 base 62 digits are needed, so cutoff any more and pad with
        // 0 if less.

        if (strlen($rand) > 4)
        {
            $rand = substr($rand, -4);
        }

        $rand = str_pad($rand, 4, '0', STR_PAD_LEFT);

        // Combine the base 62 nanotime with 4 base 62 digits
        // and create a unique identifier
        $id = $b62 . $rand;

        assertTrue(strlen($id) === 14);

        return $id;
    }

    protected static function getNanotimeInteger()
    {
        // If we have the php-nanotime extension installed
        if (function_exists('nanotime'))
        {
            return nanotime();
        }

        $cmd = '';

        if (PHP_OS === self::MAC_OS)
        {
            $cmd = '/usr/local/opt/coreutils/libexec/gnubin/';
        }

        $cmd .= 'date +%s%N';
        exec($cmd, $nanotime, $status);

        return $nanotime[0];
    }

    protected static function base62($num)
    {
        $index = self::BASE;

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

    // Use this function to convert RZP ID to epoch timestamp
    // Note : This timestamp generated here might be +/- 1 second from the original ID generation time because of rounding
    public static function uidToTimestamp($uid)
    {
        // Timestamp of 1st Jan 2014
        $ts1stJan2014 = 1388534400;

        $b62 = substr($uid, 0, 10);

        $nanotime = self::base10($b62);

        // Additional seconds elapsed from $ts1stJan2014
        $timestamp = (int) round($nanotime / 1000000000);

        return $ts1stJan2014 + $timestamp;
    }

    public static function nanotimeToBase62($nanotime)
    {
        // Timestamp of 1st Jan 2014
        $ts1stJan2014 = 1388534400;

        // Subtract nanotime of 1st Jan 2014
        $nanotime -= $ts1stJan2014*1000*1000*1000;

        // Convert to base 62
        $b62 = self::base62($nanotime);

        return $b62;
    }

    public static function base10($num, $b = 62)
    {
        $base = self::BASE;

        $limit = strlen($num);

        $res = strpos($base,$num[0]);

        for ($i=1; $i < $limit; $i++)
        {
            $res = $b * $res + strpos($base,$num[$i]);
        }

        return $res;
    }

    public static function generateUniqueIdWithCheckDigit()
    {
        $base62 = self::generateUniqueId();

        $base62 = substr($base62, 0, 13);

        $digit = Luhn::computeCheckDigit($base62, 62);

        return $base62 . $digit;
    }

    public static function getCheckDigit($uid)
    {
        $uid = substr($uid, 0, 13);

        $digit = Luhn::computeCheckDigit($uid, 62);

        return $digit;
    }

    public static function isValidBase62Id($num)
    {
        return Luhn::isValid($num, 62);
    }

    public function getGenerateIdOnCreate()
    {
        return $this->generateIdOnCreate;
    }

}
