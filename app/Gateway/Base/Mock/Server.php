<?php

namespace RZP\Gateway\Base\Mock;

use App;
use Illuminate\Support\Str;

use RZP\Exception;
use RZP\Http\Route;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Base\Validator;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\Action;
use RZP\Models\Payment\Processor\Netbanking;

class Server extends Base\Core
{
    /**
     * @var string
     */
    protected $bank;

    /**
     * Input to the Bank API
     * @var mixed
     */
    protected $input;

    /**
     * @var mixed
     */
    protected $request;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var array
     */
    protected $mockRequest;

    /**
     * @var string
     */
    protected $action;

    /**
     * Api Route instance
     *
     * @var Route
     */
    protected $route;

    /**
     * Namespace of the current gateway server
     * @var string
     */
    protected $ns;

    public function __construct()
    {
        parent::__construct();

        $this->request = $this->app['request'];

        $this->route = $this->app['api.route'];
    }

    public function authenticate($input)
    {
        return $this->authorize($input);
    }

    protected function authorize($input)
    {
        $this->action = Action::AUTHORIZE;

        $this->input = $input;
    }

    protected function capture($input)
    {
        $this->action = Action::CAPTURE;

        $this->input = $input;
    }

    protected function refund($input)
    {
        $this->action = Action::REFUND;

        $this->input = $input;
    }

    protected function reverse($input)
    {
        $this->action = Action::REVERSE;

        $this->input = $input;
    }

    protected function verify($input)
    {
        $this->action = Action::VERIFY;

        $this->input = $input;
    }

    protected function advice($input)
    {
        $this->action = Action::ADVICE;

        $this->input = $input;
    }

    protected function verifyRefund($input)
    {
        $this->action = Action::VERIFY_REFUND;

        $this->input = $input;
    }

    protected function validatePush($input)
    {
        $this->action = Action::VALIDATE_PUSH;

        $this->input = $input;
    }

    protected function generateHash($content)
    {
        return $this->getGatewayInstance()->generateHash($content);
    }

    protected function getSecret()
    {
        return $this->getGatewayInstance()->getSecret();
    }

    protected function checkReferer()
    {
        $request = $this->request;

        $referer = $request->headers->get('referer');

        if ($referer === null)
        {
            return;
        }

        $schema = $request->getScheme().'://';
        $host = $request->getHost();
        $host = $schema.$host;

        $pos = strpos($referer, $host);

        if ($pos === 0)
        {
            return;
        }

        $urlParts = parse_url($referer);
        $baseUrl = $urlParts['host'];

        if (strpos($baseUrl, 'razorpay.com') !== false)
        {
            return;
        }

        // throw new Exception\LogicException(
        //     'Unexpected referer value. Referer: ' . $referer);
    }

    protected function getGatewayInstance($bankingType = null)
    {
        $class = $this->getGatewayNamespace() . '\Gateway';

        $gateway = new $class;
        $gateway->setMode(Mode::TEST);

        if (isset($bankingType) === true)
        {
            $gateway->setBankingType($bankingType);
        }

        return $gateway;
    }

    protected function getGatewayNamespace()
    {
        $namespace = $this->getNamespace();

        return substr($namespace, 0, strpos($namespace, 'Mock') - 1 );
    }

    protected function getNamespace()
    {
        $ns = & $this->ns;

        if ($ns !== null)
            return $ns;

        $ns = substr(get_called_class(), 0, strrpos(get_called_class(), "\\"));

        return $ns;
    }

    public function setNamespace($ns)
    {
        $this->ns = $ns;
    }

    protected function getValidator()
    {
        if ($this->validator === null)
        {
            $class = $this->getNamespace() . '\\Validator';

            $this->validator = new $class;
        }

        return $this->validator;
    }

    protected function assertAccountNumberLength($accountNumber)
    {
        $accountNumberLengths = Netbanking::getAccountNumberLengths();

        if ((in_array($this->bank, array_keys($accountNumberLengths)) === true) and
            ($accountNumberLengths[$this->bank] !== strlen($accountNumber)))
        {
            throw new Exception\LogicException(
                'WRONG_ACCOUNT_NUMBER_LENGTH',
                null,
                [
                    'account_number'  => $accountNumber,
                    'bank'            => $this->bank,
                    'length'          => strlen($accountNumber),
                    'expected_length' => $accountNumberLengths,
                ]);
        }
    }

    public function processSoap($input, $location, $action)
    {
        // Should be overridden in child gateway
        $wsdlFile = $this->getWsdlFile();

        $server = new SoapServer($wsdlFile);
        $server->setObject($this);

        return $server->handle($input);
    }

    /**
     * Should be overridden in child gateway
     * @throws Exception\LogicException
     */
    protected function getWsdlFile()
    {
        throw new Exception\LogicException('getWsdlFile needs to be overridden by the child gateway');
    }

    protected function getRepo()
    {
        $class = $this->getGatewayNamespace() . '\Repository';

        return new $class;
    }

    protected function validateAuthorizeInput($input)
    {
        $this->validateActionInput($input, 'auth');
    }

    protected function validateRefundInput($input)
    {
        $this->validateActionInput($input, 'refund');
    }

    protected function validateEnrollInput($input)
    {
        $this->validateActionInput($input, 'enroll');
    }

    protected function validateAuthenticateInput($input)
    {
        $this->validateActionInput($input, 'authenticate');
    }

    protected function validateActionInput($input, $action = null)
    {
        if ($action === null)
        {
            $action = $this->action;
        }

        $validator = $this->getValidator();

        $validator->validateInput($action, $input);
    }

    public function setInput($input)
    {
        $this->input = $input;
    }

    public function setMockRequest($request)
    {
        $this->mockRequest = $request;
    }

    public function setAction($action)
    {
        $this->action = $action;
    }

    public function content(& $content, $action = '')
    {
        return $content;
    }

    public function request(& $content, $action = '')
    {
        return $content;
    }

    protected function makeResponse($msg)
    {
        $response = \Response::make($msg);

        $response->headers->set('Content-Type', 'application/text; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }

    protected function makePostResponse($request)
    {
        $content = '
            <!doctype html public "-//w3c//dtd html 4.0 transitional//en">
            <html lang="en">
                <body>
                <form name="form1" action="'.$request['url'].'" method="'.$request['method'].'">';

        foreach ($request['content'] as $key => $value)
        {
            $content .= $key . '<input type="text" name="'.$key.'" value="'.htmlspecialchars($value).'"><br />';
        }

        $content .= '
                    <input type="submit" value="Submit" >
                </form>
                <br>
                Submit within 30 secs max!
                </body>
            </html>
        ';

        $response = \Response::make($content);

        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }

    protected function getSignedPaymentId($pid)
    {
        return Payment\Entity::getSignedId($pid);
    }

    protected function compareHashes($actual, $generated)
    {
        if (hash_equals($actual, $generated) === false)
        {
            $this->trace->info(
                TraceCode::GATEWAY_CHECKSUM_VERIFY_FAILED,
                [
                    'actual'    => $actual,
                    'generated' => $generated
                ]);

            throw new Exception\RuntimeException('Failed checksum verification');
        }
    }

    protected function isV2Mock(string $case)
    {
        return Str::contains($case, 'v2');
    }

    protected function getIntegerFormattedAmount(string $amount)
    {
        return (int) number_format(($amount * 100), 0, '.', '');
    }
}
