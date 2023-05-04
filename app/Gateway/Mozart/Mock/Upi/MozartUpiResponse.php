<?php


namespace RZP\Gateway\Mozart\Mock\Upi;


use Illuminate\Support\Collection;
use RZP\Gateway\Upi\Yesbank\Status;

class MozartUpiResponse extends Collection
{
    const DATA      = 'data';
    const NEXT      = 'next';
    const SUCCESS   = 'success';
    const ERROR     = 'error';

    // Data attributes
    const UPI       = 'upi';
    const MANDATE   = 'mandate';
    const TERMINAL  = 'terminal';
    const PAYMENT   = 'payment';
    const META      = 'meta';
    const STATUS    = 'status';

    public function setNext(array $next)
    {
        $this->put(self::NEXT, $next);

        return $this;
    }

    public function setData(array $data)
    {
        $this->put(self::DATA, $data);

        return $this;
    }

    public function setSuccess(bool $isSuccess)
    {
        $this->put(self::SUCCESS, $isSuccess);

        return $this;
    }

    public function setError(array $error)
    {
        $this->put(self::ERROR, $error);

        return $this;
    }

    public function setPayment(array $payment)
    {
        $data = $this->getData();

        $data[self::PAYMENT] = $payment;

        $this->put(self::DATA, $data);

        return $this;
    }

    public function setTerminal(array $terminal)
    {
        $data = $this->getData();

        $data[self::TERMINAL] = $terminal;

        $this->put(self::DATA, $data);

        return $this;
    }

    public function setMeta(array $meta)
    {
        $data = $this->getData();

        $data[self::META] = $meta;

        $this->put(self::DATA, $data);

        return $this;
    }

    public function getData()
    {
        return $this->get(self::DATA);
    }

    public function mergeUpi(array $upi)
    {
        $data = $this->get(self::DATA);

        $data['upi'] = array_merge($data['upi'] ?? [], $upi);

        $this->setData($data);

        return $this;
    }

    public function mergeError(array $error)
    {
        $data = $this->get(self::ERROR);

        $this->setError(array_merge($data['error'], $error));

        return $this;
    }

    public static function getDefaultInstanceForV2()
    {
        return new MozartUpiResponse([
            'data'      => [
                'version' => 'v2',
            ],
            'success'   => true,
            'next'      => [],
            'error'     => [],
        ]);
    }

    public function setStatus(bool $flag)
    {
        $data = $this->getData();

        if ($flag === true)
        {
            $data[self::STATUS] = Status::SUCCESS_STATUS;
        }
        else
        {
            $data[self::STATUS] = Status::FAILURE_STATUS;
        }

        $this->put(self::DATA, $data);

        return $this;
    }
}
