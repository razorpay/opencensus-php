<?php

namespace Lib;

/**
 * Hash generator implementing the CRC-16 to ISO/IEC 3309
 */
class CRC16
{
    //Maximum value for 16 bit unsigned
    const MAX_VALUE_UNSIGNED = 65535;

    //Maximum value for 16 bit signed
    const MAX_VALUE_SIGNED = 32767;

    const CRC_LENGTH = 4;

    public function calculateCrc(string $data)
    {
        /**
         *This polynomial is ISO  3309 CRC
         *computation
         */
        $ccittPoly = 4129;

        $byteArray = unpack('C*', $data);

        $expectedCRC = $this->calculateCrcMsb($byteArray, $ccittPoly, self::MAX_VALUE_UNSIGNED);

        $hex = dechex($expectedCRC);

        $crc = str_pad($hex, self::CRC_LENGTH, '0', STR_PAD_LEFT);

        return strtoupper($crc);
    }

    /**
     * As CRC algorithm is based on XOR of
     * most signifant bit of data with the poly
     * and keep moving the stream. So we store all the
     * possible values in lookup table
     *
     * @param int $poly
     * @return array
     */
    private function genCrc16TableMsb(int $poly)
    {
        $table = [];

        /**
         * Table will have value for every number
         * represented by a byte
         */
        for ($number = 0; $number < 256; $number++)
        {
            /**
             * This is shifted by 8 but divident is 16 bits
             * in our case and we are using 8 bit register.
             */
            $finalNum = $number << 8;

            for ($i = 0; $i < 8; $i++)
            {
                if (($finalNum & 0x8000) !== 0)
                {
                    $finalNum = $finalNum << 1 ^ $poly;
                }
                else
                {
                    $finalNum <<= 1;
                }
            }

            /**
             * In php this will be 32 bit
             * We need to convert it to 16 bit digned
             */
            $finalNum = $finalNum & self::MAX_VALUE_UNSIGNED;

            if ($finalNum > self::MAX_VALUE_SIGNED)
            {
                $finalNum -= 65536;
            }

            $table[$number] = $finalNum;
        }

        return $table;
    }

     /**
     * This will calculate crc in integer
     * value.
     *
     * @param array $data
     * @param int   $poly
     * @param int   $initialCrcValue
     * @return int
     */
    private function calculateCrcMsb(array $data, int $poly, int $initialCrcValue)
    {
        //
        // Starting point is at maximum value of 16 bit number
        //
        $crc = $initialCrcValue;

        $crcTable = $this->genCrc16TableMsb($poly);

        for ($p = 1; $p <= count($data); $p++)
        {
            $crc = $crc << 8 & 0xFF00 ^ $crcTable[($crc >> 8 ^ $data[$p] & 0xFF)] & 0xFFFF;
        }

        $crc &= 0xFFFF;

        return $crc;
    }
}
