<?php

namespace Gateway\MockAtom;

use Carbon\Carbon;
use EE\Exception;
use Gateway\Hdfc;
use Gateway\Hdfc\Payment\Action;
use Gateway\MockAtom;
use Models\Card;

class Server
{
    public function capture(array $input)
    {
        $tempTxnId = random_integer(6),
        $token = $this->generateAtomToken(),

        return $this->getXmlFormattedResponse($tempTxnId, $token);

    }

    public function txnStage1Submit($input)
    {
        ;
    }

    protected function generateAtomToken()
    {
        $token = bin2hex(openssl_random_pseudo_bytes(46/2));
        $token .= 'z'.'%3D';

        return $token;
    }

    protected function getXmlFormattedResponse($tempTxnId, $token)
    {
        $str = ''.
        '<?xml version="1.0" encoding="UTF-8"?>
            <MMP><MERCHANT><RESPONSE>
                <url>http://203.114.240.183/paynetz/epi/fts</url>
                <param name="ttype">NBFundTransfer</param>
                <param name="tempTxnId">'.$tempTxnId.'</param>
                <param name="token">'.$token.'</param>
                <param name="txnStage">1</param>
            </RESPONSE></MERCHANT></MMP>';

        return $str;
    }

    public function netBankingPage()
    {
        ;
    }
}
