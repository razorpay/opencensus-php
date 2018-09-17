<?php

namespace RZP\Gateway\Enach\Rbl;

use RZP\Error;
use DOMDocument;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use phpseclib\Crypt\RSA;
use RZP\Models\Bank\IFSC;
use RobRichards\XMLSecLibs;
use RZP\Constants\Timezone;
use RZP\Constants\HashAlgo;
use RZP\Gateway\Enach\Base;
use RZP\Gateway\Base\Action;
use RZP\Models\Customer\Token;
use RZP\Models\Settlement\Holidays;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Gateway\Enach\Base\CategoryCode;
use RobRichards\XMLSecLibs\XMLSecurityKey;

class Gateway extends Base\Gateway
{
    protected $gateway = 'enach_rbl';

    const ENVELOPED = 'http://www.w3.org/2000/09/xmldsig#enveloped-signature';

    const TRANSFORMS = [
        self::ENVELOPED
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        if (($input['payment']['method'] === 'emandate') and
            ($input['payment']['auth_type'] === 'netbanking'))
        {
            return $this->netbankingAuthorize($input);
        }

        $input['gateway'] = $this->getGatewayInput($input);

        $content = [
            Base\Entity::REGISTRATION_DATE => $input['gateway']['next_working_dt']->getTimestamp()
        ];

        try
        {
            $authenticationResponse = $this->callAuthenticationGateway($input);

            $content[Base\Entity::GATEWAY_REFERENCE_ID] = $authenticationResponse['content']['reference_id'];

            $this->createGatewayPaymentEntity($content, 'authorize');

            unset($authenticationResponse['content']['reference_id']);
        }

        catch (Exception\GatewayErrorException $e)
        {
            $responseArrary = $e->getData();

            $content[Base\Entity::ERROR_CODE] = $responseArrary['code'] ?? null;

            $content[Base\Entity::ERROR_MESSAGE] = $responseArrary['message'] ?? null;

            $content[Base\Entity::GATEWAY_REFERENCE_ID] = $responseArrary['details'] ?? null;

            if ($content[Base\Entity::GATEWAY_REFERENCE_ID] !== null)
            {
                $this->createGatewayPaymentEntity($content, 'authorize');
            }
            else
            {
                $this->trace->info(
                    TraceCode::PAYMENT_AUTH_ESIGN_FAILURE,
                    [
                        'response' => $content
                    ]);
            }

            throw $e;
        }

        return $authenticationResponse;
    }

