<?php

namespace RZP\Gateway\Wallet\Amazonpay\Sdk;

use App;
use RZP\Constants\HashAlgo;
use RZP\Exception\RuntimeException;
use RZP\Gateway\Wallet\Amazonpay\Url;
use RZP\Gateway\Wallet\Amazonpay\Config;
use RZP\Gateway\Wallet\Amazonpay\RequestFields;

/**
 * This class was taken from Amazon Pay's SDK and modified to suit our requirements.
 * Initial SDK
 * @see https://drive.google.com/open?id=1ZqBYMgqNs0F5q-PB_7yZV5AsS0E4v9iM
 * Upgraded SDK
 * @see https://drive.google.com/file/d/1YrIHN0IYWKuPmZ6ls1R5fpC6bYcAC4C8/view?usp=sharing
 * Class PWAINBackendSDK
 * @package RZP\Gateway\Wallet\Amazonpay\Sdk
 */
final class PWAINBackendSDK
{
    /**
     * The url used to generate the request signature
     * @var string
     */
    private $serviceUrl = Url::SERVICE_HOSTNAME;

    /**
     * Request method used to generate request signature
     * @var string
     */
    private $urlScheme = 'POST';

    private $path = '/';

    /**
     * Amazon MWS service url
     * @var string
     */
    private $mwsServiceUrl;

    /**
     * MWS relative path parameter based on mode === sandbox
     * @var string
     */
    private $modePath;

    /**
     * Config for the SDK
     * @var array
     */
    private $config = [
        Config::MERCHANT_ID         => null,
        Config::SECRET_KEY          => null,
        Config::ACCESS_KEY          => null,
        Config::BASE_URL            => null,
        Config::CURRENCY_CODE       => null,
        Config::SANDBOX             => null,
        Config::PLATFORM_ID         => null,
        Config::APPLICATION_NAME    => null,
        Config::APPLICATION_VERSION => null,
        Config::HANDLE_THROTTLE     => true
    ];

    /**
     * New Endpoint as per upgraded SDK
     * @var array
     */
    private $api_path = array(
        'RefundPayment' => '/v2/payments/refund',
        'GetRefundDetails' => '/v2/payments/refund/details',
        'ListOrderReference' => '/v2/payments/orderReference',
    );

    /**
     * @var bool
     */
    private $mock;

    public function __construct(array $config = null, bool $mock)
    {
        if (is_null($config) === false)
        {
            $this->checkAndSetConfigKeys($config);
        }
        else
        {
            throw new \Exception ('$config cannot be null.');
        }

        $this->mock = $mock;
    }

    /**
     * To get process payment Url with given parameters.
     * Calculates signed and encrypted payload and generates url
     *
     * @param array $parameters
     * @param string $redirectUrl
     * @return string $processPaymentUrl
     * @throws \Exception
     */
    public function getProcessPaymentUrl(array $parameters, string $redirectUrl)
    {
        $this->validateNotNull($redirectUrl, "Redirect Url");

        $message = 'Invalid redirect URL. Please remember to input http:// or https:// as well. URL scheme';

        $this->validateNotNull(parse_url($redirectUrl, PHP_URL_SCHEME),  $message);

        $queryParameters = $this->generateSignatureAndEncrypt($parameters);

        return $this->constructPaymentRelativeUrl($queryParameters, $redirectUrl);
    }

    /**
     * generates the signature for the parameters given with the aws secret key
     * provided and encrypts parameters along with signature
     * @see https://docs.aws.amazon.com/encryption-sdk/latest/developer-guide/how-it-works.html
     *
     * @param array $parameters
     * @return string
     * @throws \Exception
     */
    public function generateSignatureAndEncrypt(array $parameters = [])
    {
        $parameters = $this->calculateSignForEncryption($parameters);

        $parametersToEncrypt = $this->getParametersToEncrypted($parameters);

        // Plaintext
        $dataToEncrpyt = $this->getParametersAsString($parametersToEncrypt);

        // Randomly generated symmetric key to encrypt the plaintext
        $sessionKey = $this->getSecureRandomKey();

        // We get the public key used to encrypt the generated random symmetric key
        $pubKey = $this->getPublicKey();

        // $crypted is passed in by reference and is used later as part of $encryptedResponse
        // We encrypt the key using the public key above using the RSA encryption scheme
        openssl_public_encrypt($sessionKey, $crypted, $pubKey, OPENSSL_PKCS1_OAEP_PADDING);

        $iv = $this->getSecureRandomKey();

        // We encrypt the plaintext using the random key and the random iv to generate the ciphertext
        $encyptedData = AESGCM::encryptAndAppendTag($sessionKey, $iv, $dataToEncrpyt);

        $encryptedResponse = [
            RequestFields::PAYLOAD => urlencode(base64_encode($encyptedData)),
            RequestFields::KEY     => urlencode(base64_encode($crypted)),
            RequestFields::IV      => urlencode(base64_encode($iv)),
        ];

        return $this->getParametersAsString($encryptedResponse);
    }

