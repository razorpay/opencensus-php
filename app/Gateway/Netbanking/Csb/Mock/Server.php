<?php

namespace RZP\Gateway\Netbanking\Csb\Mock;

use Carbon\Carbon;
use phpseclib\Crypt\AES;

use RZP\Gateway\Base;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\AESCrypto;
use RZP\Exception\LogicException;
use RZP\Gateway\Netbanking\Csb\Status;
use RZP\Gateway\Netbanking\Csb\RequestFields;
use RZP\Gateway\Netbanking\Csb\ResponseFields;

class Server extends Base\Mock\Server
{
    const BANK_ID   = 'CSB';
    const TID       = 9999999999;

    protected $gatewayInstance = null;

    public function authorize($input)
    {
        parent::authorize($input);

        $request = $this->getRequestDetails($input);

        $this->verifyChecksum($request);

        $this->validateAuthorizeInput($request);

        $response = $this->getAuthorizeResponse($request);

        return $request[RequestFields::RETURN_URL] . '?' .  http_build_query($response);
    }

    public function verify($input)
    {
        parent::verify($input);

        $request = $this->getRequestDetails($input);

        $this->verifyChecksum($request);

        $this->validateActionInput($request, $this->action);

        $response = $this->getVerifyResponse($request);

        return $this->makeResponse($response);
    }

    /**
     * Using the gatewayInstance like a singleton object
     *
     * @param null $bankingType
     * @return mixed|null
     */
    protected function getGatewayInstance($bankingType = null)
    {
        if ($this->gatewayInstance === null)
        {
            $this->gatewayInstance = parent::getGatewayInstance($bankingType);
        }

        return $this->gatewayInstance;
    }

    protected function getAuthorizeResponse(array $request)
    {
        $date = Carbon::now(Timezone::IST)->format('d-M-Y H:i:s A');

        $narration = $request[RequestFields::PAYEE_ID] . ' ' . $request[RequestFields::BANK_REF_NUM];

        $content = [
            ResponseFields::BANK_REF_NUM => $request[RequestFields::BANK_REF_NUM],
            ResponseFields::AMOUNT       => $request[RequestFields::AMOUNT],
            ResponseFields::MODE         => $request[RequestFields::MODE],
            ResponseFields::NARRATION    => $narration,
            ResponseFields::DATE_TIME    => $date,
            ResponseFields::TRAN_REF_NUM => self::TID,
            ResponseFields::STATUS       => Status::SUCCESS,
            ResponseFields::PAYEE_ID     => $request[RequestFields::PAYEE_ID],
            ResponseFields::BANKID       => self::BANK_ID,
            ResponseFields::CHNPGCODE    => $request[RequestFields::CHNPGCODE],
        ];

        $this->content($content, $this->action);

        $data = array_slice($content, 0, 7);

        $response = array_slice($content, 7, 3);

        $hashParams = [
            $content[ResponseFields::PAYEE_ID],
            $content[ResponseFields::CHNPGCODE],
            $content[ResponseFields::BANK_REF_NUM],
            $content[ResponseFields::AMOUNT],
            $content[ResponseFields::TRAN_REF_NUM],
            $content[ResponseFields::STATUS],
        ];

        $data[ResponseFields::CHECKSUM] = $this->getGatewayInstance()->generateHash($hashParams);

        $response[ResponseFields::DATA] = $this->encrypt(http_build_query($data), $this->getKeyForAction('Biller Payment Key'));

        return [
            ResponseFields::QOUT => $this->encrypt(urldecode(http_build_query($response)), $this->getKeyForAction('Bank Payment Key'))
        ];
    }

    protected function getVerifyResponse($request)
    {
        $xmlRoot = "<Xml />";

        $response = [
            ResponseFields::STATUS => Status::SUCCESS,
            ResponseFields::BANK_REF_NUM => $request[RequestFields::BANK_REF_NUM],
            ResponseFields::AMOUNT => $request[RequestFields::AMOUNT],
            ResponseFields::TRAN_REF_NUM => self::TID,
        ];

        $this->content($response, $this->action);

        // checksum is calculated in a different order than the response that we receive
        $hashParams = [
            $response[ResponseFields::BANK_REF_NUM],
            $response[ResponseFields::AMOUNT],
            $response[ResponseFields::TRAN_REF_NUM],
            $response[ResponseFields::STATUS],
        ];

        $response[ResponseFields::CHECKSUM] = $this->getGatewayInstance()->generateHash($hashParams);

        $encryptedResponse = $this->encrypt(implode('|', $response), $this->getKeyForAction('Biller Verification Key'));

        $encryptedResponse = $this->encrypt($encryptedResponse, $this->getKeyForAction('Bank Payment Key'));

        $gatewayParamXml = new \SimpleXMLElement($xmlRoot);

        $gatewayParamXml->addChild('Status', $encryptedResponse);

        return trim(explode('?>', $gatewayParamXml->asXML())[1]);
    }

