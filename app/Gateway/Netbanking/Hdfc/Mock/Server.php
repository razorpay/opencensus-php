<?php

namespace RZP\Gateway\Netbanking\Hdfc\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Netbanking;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $content = array(
            'MerchRefNo'    => $input['MerchantRefNo'],
            'TxnAmount'     => $input['TxnAmount'],
            'TxnCurrency'   => 'INR',
            'ClientCode'    => $input['ClientCode'],
            'TxnScAmount'   => $input['TxnScAmount'],
            'CheckSum'      => '',
            'BankRefNo'     => random_integer(6),
            'MerchantCode'  => $input['MerchantCode'],
            'Date'          => $input['Date'],
            'StFailFlg'     => 'N',
            'StSucFlg'      => 'N',
            'Message'       => '',
            'fldSessionNbr' => '5',
        );

        $this->content($content, 'authorize');

        // Send checksum only if it's not an emandate/recurring payment
        if (isset($input['ClientAccNum']) === true)
        {
            unset($content['CheckSum']);
        }
        else
        {
            $content['CheckSum'] = $this->getCallbackChecksum($content);
        }

        $url = $input['DynamicUrl'];
        $url .= '?' . http_build_query($content);

        return $url;
    }

    public function verify($input)
    {
        parent::verify($input);

        $this->validateActionInput($input);

        $id = $input['MerchantRefNo'];

        $payment = (new Netbanking\Base\Repository)->findByPaymentIdAndAction(
                                                    $id, Base\Action::AUTHORIZE);

        $content = array(
            'ClientCode'        => $payment['client_code'],
            'MerchantCode'      => $input['MerchantCode'],
            'TxnAmount'         => $payment['amount'],
            'MerchantRefNo'     => $payment['id'],
            'SuccessStaticFlag' => 'N',
            'FailureStaticFlag' => 'N',
            'Date'              => $input['Date'],
            'TransactionId'     => 'XTXTV01',
            'flgVerify'         => $input['FlgVerify'],
            'BankRefNo'         => $payment['bank_payment_id'],
            'flgSuccess'        => 'S',
            'Message'           => $payment['error_message'],
        );

        $html = $this->prepareVerifyResponseHtml($content);

        return $this->prepareResponse($html);
    }

    protected function getCallbackChecksum($input)
    {
        $paramsOrder = array(
            'ClientCode',
            'MerchantCode',
            'TxnCurrency',
            'TxnAmount',
            'TxnScAmount',
            'MerchRefNo',
            'StSucFlg',
            'StFailFlg',
            'Date',
            'Ref1',
            'Ref2',
            'Ref3',
            'Ref4',
            'Ref5',
            'Ref6',
            'Ref7',
            'Ref8',
            'Ref9',
            'Ref10',
            'Ref11',
            'Date1',
            'Date2',
            'BankRefNo',
            'Message',
        );

        $data = [];

        foreach ($paramsOrder as $param)
        {
            if (isset($input[$param]))
            {
                $data[$param] = $input[$param];
            }
        }

        return $this->generateHash($data);
    }

    protected function getHostname($url)
    {
        return parse_url($url, PHP_URL_HOST);
    }

    protected function prepareVerifyResponseHtml($content)
    {
        $content = http_build_query($content);

        $appUrl = $this->app['config']->get('app.url');

        $redirectUrl = $this->getHostname($appUrl) . '?' . $content;

        ob_start();

        require ('VerifyResponseHtml.php');

        $html = ob_get_clean();

        return $html;
    }

    protected function prepareResponse($content)
    {
        $response = \Response::make($content);

        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
