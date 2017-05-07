<?php

namespace RZP\Gateway\Aeps\Icici;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;

class Socket extends Base\Core
{
    protected $sock;

    const TIMEOUT = 90;

    public function __construct()
    {
        parent::__construct();

        // TODO: See if socket_stream can be used isntead of this
        $this->sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if ($this->sock === false)
        {
            $this->trace->info(
                TraceCode::SOCKET_CREATION_FAILED,
                [
                    'error' => socket_strerror(socket_last_error())
                ]);

            throw new Exception\ServerErrorException(
                'Socket Creation Failed',
                ErrorCode::SERVER_ERROR_SOCKET_FAILURE);
        }

        list($address, $port) = $this->getAddressAndPort();

        $result = socket_connect($this->sock, $address, $port);

        if ($result === false)
        {
            $this->trace->info(
                TraceCode::SOCKET_CONNECTION_FAILED,
                [
                    'error' => socket_strerror(socket_last_error($this->sock))
                ]);

            throw new Exception\ServerErrorException(
                'Socket Connection Failed',
                ErrorCode::SERVER_ERROR_SOCKET_FAILURE);
        }
    }

    public function sendData($data)
    {
        socket_write($this->sock, $data, strlen($data));
    }

    public function receiveData($maxDataSize = 10000)
    {
        socket_set_option(
            $this->sock,
            SOL_SOCKET,
            SO_RCVTIMEO,
            [
                'sec'  => self::TIMEOUT,
                'usec' => 0
            ]);

        $data = socket_read($this->sock, $maxDataSize);

        $this->closeSocket();

        // IF we do not receive any data, throw a TIMEOUT exception,
        // so reversal request can be done
        if ($data === null)
        {
            throw new Exception\GatewayTimeoutException('Socket Read timeed out');
        }

        return $data;
    }

    public function closeSocket()
    {
        socket_close($this->sock);
    }

    protected function getAddressAndPort()
    {
        if ($this->mode === Mode::TEST)
        {
            return [Url::TEST_DOMAIN_URL, Url::TEST_DOMAIN_PORT];
        }

        return [Url::LIVE_DOMAIN_URL, Url::LIVE_DOMAIN_PORT];
    }
}