    /**
     * This method was added as a custom implementation of the decryption logic.
     * We decrypt the input information using Envelope Decryption by the AES-GCM algorithm
     * @see https://docs.aws.amazon.com/encryption-sdk/latest/developer-guide/how-it-works.html
     *
     * PHP 7.1 supports AES-GCM via Open-SSL. When we migrate to 7.1, AESGCM will take care of itg.
     * @see https://wiki.php.net/rfc/openssl_aead
     *
     * @param array $encryptedData
     * @return array
     */
    public function getDecryptedData(array $encryptedData): array
    {
        $cipherText = base64_decode($encryptedData[RequestFields::PAYLOAD]);

        $encryptedKey = base64_decode($encryptedData[RequestFields::KEY]);

        $iv = base64_decode($encryptedData[RequestFields::IV]);

        $privateKey = file_get_contents(__DIR__ . '/mock-private.cer');

        // We decrypt the key using the RSA algorithm with the private key
        openssl_private_decrypt($encryptedKey, $decryptedKey, $privateKey, OPENSSL_PKCS1_OAEP_PADDING);

        // Auth tag is a 16 byte string appended to the end of the ciphertext
        $authTag = mb_substr($cipherText, -16, 16, '8bit');

        // We remove the authTag from the cipherText
        $cipherText = mb_substr($cipherText, 0, strlen($cipherText) - strlen($authTag), '8bit');

        // We now decrypt the cipherText using AES-GCM algorithm
        $plainText = AESGCM::decrypt($decryptedKey, $iv, $cipherText, null, $authTag);

        parse_str($plainText, $decryptedData);

        $this->verifyMockGatewaySignature($decryptedData);

        return $decryptedData;
    }

    /**
     * This method is used to verify the signature sent across in server to server API's
     * S2S API's - Verify, Refund etc
     *
     * @param array $parameters
     * @throws RuntimeException
     */
    public function verifyMockGatewayS2sSignature(array $parameters = [])
    {
        $this->createServiceUrl("ListOrderReference");

        $actualSignature = $parameters[RequestFields::SIGNATURE];

        unset($parameters[RequestFields::SIGNATURE]);
        unset($parameters[RequestFields::IS_SANDBOX]);

        $parameters = $this->signParameters($parameters);

        $generatedSignature = $parameters[RequestFields::SIGNATURE];

        if (hash_equals($actualSignature, $generatedSignature) === false)
        {
            throw new RuntimeException('Failed checksum verification');
        }
    }

    /**
     * This method was added to compute the verify signature in the mock server
     * @param array $parameters
     * @return string
     */
    public function getVerifySign(array $parameters): string
    {
        $parameters = $this->calculateSignForVerification($parameters);

        return $parameters['Signature'];
    }

    /**
     * calculates the signature for the parameters given with the aws secret key
     * provided and verifies it against the signature provided.
     *
     * @param $paymentResponseMap
     * @return array
     */
    public function verifySignature($paymentResponseMap): array
    {
        $providedSignature = $paymentResponseMap['signature'];

        unset($paymentResponseMap['signature']);

        $this->validateNotNull($providedSignature, "ProvidedSignature");

        $calculatedSignature = $this->calculateSignForVerification($paymentResponseMap);

        return [$calculatedSignature['Signature'], $providedSignature];
    }

    /**
     * Refund API call - Refunds a previously captured amount.
     * @see http://docs.developer.amazonservices.com/en_US/off_amazon_payments/OffAmazonPayments_Refund.html
     *
     * @param array $requestParameters
     * @return mixed|\SimpleXMLElement|string
     */
    public function refund(array $requestParameters = [])
    {
        return $this->setParametersAndReturnUrl(['Action' => 'RefundPayment'], $requestParameters);
    }

