<?php

namespace App\Exceptions;

use Exception;

class ApiPromiseResponseException extends Exception
{
    protected int $httpCode;
    
    protected string $errorMessage;
    
    protected array $errors;
    
    public function __construct(string $errorMessage, int $httpCode, )
    {
        parent::__construct($errorMessage, $httpCode);
        
        $this->setHttpCode($httpCode);
        $this->setErrorMessage($errorMessage);
    }
    
    /**
     * @param int $httpCode
     */
    public function setHttpCode(int $httpCode): void
    {
        $this->httpCode = $httpCode;
    }
    
    /**
     * @return int
     */
    public function getHttpCode(): int
    {
        return $this->httpCode;
    }
    
    /**
     * @param string $errorMessage
     */
    public function setErrorMessage(string $errorMessage): void
    {
        $this->setErrors([$errorMessage]);
        
        if ($this instanceof \GuzzleHttp\Exception\ClientException)
        {
            $this->setErrors([$errorMessage, "Status Code: {$this->getHttpCode()}"]);
        }
        
        $this->errorMessage = $errorMessage;
    }
    
    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }
    
    /**
     * @param array $errors
     */
    public function setErrors(array $errors): void
    {
        $this->errors = $errors;
    }
    
    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}