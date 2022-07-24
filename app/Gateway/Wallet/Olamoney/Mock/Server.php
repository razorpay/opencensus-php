<?php

namespace RZP\Gateway\Wallet\Olamoney\Mock;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Wallet\Base\Otp;
use RZP\Gateway\Wallet\Olamoney;
use RZP\Gateway\Wallet\Olamoney\Command;
use RZP\Gateway\Wallet\Olamoney\ResponseCode;
use RZP\Gateway\Wallet\Olamoney\RequestFields;
use RZP\Gateway\Wallet\Olamoney\ResponseFields;
use RZP\Gateway\Wallet\Olamoney\Status;
use RZP\Models\Payment;
use phpseclib\Crypt\RSA;
use phpseclib\Crypt\AES;
use RZP\Trace\TraceCode;

class Server extends Base\Mock\Server
{
    public function eligibility($input)
    {
        $content = json_decode($input, true);

        if ($content[RequestFields::USER_INFO][RequestFields::MOBILE_NUMBER] === '9008129412')
        {
            $responseContent[ResponseFields::ELIGIBILITY] = [ResponseFields::STATUS_CODE => 'OC_011'];
        }
        elseif ($content[RequestFields::USER_INFO][RequestFields::MOBILE_NUMBER] === '9011219027')
        {
            $responseContent[ResponseFields::ELIGIBILITY] = [ResponseFields::STATUS_CODE => 'OC_010'];
        }
        else
        {
            $responseContent[ResponseFields::ELIGIBILITY] = [ResponseFields::STATUS_CODE => 'OC_000'];
        }

        return $this->makeResponse($responseContent);
    }

    public function authorize($input)
    {
        if (gettype($input) === 'string')
        {
            return $this->eligibility($input);
        }
        else
        {
            if (isset($input['signature']))
            {
                return $this->authorizeV2($input);
            }

            parent::authorize($input);

            $bill = RequestFields::BILL;

            $input[$bill] = json_decode(base64_decode(urldecode($input[$bill])), true);


            $this->validateActionInput($input, $input[$bill][RequestFields::COMMAND]);

            $bill = $input['bill'];

            $content = [
                ResponseFields::TYPE              => 'credit',
                ResponseFields::STATUS            => Status::SUCCESS,
                ResponseFields::MERCHANT_BILL_ID  => $bill[RequestFields::MERCHANT_REFERENCE_ID],
                ResponseFields::TRANSACTION_ID    => 'ola_txn_id',
                ResponseFields::AMOUNT            => $bill[RequestFields::AMOUNT],
                ResponseFields::COMMENTS          => $bill[RequestFields::COMMENTS],
                ResponseFields::UDF               => $bill[RequestFields::UDF],
                ResponseFields::TIMESTAMP         => time(),
            ];

            $content[ResponseFields::HASH] = $this->generateHash($content);

            $paymentId = $input['paymentId'];

            $publicId = $this->getSignedPaymentId($paymentId);

            $url = $this->route->getPublicCallbackUrlWithHash($publicId);

            $url .= '?' . http_build_query($content);

            return \Redirect::to($url);
        }
    }

    public function authorizeV2($input)
    {
        if (gettype($input) === 'string')
        {
            return $this->eligibility($input);
        }
        else
        {
            parent::authorize($input);

            $content = [
                ResponseFields::TYPE              => 'debit',
                ResponseFields::STATUS            => Status::SUCCESS,
                ResponseFields::MERCHANT_BILL_ID  => $input[RequestFields::UNIQUE_ID],
                ResponseFields::TRANSACTION_ID    => 'dqtf-j717-qlfb',
                ResponseFields::AMOUNT            => $input[RequestFields::AMOUNT],
                ResponseFields::COMMENTS          => $input[RequestFields::COMMENTS],
                ResponseFields::UDF               => $input[RequestFields::UDF],
                ResponseFields::IS_CASHBACK_ATTEMPTED =>"false",
                ResponseFields::IS_CASHBACK_SUCCESSFUL =>"false",
                ResponseFields::TIMESTAMP         => time(),
                ResponseFields::SALT              => 'merchant_salt'

            ];

            $this->content($content);

            $content[ResponseFields::HASH] = $this->generateHash($content);

            $uuid = 'dummyuuid';
            $encryptedXTtenantKey = $this->encryptXTenantKey($uuid.':'.time());

            if ($content[ResponseFields::TRANSACTION_ID] == 'invalid_body')
            {
                $output[ResponseFields::BODY] = 'Invalid Body';
            }

            $output = [
                ResponseFields::X_TENANT     => 'Ola',
                ResponseFields::X_TENANT_KEY => $encryptedXTtenantKey,
                ResponseFields::X_AUTH_KEY   => $this->signTenantKey($encryptedXTtenantKey),
                ResponseFields::BODY         => $this->encryptBody($content, $uuid)
            ];

            $paymentId = $input['paymentId'];

            $publicId = $this->getSignedPaymentId($paymentId);

            $url = $this->route->getPublicCallbackUrlWithHash($publicId);

            $url .= '?' . http_build_query($output);

            return \Redirect::to($url);
        }
    }