    /* GetRefundDetails API call - Returns the status of a particular refund.
     * @see http://docs.developer.amazonservices.com/en_US/off_amazon_payments/OffAmazonPayments_GetRefundDetails.html
     *
     * @param requestParameters['merchant_id'] - [String]
     * @param requestParameters['amazon_refund_id'] - [String]
     * @optional requestParameters['mws_auth_token'] - [String]
     */
    public function getRefundDetails($requestParameters = array())
    {
        $parameters['Action'] = 'GetRefundDetails';

        return $this->setParametersAndReturnUrl($parameters, $requestParameters);
    }

    /**
     * ListOrderReference API call - provide a list of Orders with some
     * information about OrderDetails corresponding to a sellerOrderID.
     *
     * @param array $requestParameters
     * @return string
     */
    public function listOrderReference($requestParameters = [])
    {
        $parameters['Action'] = 'ListOrderReference';

        return $this->setParametersAndReturnUrl($parameters, $requestParameters);
    }

    /**
     * ListOrderReferenceByNextToken API call - provide a list of Orders with
     * some information about OrderDetails corresponding to next page token.
     * @param requestParameters['merchant_id'] - [String]
     * @param requestParameters['next_page_token'] - [String]
     * @optional requestParameters['created_time_range_start'] - [String]
     * @optional requestParameters['created_time_range_end'] - [String]
     */
    public function listOrderReferenceByNextToken($requestParameters = array())
    {
        $parameters['Action'] = 'ListOrderReferenceByNextToken';
        $parameters['Version'] = '2013-01-01';

        return $this->setParametersAndReturnUrl($parameters, $requestParameters);
    }

    /**
     * FetchTransactionDetails API call - it fetches details of transaction by calling get charge status
     * @param requestParameters['transactionId'] - [String]
     * @param requestParameters['transactionIdType'] - [String]
     */
    public function fetchTransactionDetails($requestParameters = array())
    {
        throw new RuntimeException('Not Implemented');
    }

    ######### Private Functions ##########

    private function verifyMockGatewaySignature(array $decryptedData)
    {
        $actualSign = $decryptedData['Signature'];

        unset($decryptedData['Signature']);

        $decryptedData = $this->calculateSignForEncryption($decryptedData);

        $generatedSign = $decryptedData['Signature'];

        if (hash_equals($actualSign, $generatedSign) === false)
        {
            throw new RuntimeException('Failed checksum verification');
        }
    }

    /**
     * @param string $queryParameters
     * @param string $redirectUrl
     * @return string
     */
    private function constructPaymentRelativeUrl(string $queryParameters, string $redirectUrl)
    {
        return $queryParameters . '&redirectUrl=' . urlencode($redirectUrl);
    }

    /**
     * Generates a random 128-bit (16-byte) string
     * @return string
     */
    private function getSecureRandomKey()
    {
        return openssl_random_pseudo_bytes(16);
    }

    private function getPublicKey()
    {
        if ($this->mock === true)
        {
            return file_get_contents(__DIR__ . '/mock-public.cer');
        }

        return file_get_contents(__DIR__ . '/public.cer');
    }

    private function addDefaultParameters(array $parameters): array
    {
        $parameters['AWSAccessKeyId'] = $this->config['access_key'];

        $parameters['SignatureMethod'] = 'HmacSHA256';

        $parameters['SignatureVersion'] = 2;

        return $parameters;
    }

    private function addParametersForEncryption($parameters)
    {
        $parameters['sellerId'] = $this->config['merchant_id'];

        $parameters['startTime'] = time();

        if ($this->mock === true)
        {
            //
            // We want to ensure that for test cases, we will be able to avoid minor time differences
            // when gateway computes sign, and when mock server computes sign - so we hard code this value
            //
            $parameters['startTime'] = 1519048934;
        }

        return $parameters;
    }

    /**
     * This method calculates the signature for the parameters given with the
     * aws secret key provided.
     *
     * @param array $parameters
     * @return the
     */
    private function calculateSignForEncryption(array $parameters = [])
    {
        $this->validateNotEmpty($parameters, "parameters");

        $parameters = $this->addParametersForEncryption($parameters);

        return $this->signParameters($parameters);
    }

    private function calculateSignForVerification(array $parameters = [])
    {
        $this->validateNotEmpty($parameters, "parameters");

        return $this->signParameters($parameters);
    }

    /**
     * This method return signature after signing the parameters with the given
     * secret key.
     *
     * @param array $parameters
     * @return array|mixed
     */
    private function signParameters(array $parameters = [])
    {
        $parameters = $this->urlEncodeParams($parameters);

        $parameters = $this->addDefaultParameters($parameters);

        uksort($parameters, 'strcmp');

        $stringToSign = $this->calculateStringToSignV2($parameters);

        $sign = $this->sign($stringToSign, $this->config['secret_key']);

        $parameters['Signature'] = $sign;

        return $parameters;
    }

