<?php

namespace RZP\Models\BharatQr;

/**
 * Hash generator implementing the CRC-CCITT-16
 */
class CRC16
{
    private function genCrc16TableMsb($poly)
    {
        $table = [];
        for ($x = 0; $x < 256; $x++)
        {
            $w = $x << 8;
            for ($i = 0; $i < 8; $i++)
            {
                if (($w & 0x8000) != 0)
                {
                    $w = $w << 1 ^ $poly;
                } else
                {
                    $w <<= 1;
                }
            }

            $w = $w & 65535;

            if ($w > 32767)
            {
                $w -= 65536;
            }

            $table[$x] = $w;
        }

        return $table;
    }

    public function calculateCrc($data)
    {
        $ccittPoly = 4129;

        $byteArray = unpack('C*', $data);

        $expectedCRC = $this->calculateCrcMsb($byteArray, $ccittPoly, 65535);

        $hex = dechex($expectedCRC);

        return str_pad($hex, 4, "0", STR_PAD_LEFT);
    }

    private function calculateCrcMsb($data, $poly, $initialCrcValue)
    {
        $crc = $initialCrcValue;

        $crcTable = $this->genCrc16TableMsb($poly);

        for ($p = 1; $p <= sizeof($data); $p++)
        {
            $crc = $crc << 8 & 0xFF00 ^ $crcTable[($crc >> 8 ^ $data[$p] & 0xFF)] & 0xFFFF;
        }

        $crc &= 0xFFFF;

        return($crc);
    }
}