    public function otpGenerate($input)
    {
        if (gettype($input) === 'string')
        {
            return $this->eligibility($input);
        }
        else
        {
            $this->validateActionInput($input, 'otpGenerate');

            if (in_array($input['phone'], ['9008119029', '9022219029']))
            {
                $responseContent = [ResponseFields::STATUS => Status::ERROR];
            }
            elseif ($input['phone'] === '9022219027')
            {
                $responseContent = [
                    ResponseFields::STATUS  => Status::FAILED,
                    ResponseFields::MESSAGE =>
                        'The email ID provided is already registered with us. Please try with a different email ID.',
                ];
            }
            else
            {
                $responseContent = [
                    ResponseFields::STATUS    => Status::SUCCESS,
                    ResponseFields::MESSAGE   => '',
                ];
            }

            $response = $this->makeResponse($responseContent);

            if ($input['phone'] === '9022219029')
            {
                $response->setStatusCode(429);
            }

            return $response;
        }
    }

    public function otpSubmit($input)
    {
        $this->validateActionInput($input, 'otpSubmit');

        $responseContent = [
            ResponseFields::STATUS          => Status::SUCCESS,
            ResponseFields::MESSAGE         => '',
            ResponseFields::ACCESS_TOKEN    => 'success_access_token',
            ResponseFields::REFRESH_TOKEN   => 'success_refresh_token',
        ];

        if ($input[RequestFields::OTP] === Otp::INCORRECT)
        {
            $responseContent = [
                ResponseFields::STATUS      => 'FAILED',
                ResponseFields::MESSAGE     => 'Invalid OTP',
            ];
        }
        else if ($input[RequestFields::OTP] === Otp::INSUFFICIENT_BALANCE)
        {
            $responseContent = [
                ResponseFields::STATUS          => Status::SUCCESS,
                ResponseFields::MESSAGE         => '',
                ResponseFields::ACCESS_TOKEN    => 'insufficient_balance_access_token',
                ResponseFields::REFRESH_TOKEN   => 'insufficient_balance_refresh_token',
            ];
        }

        return $this->makeResponse($responseContent);
    }

    public function getBalance($input)
    {
        $input = json_decode($input, true);

        $this->validateActionInput($input, 'checkBalance');

        $balance = 999999.00;

        if ($input[RequestFields::USER_ACCESS_TOKEN] === 'insufficient_balance_access_token')
        {
            $balance = 0;
        }

        $responseContent = [
            ResponseFields::STATUS          => Status::SUCCESS,
            ResponseFields::COMMENTS        => 'olaComments',
            ResponseFields::AMOUNT          => $balance,
            ResponseFields::BALANCE_TYPE    => 'olaBalanceType',
        ];

        return $this->makeResponse($responseContent);
    }

    // Not being used right now as topup is a redirection flow
    public function topupWallet($input)
    {
        $this->validateActionInput($input, 'topupWallet');

        $this->topupRequest = $input;

        $response = ['status' => 'success'];

        return $this->makeResponse($response);
    }

