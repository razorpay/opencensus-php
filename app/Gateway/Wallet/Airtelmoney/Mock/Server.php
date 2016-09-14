<?php

namespace RZP\Gateway\Wallet\Airtelmoney\Mock;

use Carbon\Carbon;

use RZP\Constants\HashAlgo;
use RZP\Http\Route;
use RZP\Gateway\Base;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Wallet\Base\Otp;
use RZP\Gateway\Wallet\Airtelmoney;
use RZP\Gateway\Wallet\Airtelmoney\TestAmount;
use RZP\Gateway\Wallet\Airtelmoney\DateFormat;
use RZP\Gateway\Wallet\Airtelmoney\Status;
use RZP\Gateway\Wallet\Airtelmoney\ResponseCode;
use RZP\Gateway\Wallet\Airtelmoney\ResponseFields;
use RZP\Gateway\Wallet\Airtelmoney\RequestFields;


class Server extends Base\Mock\Server
{
    const DUMMY_MSG = 'SUCCESS';

    public function authorize($input)
    {
        $this->validateActionInput($input, 'authorize');

        $this->verifyHash($input);

        // TODO Change it some definite value
        if ($input[RequestFields::AMT] === TestAmount::FAIL_PAYMENT_AMOUNT)
        {
            $redirectUrl = $input[RequestFields::FU];

            $queryArray = [
                Responsefields::STATUS     => Status::FAILED,
                ResponseFields::CODE       => '902',
                ResponseFields::MSG        => ResponseCode::getResponseMessage('902'),
                ResponseFields::TXN_REF_NO => $input[RequestFields::TXN_REF_NO],
            ];

            $params = http_build_query($queryArray);
        }
        else
        {
            $redirectUrl = $input[RequestFields::SU];

            $queryArray = [
                ResponseFields::STATUS     => Status::SUCCESS,
                ResponseFields::CODE       => ResponseCode::SUCCESS_CODE,
                ResponseFields::MSG        => self::DUMMY_MSG,
                ResponseFields::MID        => $input[RequestFields::MID],
                ResponseFields::TRAN_ID    => $this->getArtlTxnId(),
                ResponseFields::TRAN_AMT   => $input[RequestFields::AMT],
                ResponseFields::TRAN_CUR   => 'INR',
                ResponseFields::TRAN_DATE  => $this->getFormattedDate(
                    Carbon::now(),
                    DateFormat::TRAN_DATE_FORMAT),
                ResponseFields::TXN_REF_NO => $input[RequestFields::TXN_REF_NO],
            ];

            $params = http_build_query($queryArray);
        }

        return \Redirect::to($redirectUrl.'?'.$params);
    }

    public function verify($input)
    {
        parent::verify($input);

        $this->validateActionInput($this->mockRequest['content']);

        $response = array(
            ResponseFields::STATUS       => Status::SUCCESS,
            ResponseFields::CODE         => ResponseCode::SUCCESS_CODE,
            ResponseFields::FDC_TXN_ID   => $this->getArtlTxnId(),
            ResponseFields::TXN_AMT      => number_format(($input['amount']/100), 2),
            ResponseFields::FDC_TXN_DATE => $this->getFormattedDate(
                Carbon::now(),
                DateFormat::FDC_TXN_DATE_FORMAT),
            ResponseFields::MSG          => self::DUMMY_MSG,
        );

        $response = $this->generateXMLResponse($response);

        return $this->makeXmlResponse($response);
    }

    public function refund($input)
    {
        parent::refund($input);

        $this->validateActionInput($input, 'refund');

        if($input[RequestFields::AMT] === (float) TestAmount::FAIL_REFUND_AMOUNT)
        {
            $refundResponse = [
                ResponseFields::STATUS => Status::FAILED,
                ResponseFields::CODE   => '923',
                ResponseFields::MSG    => ResponseCode::getResponseMessage('923'),
            ];

            $response = $this->generateXMLResponse($refundResponse);

            return $this->makeXmlResponse($response);
        }
        else
        {
            $refundResponse = [
                ResponseFields::STATUS           => Status::SUCCESS,
                ResponseFields::CODE             => ResponseCode::SUCCESS_CODE,
                ResponseFields::NEW_FDC_TXN_ID   => $this->getArtlTxnId(),
                ResponseFields::AMT              => $input[RequestFields::AMT],
                ResponseFields::NEW_FDC_TXN_DATE => $this->getFormattedDate(
                    Carbon::now(),
                    DateFormat::NEW_FDC_TXN_DATE_FORMAT),
                ResponseFields::MSG              => self::DUMMY_MSG,
            ];

            $response = $this->generateXMLResponse($refundResponse);

            return $this->makeXmlResponse($response);
        }
    }

    protected function getArtlTxnId()
    {
        return uniqid();
    }

    protected function verifyHash(array $content)
    {
        $hashArray = [
            $content['MID'],
            $content['TXN_REF_NO'],
            $content['AMT'],
            $content['DATE'],
            $this->getSecret(),
        ];

        $hashString = implode('#', $hashArray);

        $hash = $this->getHashOfString($hashString);

        assert($hash === $content['HASH']);
    }

    protected function getHashOfString($hashString)
    {
        return hash(HashAlgo::SHA512, $hashString);
    }

    protected function makeResponse($json)
    {
        $response = parent::makeResponse($json);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');

        return $response;
    }

    protected function makeXmlResponse($json)
    {
        $response = parent::makeResponse($json);

        $response->headers->set('Content-Type', 'text/xml; charset=UTF-8');

        return $response;
    }

    protected function getFormattedDate($date, $format)
    {
        return $date->format($format);
    }

    protected function generateXMLResponse($content)
    {
        $content = array_flip($content);
        $xml = new \SimpleXMLElement('<wallet/>');
        array_walk_recursive($content, array($xml, 'addChild'));
        return ($xml->asXML());
    }
}
