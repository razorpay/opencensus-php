<?php

namespace RZP\Gateway\Atom;

class Card
{
    public static function encryptCardData($card)
    {
        $expiryMonth = (string) $card['expiry_month'];

        if (strlen($expiryMonth) === 1)
        {
            $expiryMonth = '0'.$expiryMonth;
        }

        $str =  $card['number']         . '|' .
                $card['cvv']            . '|' .
                $card['expiry_year']    . '|' .
                $expiryMonth;

        return self::encode($str);
    }

    protected static function pkcs5_pad($text, $blocksize)
    {
        $pad = $blocksize - (strlen($text) % $blocksize);

        return $text . str_repeat(chr($pad), $pad);
    }

    protected static function pkcs5_unpad($text)
    {
        $pad = ord($text{strlen($text)-1});

        if ($pad > strlen($text))
        {
            return false;
        }

        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad)
        {
            return false;
        }

        return substr($text, 0, -1 * $pad);
    }

    public static  function encode($data)
    {
        $blockSize = mcrypt_get_block_size(MCRYPT_DES);

        $padded = self::pkcs5_pad($data, $blockSize);

        $mcryptEncrypted = mcrypt_encrypt(MCRYPT_DES, "61220121" , $padded , MCRYPT_MODE_ECB);

        $encoded = base64_encode($mcryptEncrypted);

        return $encoded;
    }

    public static function decode($encoded)
    {
        $b64Decoded = base64_decode($encoded);

        $mcryptDecrypted = mcrypt_decrypt(MCRYPT_DES, "61220121" , $b64Decoded, MCRYPT_MODE_ECB);

        $decoded = self::pkcs5_unpad($mcryptDecrypted);

        return $decoded;
    }
}
