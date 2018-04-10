<?php

namespace RZP\Exception;

use Razorpay\Trace\Logger as Trace;

use RZP\Error\Error;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;

/**
 * This exception class is created to uniformly handle any exceptions
 * while processing the gateway_file entity. The exception object stores
 * the relevant error code (currently at what stage of processing the error
 * occurred) and error description. It extends RecoverableException as we don't
 * want to interrupt / fail the processing,but update the entity with details of
 *  the failure.
 */
class GatewayFileException extends RecoverableException
{
    const PREFIX            = 'SERVER_ERROR_GATEWAY_FILE_';

    const TRACE_CODE_PREFIX = 'SERVER_ERROR_';

    protected $traceLevel;

    public function __construct(
        string $code,
        array $data = [],
        \Throwable $previous = null,
        string $traceLevel = Trace::INFO)
    {
        $this->error = new Error($code);

        $this->data = $data;

        $this->traceLevel = $traceLevel;

        $message = $this->error->getDescription();

        parent::__construct($message, $code, $previous);
    }

    public function getErrorCode(): string
    {
        return strtolower(substr($this->getCode(), strlen(self::PREFIX)));
    }

    public function getTraceCode(): string
    {
        $traceCode = substr($this->getCode(), strlen('SERVER_ERROR_'));

        return constant(TraceCode::class . '::' . $traceCode);
    }

    public function getTraceLevel(): string
    {
        return $this->traceLevel;
    }
}
