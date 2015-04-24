<?php

namespace Gateway\MockAxis;

use Models\Card;
use Carbon\Carbon;
use EE\Exception;
use Gateway\Atom;
use Gateway\MockAtom;
use Http\Route;
use Models\Payment;

class Server
{
    public function __construct()
    {
        $this->request = \Request::getFacadeRoot();

        $this->repo = new Axis\Repository;
    }

    public function authorize($input)
    {
        $validator = $this->getValidator();

        $validator->validateInput('auth', $input);

        ;
    }

    public function capture(array $input)
    {
        ;
    }

    protected function checkReferer()
    {
        $request = $this->request;

        $referer = $request->headers->get('referer');

        $schema = $request->getScheme().'://';
        $host = $request->getHost();
        $host = $schema.$host;

        $pos = strpos($referer, $host);

        if ($pos !== 0)
        {
            throw new Exception\LogicException(
                'Unexpected referer value. Referer: ' . $referer);
        }
    }

    protected function getBankPageUrl()
    {
        ;
    }

    protected function generateToken()
    {
        $token = \Models\Base\UniqueIdEntity::generateUniqueId();

        return $token;
    }

    public function setInput($input)
    {
        $this->input = $input;
    }

    public function getValidator()
    {
        if ($this->validator === null)
        {
            $this->validator = new Validator;
        }

        return $this->validator;
    }
}