    /**
     * @param array $parameters
     * @return string
     */
    private function calculateStringToSignV2(array $parameters)
    {
        $data = [$this->urlScheme, $this->serviceUrl, $this->path, ""];

        $data = implode("\n", $data);

        $data .= $this->getParametersAsString($parameters);

        return $data;
    }

    private function getParametersToEncrypted($parameters)
    {
        $parameters['Signature'] = $this->urlEncode($parameters['Signature'], false);

        unset($parameters['SignatureMethod']);

        unset($parameters['SignatureVersion']);

        return $parameters;
    }

    private function urlEncodeParams($parameters)
    {
        foreach ($parameters as $key => $value)
        {
            $parameters[$key] = $this->urlEncode($value, false);
        }

        return $parameters;
    }

    private function urlEncode($value, $path)
    {
        $encodedString = stripslashes(rawurlencode(utf8_encode($value)));

        if ($path)
        {
            $encodedString = str_replace('%2F', '/', $encodedString);
        }

        return $encodedString;
    }

    private function getParametersAsString(array $parameters)
    {
        $queryParameters = [];

        foreach ($parameters as $key => $value)
        {
            $queryParameters[] = $this->urlEncode($key, false) . '=' . $value;
        }

        return implode('&', $queryParameters);
    }

    /**
     * Computes RFC 2104-compliant HMAC signature.
     * For more details refer this
     * http://docs.aws.amazon.com/general/latest/gr/signature-version-2.html
     *
     * @param $data
     * @param $secretKey
     * @return string
     */
    private function sign($data, $secretKey): string
    {
        return base64_encode(hash_hmac(HashAlgo::SHA256, $data, $secretKey, true));
    }

    private function checkAndSetConfigKeys(array $config)
    {
        foreach ($config as $key => $value)
        {
            if (array_key_exists($key, $this->config) === true)
            {
                $this->config [$key] = $value;
            }
            else
            {
                // check the config array key names to match your key names of your config array
                throw new \Exception ( 'Key ' . $key . ' is either not part of the configuration', 1 );
            }
        }
    }

    private function setParametersAndReturnUrl(array $parameters, array $requestParameters)
    {
        $parameters = array_merge($requestParameters, $parameters);

        if (empty($requestParameters['merchant_id']))
        {
            $parameters['SellerId'] = $this->config['merchant_id'];
        }

        return $this->calculateSignatureAndReturnUrl($parameters);
    }

    /* calculateSignatureAndPost - convert the Parameters array to string and curl POST the parameters to MWS */
    private function calculateSignatureAndReturnUrl($parameters)
    {
        // Call the signature and Post function to perform the actions. Returns XML in array format
        $parametersString = $this->calculateSignatureAndParametersToString($parameters);

        return $this->mwsServiceUrl . '?' . $parametersString;
    }

    private function calculateSignatureAndParametersToString(array $parameters = [])
    {
        $parameters['Timestamp'] = $this->getFormattedTimestamp();

        $this->createServiceUrl($parameters['Action']);

        unset($parameters['Action']);

        $parameters = $this->signParameters($parameters);

        $parameters['Signature'] = $this->urlEncode($parameters['Signature'], false);

        $parameters['isSandbox'] = $this->config[Config::SANDBOX];

        $parameters = $this->getParametersAsString($parameters);

        return $parameters;
    }

    /* Create MWS service URL and the Endpoint path */
    private function createServiceUrl($action)
    {
        $this->modePath = $this->api_path[$action];

        //$this->serviceUrl  = 'mws.amazonservices.in';

        $this->mwsServiceUrl   = 'https://' . $this->serviceUrl . $this->modePath;
    }

    private function getFormattedTimestamp()
    {
        return gmdate("Y-m-d\TH:i:s.\\0\\0\\0\\Z", time());
    }

    /* checkIfBool - checks if the input is a boolean */

    private function checkIfBool($string)
    {
        $string = strtolower ( $string );

        return in_array ( $string, array ( 'true', 'false' ) );
    }

    private function validateNotNull($value, $message)
    {
        if (is_null ( $value ))
        {
            throw new \InvalidArgumentException ( $message . ' cannot be null.' );
        }
    }

    private function validateNotEmpty($value, $message)
    {
        if (empty ( $value ))
        {
            throw new InvalidArgumentException ( $message . ' cannot be empty.' );
        }
    }

}
