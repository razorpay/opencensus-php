<?php

namespace RZP\Gateway\Netbanking\Icici\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Netbanking;

use RZP\Gateway\Netbanking\Icici\RequestFields;
use RZP\Gateway\Netbanking\Icici\ResponseFields;
use RZP\Gateway\Netbanking\Icici\Confirmation;

class Server extends Base\Mock\Server
{
    protected $openssl_algorithm = 'aes-128-ecb';

    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $master_key = getenv('ICICI_GATEWAY_MASTER_KEY');

        $decrypted_string = openssl_decrypt($input['ES'], $this->openssl_algorithm, $master_key, 0);

        $string = str_replace('%22', '', $decrypted_string);

        parse_str($string, $decrypted_data);

        // $decrypted_data['RU'] now contains the callback URL
        $callbackUrl = $decrypted_data['RU'];

        $post_data = $this->createPostData($decrypted_data); // response from icici bank

        $content = $this->formatPostData($post_data);

        $request = array(
            'url' => $callbackUrl,
            'content' => $content,
            'method' => 'post', // for debug only
        );

        return $this->makePostResponse($request);
    }

    public function verify($input)
    {
        sd($input);
    }

    public function createPostData($input)
    {
        $response = array(
            RequestFields::PAYMENT_REFERENCE_NUBER      => $input[RequestFields::PAYMENT_REFERENCE_NUBER],
            RequestFields::ITEM_CODE                    => $input[RequestFields::ITEM_CODE],
            RequestFields::AMOUNT                       => $input[RequestFields::AMOUNT],
            RequestFields::CURRENCY_CODE                => $input[RequestFields::CURRENCY_CODE],
            ResponseFields::STATUS                      => 'Y',
        );

        if ($input[RequestFields::ONLINE_CONFIRMATION] === Confirmation::YES)
        {
            $response[ResponseFields::BANK_PAYMENT_ID] = '1124324'; // setting it to this for now
        }

        // Forcing PAID to be Y for the mock server

        return $response;
    }

    public function formatPostData($post_data)
    {
        $content['var1'] = 'xyz'; // ICICI dumb data

        $master_key = getenv('ICICI_GATEWAY_MASTER_KEY');

        $content['ES'] = openssl_encrypt(http_build_query($post_data), $this->openssl_algorithm , $master_key, 0);

        return $content;
    }
}
