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

    /** Generates request object for the status call */
    protected function statusData()
    {
        return [
            "merchantId"        =>  "merchantId",
            "subMerchantId"     =>  "12234",
            "terminalId"        =>  "2342342",
            "merchantTranId"    =>  "612413726581"
        ];
    }


    protected function collectPayData()
    {
        return [
            "payerVa"       =>  "testing1@imobile",
            "amount"        =>  "5.00",
            "note"          =>  "collect-pay-request",
            "collectByDate" =>  "15/06/2016 11: 01 AM",
            "merchantId"    =>  "merchantId",
            "subMerchantId" =>  "12234",
            "terminalId"    =>  "2342342",
            "merchantTranId"=>  "345345345",
            "billNumber"    =>  "sdf234234"
        ];
    }

    protected function generateInputString()
    {
        $data = [
            'APIKey'    =>  111111,
            'SDKVersion'=>  '1.8',
            'Package'   =>  'com.razorpay',
            'OrderId'   =>  '',
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

    /**
     *
     * Encrypts inputString as per ICICI logic
     *
     * public String encrypt(String key,String inputString) {
     *     try {
     *         SecretKeySpec secretKeySpec = new
     *         SecretKeySpec(key.getBytes(), "AES");
     *         Cipher cipher = Cipher.getInstance("AES/ECB/PKCS5Padding");
     *         // 1 = ENCRYPT_MODE
     *         cipher.init(1, secretKeySpec);
     *         byte[] aBytes = cipher.doFinal(inputString.getBytes());
     *         BASE64Encoder encoder = new BASE64Encoder();
     *         String base64 = encoder.encode(aBytes).toString();
     *         base64 = URLEncoder.encode(base64, "UTF-8");
     *         return base64;
     *     }
     *     catch(Exception ex) {
     *         System.out.println("Exception occured in encrypt :"+ex.toString());
     *     }
     *     return null;
     * }
     *
     */
    public static function encrypt($key, $input)
    {
        $size = mcrypt_get_block_size('aes', 'ecb');
        $input = $this->PKCS5Padding($input, $size);

        $td = mcrypt_module_open('aes', '', 'ecb', '');
        $iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, $key, $iv);

        $data = mcrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);

        $data = base64_encode($data);
        return $data;
    }

    protected function PKCS5Padding($text, $blocksize)
    {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }
}