    protected function getRequestDetails(array $input)
    {
        $isTPV = false;

        $encryptedInputL1 = $input[RequestFields::POST_DATA];

        switch ($this->action) {
            case Action::AUTHORIZE:
                $decryptedInputL1 = explode('|', $this->decrypt(urldecode($encryptedInputL1), $this->getKeyForAction('Bank Payment Key')));
                $encryptedInputL2 = urldecode(array_pop($decryptedInputL1));
                $decryptedInputL2 = explode('|', $this->decrypt($encryptedInputL2, $this->getKeyForAction('Biller Payment Key')));
                if (count($decryptedInputL2) === 6)
                {
                    $isTPV = true;
                }
                break;

            case Action::VERIFY:
                $decryptedInputL1 = explode('|', $this->decrypt($encryptedInputL1, $this->getKeyForAction('Bank Payment Key')));
                $encryptedInputL2 = urldecode(array_pop($decryptedInputL1));
                $decryptedInputL2 = explode('|', $this->decrypt($encryptedInputL2, $this->getKeyForAction('Biller Verification Key')));
                break;

            default:
                throw new LogicException('should not have reached here. Invalid action '. $this->action);
        }

        $requestDetails = array_merge($decryptedInputL1, $decryptedInputL2);

        return array_combine($this->getRequestFields($isTPV), $requestDetails);
    }

    protected function verifyChecksum(array $request)
    {
        $checksum = $request[RequestFields::CHECKSUM];

        unset($request[RequestFields::CHECKSUM]);

        unset($request[RequestFields::NARRATION]);

        unset($request[RequestFields::ACCOUNT_NUM]);

        $generatedCheckSum = $this->getChecksum($request);

        $this->compareHashes($checksum, $generatedCheckSum);
    }

    protected function getChecksum(array $request)
    {
        $content = array_values($request);

        return $this->getGatewayInstance()->generateHash($content);
    }

    protected function getRequestFields($isTPV)
    {
        switch ($this->action) {
            case Action::AUTHORIZE:
                $requestFields =  [
                    RequestFields::CHNPGSYN,
                    RequestFields::CHNPGCODE,
                    RequestFields::PAYEE_ID,
                    RequestFields::BANK_REF_NUM,
                    RequestFields::AMOUNT,
                    RequestFields::RETURN_URL,
                    RequestFields::MODE,
                    RequestFields::CHECKSUM
                ];
                if ($isTPV === true)
                {
                    $requestFields[] = RequestFields::ACCOUNT_NUM;
                }
                break;

            case Action::VERIFY:
                $requestFields =  [
                    RequestFields::CHNPGSYN,
                    RequestFields::CHNPGCODE,
                    RequestFields::PAYEE_ID,
                    RequestFields::BANK_REF_NUM,
                    RequestFields::AMOUNT,
                    RequestFields::RETURN_URL,
                    RequestFields::TRAN_REF_NUM,
                    RequestFields::MODE,
                    RequestFields::CHECKSUM
                ];
                break;

            default:
                throw new LogicException('should not have reached here. Invalid action '. $this->action);
        }

        return $requestFields;
    }

    protected function encrypt(string $str, $key)
    {
        $aes = new AESCrypto(AES::MODE_CBC, substr($key, 0, 16), substr($key, 0, 16));

        return base64_encode($aes->encryptString($str));
    }

    protected function decrypt(string $str, $key)
    {
        $aes = new AESCrypto(AES::MODE_CBC, substr($key, 0, 16), substr($key, 0, 16));

        return $aes->decryptString(base64_decode($str));
    }

    protected function getKeyForAction($action)
    {
        switch ($action) {
            case 'Biller Payment Key':
                $key = $this->getGatewayInstance()->getTerminalPassword();
                break;

            case 'Bank Payment Key':
                $key = $this->getGatewayInstance()->getTerminalPassword2();
                break;

            case 'Biller Verification Key':
                $key = $this->getGatewayInstance()->getSecureSecret2();
                break;

            default:
                throw new LogicException('should not have reached here. Invalid action '. $action);
        }

        return $key;
    }
}