    public function debitWallet($input)
    {
        $input = json_decode($input, true);

        $this->validateActionInput($input, Command::DEBIT);

        $responseContent = [
            ResponseFields::TYPE                    => 'debit',
            ResponseFields::STATUS                  => Status::SUCCESS,
            ResponseFields::MERCHANT_BILL_ID        => $input[RequestFields::UNIQUE_ID],
            ResponseFields::TRANSACTION_ID          => 'olaUniqTxnId',
            ResponseFields::AMOUNT                  => $input[RequestFields::AMOUNT],
            ResponseFields::COMMENTS                => $input[RequestFields::COMMENTS],
            ResponseFields::UDF                     => $input[RequestFields::UDF],
            ResponseFields::IS_CASHBACK_ATTEMPTED   => 'NA',
            ResponseFields::IS_CASHBACK_SUCCESSFUL  => 'NA',
            ResponseFields::TIMESTAMP               => time(),
        ];

        $this->content($responseContent);

        if (empty($responseContent) === false)
        {
            $responseContent[ResponseFields::HASH] = $this->generateHash($responseContent);
        }

        return $this->makeResponse($responseContent);
    }

    public function refund($input)
    {
        $input = json_decode($input, true);

        parent::refund($input);

        $this->validateActionInput($input, Command::REFUND);

        $responseContent = [
            ResponseFields::TYPE              => 'refund',
            ResponseFields::TRANSACTION_ID    => 'bgho5botne16',
            ResponseFields::MERCHANT_BILL_ID  => 'cd1501cea88e4654898d8b2a266bc467',
            ResponseFields::AMOUNT            => '20.0',
            ResponseFields::TIMESTAMP         => '1439473847354',
            ResponseFields::COMMENTS          => 'test',
            ResponseFields::UDF               => 'test',
        ];

        // error amount
        if ($input[RequestFields::AMOUNT] === '13.00')
        {
            $responseContent[ResponseFields::STATUS] = Status::ERROR;
        }
        else
        {
            $responseContent[ResponseFields::STATUS] = Status::SUCCESS;
        }

        return $this->makeResponse($responseContent);
    }

    public function verify($input)
    {
        parent::verify($input);

        $this->validateActionInput($this->mockRequest['content']);

        $response = [
            ResponseFields::STATUS             => 'completed',
            ResponseFields::AMOUNT             => '500.00',
            ResponseFields::TYPE               => 'debit',
            ResponseFields::UNIQUE_BILL_ID     => 'bgho5botne16',
            ResponseFields::GLOBAL_MERCHANT_ID => 'ek78-s35w-ffm8'
        ];

        $this->content($response);

        return $this->makeResponse($response);
    }

    protected function makeResponse($json, $content_type = 'application/json; charset=UTF-8')
    {
        $response = \Response::make($json);

        $response->headers->set('Content-Type', $content_type);
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }

