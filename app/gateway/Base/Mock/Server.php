<?php

namespace Gateway\Base\Mock;

use Constants\Mode;
use EE\Exception;
use Request;
use Requests_Response;

class Server
{
    protected $request;

    protected $validator;

    /**
     * Namespace of the current gateway server
     * @var string
     */
    protected $ns;

    public function __construct()
    {
        $this->request = Request::getFacadeRoot();
    }

    protected function authorize($input)
    {
        $this->action = 'authorize';

        $this->input = $input;
    }

    protected function capture($input)
    {
        $this->action = 'capture';

        $this->input = $input;
    }

    protected function refund($input)
    {
        $this->action = 'refund';

        $this->input = $input;
    }

    protected function verify($input)
    {
        $this->action = 'verify';

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

        throw new Exception\LogicException(
            'Unexpected referer value. Referer: ' . $referer);
    }

    protected function getGatewayInstance()
    {
        $class = $this->getGatewayNamespace() . '\Gateway';

        $gateway = new $class;
        $gateway->setMode(Mode::TEST);

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

    protected function setNamespace($ns)
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

    protected function getRepo()
    {
        $class = $this->getGatewayNamespace() . '\Repository';

        return new $class;
    }

    protected function validateAuthorizeInput($input)
    {
        $this->validateActionInput($input, 'auth');
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

    public function content(& $content)
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
                <form name="form1" action="'.$request['url'].'" method="post">';

        foreach ($request['content'] as $key => $value)
        {
            $content .= $key . '<input type="text" name="'.$key.'" value="'.$value.'"><br />';
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
}
