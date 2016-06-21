<?php

namespace Gateway\UPI\ICICI;

use Gateway\Base;

class Gateway extends Base\Gateway
{
    protected $gateway = 'upi_icici';


    protected function genKey()
    {
        return random_alphanum_string(32);
    }

    protected function generateInputString()
    {
        $data = [
            'APIKey'    =>  1,
            'SDKVersion'=>  '1.2',
            'Package'   =>  'com.razorpay',
            'OrderId'   =>  'payment_id+1234',
            'MID'       =>  'merchant_id',
            'TAmt'      =>  '14.50',
            'Currency'  =>  'INR',
            'CustomField1' =>   '',
            'CustomField2' =>   '',
            'CustomField3' =>   '',
        ];

        return http_build_query($data);
    }

    /**
     * Generates checksum as per ICICI's spec
     *
     * Original Java Code below:
     *
     * private String generateChecksum(String inputString) {
     *     String checksum = "";
     *     try {
     *         MessageDigest md = MessageDigest.getInstance("MD5");
     *         md.update(inputString.getBytes());
     *         byte[] digest = md.digest();
     *         StringBuffer sb = new StringBuffer();
     *         for (byte b : digest) {
     *             sb.append(String.format("%02x", b & 0xff));
     *         }
     *         checksum = sb.toString();
     *     }  catch(Exception e) {
     *         e.printStackTrace();
     *     }
     *
     *     return checksum;
     * }
     */
    public static function generateChecksum($inputString)
    {
        $checksum = '';

        // Second argument is raw_output which returns it
        // in binary format of length 16
        $hash = md5($inputString, true);
        $hashArray = unpack('C*', $hash);

        foreach ($hashArray as $byte) {
            $checksum .= sprintf('%02x', $byte & 0xff);
        }

        return $checksum;
    }
}