    public function signTenantKey($tenantKey)
    {
        //$ola_private_key= (new Gateway)->getOlaPublicKey();
        $ola_private_key = 'MIIEvwIBADANBgkqhkiG9w0BAQEFAASCBKkwggSlAgEAAoIBAQCWF5PFBUAGMulXKgzguT3PaxriJLvJ82MjRJUFjgPG9X1+tQrMy/it28E4059AExIDHA74jxM+o8CLtiNRz9Y7+ciIPzavkd/KfxP2Ic7kTaO6M8LLfITUA6dS4dGgPJGAeMZKWVQG/IwoeWNJOrOinaxnO3l77RmYr3E/UkSVnY435Gl2jdWivPr8u3eErnsu2CtWaB+VCASj44Honl7kqXdq8ZdZLVwDGjrfLgawVm79cOI3JtQwI/9gn+zpJbzpbhp65yO0PAQ+RaojpCdn3DiHxYmNlSdN4dkFW45dp3s6iffsAust/SGBJan+VOQKQtZt4a9VjmglZwuNxw4FAgMBAAECggEAEM2Y7IC29zqx5uE9SddTNSpvewvTvjsySRt/d3y7rYWERDAuglj/gS9OBXejp3+7D4APqQITjHq2rq14bMtQ16wSKDazf5pcLIZnjLGiQOr0Pn9W+oL5N+ckz2Gan07Il1JuGJrBjnqtkkZsuCELRVRTnccJxbb4m6BglE84gGtT3+PQx9/4ejmeE2J3hY8LalCVIDZlWvySqIJGeaOnpzt4cKSTk85ZNwwP9a2U33fZsoSjZw1tGJAlC1+NiXq+GFsmPRTqb7bEg1NWE9KS4m+B3x8uEz68CqXEqcl15Iw/YQMxzWs9baCCXpfpV5dudVH0VQKfdpFzmnE2FGK1rQKBgQDfqKRuSL8gRuxihadR8nzVzZQMo/0qef5LZ1KUIyiSAHjH2BKFHSeKoYc+L5QgufQLA5cdjHOUFy8XM04/b2xhLAhAzOn7PshEinXtQ554vih6+ZVWoU/vrR2gAlsg9UETsiX9U8JoOwUFa+TZiAS/015vuNdaMyGrS+WtsQJ+iwKBgQCry6h+PiU6OB4LZe2PU6X5xTIipZgoPlDhShJOvPoZaaV6x1kLc79NF9akR5H+WZdHzjlxoq5cQWM/fiRkM6/QGZuh6ivt/kpBN4hezTpPIRZwLb73+eJ/s2e/vm9typSIosiT9P4VSg+Xhq0V2BZb8e6RGg2heIFjN3maPS5HrwKBgQDIw0y2Yj6N7pwJ5AdJm+1KzfpzTlDWbCND9D9AEj88r4e7e81EB+OSoWQRAgxpRAI4UMS5FXY6HIV8weUfNBmJMElIQahWiwih3df1XplFsQwNNzRCSxLCBhdtpi++6ee8klFfkGwVu8TKFQub6Gi6+DTw/G7y3KsAZGSLATVH+QKBgQCZAMkPpkmBkHkxrZXmEJnB2d7M/K6HKPjfrRihB6229GBs+R5VFMFL5+9CYHumDCSvzvtaOYkQoSvDYJUIqP/sVuJFUknNrKx1aQALbrx/vPg+8H8kW2leUmoUW4biQYoIJvJ807V3QH6idU+yJMHFIbNXh9yb8rdJph6nP9X4AQKBgQCtHehQ09hduCObOC0URDuowQV1/9hU/2PL7ezZUsQ1+B6G6KVnRm+S8ak5a7MsaiVjbVjAlXVlHCLHAhcMkr3Vy3W0HapVBC5/T+JLf16OrqjpxmaQ1YPmbzbtg2PhkY0wDHEUETTpIi7OeLiT3Cg28NNTNkLvTTHb0FWZrxLgvw==';

        $rsa = new RSA();

        extract($rsa->createKey());

        $rsa->setPrivateKeyFormat(RSA::PRIVATE_FORMAT_PKCS8);

        $rsa->loadKey(base64_decode($ola_private_key), RSA::PRIVATE_FORMAT_PKCS8);

        $rsa->setSignatureMode(RSA::SIGNATURE_PKCS1);

        return base64_encode($rsa->sign(base64_decode($tenantKey)));
    }

    public function encryptBody($content,$uuid)
    {
        //$getIvParamFromConfig = (new Gateway)->getIV();
        $getIvParamFromConfig = 'OlaM0neyEncrypt0';

        $cipher = new AES();

        $cipher->setKey($uuid);

        $cipher->setIV($getIvParamFromConfig);

        return base64_encode(($cipher->encrypt((json_encode($content)))));
    }

    public function encryptXTenantKey($content)
    {
        //$public_key= (new Gateway)->getPublicKey();
        $public_key = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAlheTxQVABjLpVyoM4Lk9z2sa4iS7yfNjI0SVBY4DxvV9frUKzMv4rdvBONOfQBMSAxwO+I8TPqPAi7YjUc/WO/nIiD82r5Hfyn8T9iHO5E2jujPCy3yE1AOnUuHRoDyRgHjGSllUBvyMKHljSTqzop2sZzt5e+0ZmK9xP1JElZ2ON+Rpdo3Vorz6/Lt3hK57LtgrVmgflQgEo+OB6J5e5Kl3avGXWS1cAxo63y4GsFZu/XDiNybUMCP/YJ/s6SW86W4aeucjtDwEPkWqI6QnZ9w4h8WJjZUnTeHZBVuOXad7Oon37ALrLf0hgSWp/lTkCkLWbeGvVY5oJWcLjccOBQIDAQAB';

        $rsa = new RSA();

        $rsa->setPublicKeyFormat(RSA::PUBLIC_FORMAT_PKCS8);

        $rsa->loadKey(base64_decode($public_key));

        $rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);

        return base64_encode($rsa->encrypt(($content)));
    }
}