    public function callback(array $input)
    {
        sd($input);
        parent::callback($input);

        $authResponse = $this->callAuthenticationGateway($input);

        $enach = $this->repo->findByPaymentIdAndAction(
            $input['payment']['id'],
            Action::AUTHORIZE
        );

        $this->updateGatewayPaymentEntity($enach, $authResponse, false);

        $data = [];

        if ($input['payment'][Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::INITIAL)
        {
            $data = $this->getRecurringData();
        }

        return $data;
    }

    protected function netbankingAuthorize($input)
    {
        $this->createGatewayPaymentEntity([], 'authorize');

        $request = $this->getRequest($input);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    protected function getRecurringData()
    {
        $recurringData = [
            Token\Entity::RECURRING_STATUS => Token\RecurringStatus::INITIATED,
        ];

        return $recurringData;
    }

    protected function getGatewayInput(array $input)
    {
        return [
            'next_working_dt' => $this->getNextWorkingDate($input)
        ];
    }

    // @todo: Fix this using the holiday schedule
    protected function getNextWorkingDate(array $input)
    {
        $currentTs = $input['payment']['created_at'];

        $dt = Carbon::createFromTimestamp($currentTs, Timezone::IST);

        // @todo: Move this to a holiday model
        return Holidays::getNextWorkingDay($dt);
    }

    protected function getGatewayTerminalId()
    {
        if ($this->mode === Mode::LIVE)
        {
            return $this->input['terminal']['gateway_terminal_id'];
        }

        return $this->config['test_terminal_id'];
    }

    public function refund(array $input)
    {
        throw new Exception\RuntimeException(
            'Refund is not implemented');
    }

    public function verify(array $input)
    {
        throw new Exception\RuntimeException(
            'Verify is not implemented');
    }

    protected function getRequest($input)
    {
        $secureData = $this->getSecureData($input);

        $checksum = $this->generateHash($secureData);

        $encryptedChecksum = $this->encryptChecksum($checksum);

        $data = $this->getDataForXml($input, $secureData);

        $xml = $this->getXml($data);

        $mid = $this->getMerchantId();

        $bank = $input['payment']['bank'];

        $content = [
            'MerchantID' => $mid,
            'MandateReqDoc' => $xml,
            'CheckSumVal' => $encryptedChecksum,
            'BankID' => $bank,
        ];

        $request = $this->getStandardRequestArray($content, 'post', 'npciauth');

        $request = $this->addHeadersForNpciRequest($request);

        return $request;
    }

    protected function getSecureData($input)
    {
        $nextWorkingDt = $this->getNextWorkingDate($input)->format('Y-m-d+05:30');

        $finalCollection = Carbon::createFromTimestamp($input['token']->getExpiredAt(), Timezone::IST)
                                                      ->format('Y-m-d+05:30');

        return [
            RequestNpciTags::DEBTOR_ACCOUNT => $input['token']->getAccountNumber(),
            RequestNpciTags::FIRST_COLLECTION_DATE => $nextWorkingDt,
            RequestNpciTags::FINAL_COLLECTION_DATE => $finalCollection,
            RequestNpciTags::COLLECTION_AMOUNT    => '',
            RequestNpciTags::MAX_AMOUNT => $input['token']->getMaxAmount() / 100,
        ];
    }

    protected function getDataForXml($input, $secureData)
    {
        $encryptedData = $this->getEncryptedData($secureData);

        $mid = $this->getMerchantId();

        $mcc = $input['terminal']['category'];

        $currentDate = Carbon::now()->setTimezone(Timezone::IST)->format('Y-m-d\TH:i:s');

        $data = [
            NpciXmlHeaderTags::GROUP_HEADER      => [
                RequestNpciTags::MESSAGE_ID            => $this->getMsgId(),
                RequestNpciTags::CREATION_DATE_TIME    => $currentDate,
            ],

            NpciXmlHeaderTags::INFO              => [
                RequestNpciTags::MID                   => $mid,
                RequestNpciTags::CATEGORY_CODE         => CategoryCode::getCategoryCodeFromMcc($mcc),
                RequestNpciTags::UTILITY_CODE          => $mid,
                RequestNpciTags::CATEGORY_DESCRIPTION  => 'Api Mandate', //Todo have to add mapping for this
                RequestNpciTags::NAME                  => 'Razorpay software pvt ltd', //Todo check if this ok
            ],

            RequestNpciTags::MANDATE_ID           => $this->getMandateId($input['payment']['id']),

            NpciXmlHeaderTags::OCCURENCE          => [
                RequestNpciTags::SEQUENCE_TYPE         => 'RCUR',
                RequestNpciTags::FREQUENCY             => Frequency::ADHOC,
                RequestNpciTags::FIRST_COLLECTION_DATE => $encryptedData[RequestNpciTags::FIRST_COLLECTION_DATE],
                RequestNpciTags::FINAL_COLLECTION_DATE => $encryptedData[RequestNpciTags::FINAL_COLLECTION_DATE],
            ],

            RequestNpciTags::MAX_AMOUNT            => $encryptedData[RequestNpciTags::MAX_AMOUNT],

            NpciXmlHeaderTags::DEBTOR              => [
                RequestNpciTags::DEBTOR_NAME           => $input['token']->getBeneficiaryName(),
                RequestNpciTags::DEBTOR_ACCOUNT        => $encryptedData[RequestNpciTags::DEBTOR_ACCOUNT],
            ],

            NpciXmlHeaderTags::CREDITOR            => [
                RequestNpciTags::CREDITOR_NAME         => 'Razorpay software pvt ltd',
                RequestNpciTags::CREDITOR_ACCOUNT      => '123456789012345', //TODO find this value
                RequestNpciTags::IFSC_SPONSOR          => 'RATN0000057', // TODO insert proper value
            ]
        ];

        return $data;
    }

    protected function getXml($data)
    {
        $document = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'
                                                .'<Document xmlns="http://npci.org/onmags/schema"/>');

        $mandateroot = $document->addChild(NpciXmlHeaderTags::MANDATE_ROOT_HEADER);

        $grp = $mandateroot->addChild(NpciXmlHeaderTags::GROUP_HEADER);

        $this->addChildren($data[NpciXmlHeaderTags::GROUP_HEADER], $grp);

        $req = $grp->addChild(NpciXmlHeaderTags::REQUEST_INITIATING_PARTY);

        $info = $req->addChild(NpciXmlHeaderTags::INFO);

        $this->addChildren($data[NpciXmlHeaderTags::INFO], $info);

        $mandate = $mandateroot->addChild(NpciXmlHeaderTags::MANDATE);

        $mandate->addChild(RequestNpciTags::MANDATE_ID, $data[RequestNpciTags::MANDATE_ID]);

        $occurence = $mandate->addChild(NpciXmlHeaderTags::OCCURENCE);

        $this->addChildren($data[NpciXmlHeaderTags::OCCURENCE], $occurence);

        $maxAmount = $mandate->addChild(RequestNpciTags::MAX_AMOUNT, $data[RequestNpciTags::MAX_AMOUNT]);

        $maxAmount->addAttribute('Ccy', 'INR');

        $debtor = $mandate->addChild(NpciXmlHeaderTags::DEBTOR);

        $this->addChildren($data[NpciXmlHeaderTags::DEBTOR], $debtor);

        $creditor = $mandate->addChild(NpciXmlHeaderTags::CREDITOR);

        $this->addChildren($data[NpciXmlHeaderTags::CREDITOR], $creditor);

        $xmlString = $document->asXml();

        $xmlDoc = new DOMDocument('1.0', 'UTF-8');

        $xmlDoc->preserveWhiteSpace = false;

        $xmlDoc->formatOutput = true;

        $xmlDoc->loadXML($xmlString);

        $xmlString = $xmlDoc->saveXML();

        $signedxml = $this->addSignature($xmlString);

        return $signedxml;
    }

    protected function getEncryptedData($secureData)
    {
        unset($secureData[RequestNpciTags::COLLECTION_AMOUNT]);

        $encryptedData = [];

        $rsa = $this->getRsaInstance('request');
        //$publicKey = file_get_contents(__DIR__ . '/keys/NpciMms.cer');

        foreach ($secureData as $key => $value)
        {
            $encrypted = $rsa->encrypt($value);
            //openssl_public_encrypt($value, $encrypted, $publicKey, OPENSSL_PKCS1_OAEP_PADDING);
            $encoded = base64_encode($encrypted);

            $encryptedData[$key] = $encoded;
        }
        return $encryptedData;
    }

    protected function encryptChecksum($checksum)
    {
        $rsa = $this->getRsaInstance('request');

        $encrypted = $rsa->encrypt($checksum);

        $encoded = base64_encode($encrypted);

        return $encoded;
    }

    protected function addSignature($xml)
    {
        $xmlDoc = $this->makeDomDocument($xml);

        $sign = new XMLSecLibs\XMLSecurityDSig(null);

        $sign->setCanonicalMethod(XMLSecLibs\XMLSecurityDSig::C14N);

        $sign->canonicalizeSignedInfo();

        $sign->addReference(
            $xmlDoc,
            XMLSecLibs\XMLSecurityDSig::SHA256,
            self::TRANSFORMS,
            ['force_uri' => true]
        );

        $sign->add509Cert($this->getRzpCert(),true, false, ['subjectName' => true ]);

        $sign->sign($this->getSigningKey());

        $sign->appendSignature($xmlDoc->documentElement);

        $signedxml = $xmlDoc->saveXML();

        //$xmlDoc->save('request.xml');

        assertTrue($this->verifySignature($signedxml));

        return $signedxml;
    }

    protected function getRsaInstance($mode)
    {
        $rsa = new RSA();

        switch ($mode)
        {

            case 'response':
                break;

            case 'request':
                $key = $this->getNpciPublicKey();
                $rsa->loadKey($key);
                break;
        }

        $rsa->setEncryptionMode(RSA::ENCRYPTION_OAEP);
        $rsa->setHash('sha256');
        $rsa->setMGFHash('sha1');

        return $rsa;
    }

    protected function getNpciPublicKey()
    {
        $cert = (file_get_contents(__DIR__ . '/keys/onmag_cert.cer'));

        $publicKeyResource = openssl_pkey_get_public($cert);

        $pubkeyInfo = openssl_pkey_get_details($publicKeyResource);

        return $pubkeyInfo['key'];
    }

    protected function getRzpCert()
    {
        return (file_get_contents(__DIR__ . '/keys/cert.pem'));
    }

    public function generateHash($content)
    {
        $hashString = $this->getStringToHash($content);

        return $this->getHashOfString($hashString);
    }

    protected function getStringToHash($content, $glue = '|')
    {
        return implode($glue, $content);
    }

    protected function getHashOfString($string)
    {
        return hash(HashAlgo::SHA256, $string);
    }

    public function getMerchantId()
    {
        $mid = $this->getLiveMerchantId();

        if ($this->mode === Mode::TEST)
        {
            $mid = $this->getTestMerchantId();
        }

        return $mid;
    }

    protected function addHeadersForNpciRequest($request)
    {
        //TODO add appropriate values here below
        $headers = [
            'Content-Type'  => 'application/x-www-form-urlencoded'
        ];

        $request['headers'] = $headers;

        return $request;
    }

    protected function getUniqueId()
    {
        return UniqueIdEntity::generateUniqueId();
    }

    protected function getMsgId()
    {
        $id = $this->getUniqueId();

        return 'msg' . '_' . $id;
    }

    protected function getMandateId($id)
    {
        return 'mandate' . '_' . $id;
    }

    protected function callAuthenticationGateway(array $input)
    {
        return $this->app['gateway']->call(
            Payment\Gateway::ESIGNER_DIGIO,
            $this->action,
            $input,
            $this->mode);
    }

    private function addChildren($data, $xml)
    {
        foreach ($data as $key => $value) {
            $xml->addChild($key,$data[$key]);
        }
    }

    protected function getSigningKey()
    {
        $key =  (file_get_contents(__DIR__ . '/keys/key.pem'));

        $key = trim(str_replace('\n', "\n", $key));

        $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, array('type' => 'private'));

        $objKey->loadKey($key);

        return $objKey;
    }

    protected function makeDomDocument(string $xml)
    {
        $xmlDoc = new DOMDocument('1.0', 'UTF-8');

        $xmlDoc->loadXML($xml);

        return $xmlDoc;
    }

    protected function verifySignature(string $xml)
    {
        $sign = new XMLSecLibs\XMLSecurityDSig(null);

        $xmlDoc = new DOMDocument('1.0', 'UTF-8');

        $xmlDoc->loadXML($xml);

        assertTrue($sign->locateSignature($xmlDoc));

        $sign->canonicalizeSignedInfo();

        assertTrue($sign->validateReference());

        $objKey = $sign->locateKey();

        $objKey->loadKey($this->getSigningPublicKey());

        $verify = $sign->verify($objKey);

        // Calls openssl_verify, which returns 1 on success, 0 on failure, -1 on error
        return ($verify === 1);
    }

    protected function getSigningPublicKey()
    {
        $cert = $this->getRzpCert();

        $publicKeyResource = openssl_pkey_get_public($cert);

        $pubkeyInfo = openssl_pkey_get_details($publicKeyResource);

        return $pubkeyInfo['key'];
    }
}
