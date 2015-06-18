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

    public function __construct()
    {
        $this->request = Request::getFacadeRoot();
    }

    protected function authorize($input)
    {
        $this->action = 'authorize';

        $this->input = $input;
    }

    protected function generateHash($content)
    {
        return $this->getGatewayInstance()->generateHash($content);
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
        $class = get_class($this);
        $class = substr($class, 0, strpos($class, '\Mock')) . '\Gateway';

        $gateway = new $class;
        $gateway->setMode(Mode::TEST);

        return $gateway;
    }

    protected function getNamespace()
    {
        return substr(get_called_class(), 0, strrpos(get_called_class(), "\\"));
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

    protected function validateAuthorizeInput($input)
    {
        $validator = $this->getValidator();

        $validator->validateInput('auth', $input);
    }

    public function setInput($input)
    {
        $this->input = $input;
    }

    protected function makePostResponse($request)
    {
        $content = '
            <!doctype html>
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

        $response->headers->set('Content-Type', 'application/html; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }
}
