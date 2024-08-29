<?php

namespace RZP\Models\QrCode\NonVirtualAccountQrCode;

use App;
use RZP\Trace\TraceCode;
use RZP\Models\BharatQr\Tags;
use Razorpay\Trace\Logger as Trace;

/*
 * TLV class stores the Tag Number and Tag Value.
 * It acts as a Data Structure to encapsulate a TLV entry.
 * TLV stands for Tag-Length-Value.
 * - Tag: Identifier for the data element.
 * - Length: Length of the value.
 * - Value: The actual data.
 * */
class TLV {
    private $tagNumber;
    private $tagValue;

    public function __construct($tagNumber, $tagValue) {
        $this->tagNumber = $tagNumber;
        $this->tagValue = $tagValue;
    }

    public function getTagNumber() {
        return $this->tagNumber;
    }

    public function getTagValue() {
        return $this->tagValue;
    }
}

/**
 * Static method to extract the VPA from the QR string.
 * @param string $qr_string - The QR string in TLV format.
 * @return string|null - Returns the VPA if found, otherwise null.
 */
class BharatQrVpaExtracter
{
    protected static $qrString;

    public static function getVPA(string $qrString)
    {

        try {
            self::$qrString = $qrString;

            // Parse the QR string into TLVs
            $tlvs = self::findTags(0);

            // Search for VPA under tag "26"
            $vpa = self::getVpaFromUpiTlv($tlvs);
            if ($vpa !== null)
            {
               return $vpa;
            }
            else
            {
                return null;
            }
        }
        catch (\Throwable $e)
        {
            $app = App::getFacadeRoot();
            $trace = $app['trace'];
            $trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::INVALID_BQR_STRING,
                [
                    "qrString" => $qrString
                ]
            );


        }
    }

    /**
     * Recursive method to parse the QR string into an array of TLV objects.
     * @param int $cursor - Current position in the QR string.
     * @return array - Array of TLV objects.
     */
    private static function findTags($cursor)
    {
        // Extract the tag number
        $tagName = substr(self::$qrString, $cursor, 2);

        // Extract the length of the value
        $tagLength = substr(self::$qrString, $cursor + 2, 2);

        // Remove leading zero from the length if present
        if (strpos($tagLength, "0") === 0)
        {
            $tagLength = substr($tagLength, 1);
        }

        $length = intval($tagLength);
        $tagValue = substr(self::$qrString, $cursor + 4, $length);
        $tlv = new TLV($tagName, $tagValue);
        $newCursor = $cursor + 4 + $length;

        // Check if we have reached the end of the string
        if ($newCursor >= strlen(self::$qrString))
        {
            return [$tlv];
        }

        // Recursively find the next TLVs and add the current one to the list
        $tlvs = self::findTags($newCursor);
        array_push($tlvs, $tlv);
        return $tlvs;
    }

    /**
     * Recursive method to parse sub-tags under a specific tag.
     * @param string $tagString - The string containing sub-tags.
     * @param int $cursor - Current position in the tag string.
     * @return array - Array of sub-TLV objects.
     */
    private static function findSubTags($tagString, $cursor)
    {
        $tagName = substr($tagString, $cursor, 2);
        $tagLength = substr($tagString, $cursor + 2, 2);

        if (strpos($tagLength, "0") === 0)
        {
            $tagLength = substr($tagLength, 1);
        }

        $length = intval($tagLength);
        $tagValue = substr($tagString, $cursor + 4, $length);
        $tlv = new TLV($tagName, $tagValue);
        $newCursor = $cursor + 4 + $length;

        if ($newCursor >= strlen($tagString))
        {
            return [$tlv];
        }

        $tlvs = self::findSubTags($tagString, $newCursor);
        array_push($tlvs, $tlv);
        return $tlvs;
    }

    /**
     * Method to search for the VPA under the specific tag "26".
     * @param array $tlvs - Array of TLV objects.
     * @return string|null - Returns the VPA if found, otherwise null.
     */
    private static function getVpaFromUpiTlv($tlvs)
    {
        foreach ($tlvs as $tlv)
        {
            if ($tlv->getTagNumber() == Tags::UPI_VPA)
            {
                $subTlvs = self::findSubTags($tlv->getTagValue(), 0);
                foreach ($subTlvs as $subTlv)
                {
                    if ($subTlv->getTagNumber() == Tags::UPI_VPA_MERCHANT_VPA)
                    {
                        // Return the VPA
                        return $subTlv->getTagValue();
                    }
                }
            }
        }
        // Return null if VPA not found
        return null;
    }
}

