<?php

namespace RZP\Gateway\Upi\Axis;

use Requests_Transport_cURL;

class Curl extends Requests_Transport_cURL
{
    public function __construct()
    {
        $curl = curl_version();
        $this->version = $curl['version_number'];
        $this->handle = curl_init();

        curl_setopt($this->handle, CURLOPT_HEADER, false);
        curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, 1);
        // This is the only part where we need to disable the encoding for Axis
//        if ($this->version >= self::CURL_7_10_5) {
//            curl_setopt($this->handle, CURLOPT_ENCODING, '');
//        }
        if (defined('CURLOPT_PROTOCOLS')) {
            curl_setopt($this->handle, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        }
        if (defined('CURLOPT_REDIR_PROTOCOLS')) {
            curl_setopt($this->handle, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        }
    }
}
