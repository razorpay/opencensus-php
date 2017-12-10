<?php

namespace RZP\Gateway\FirstData;

class ApprovalCodeParser
{
    protected $success = false;
    protected $errorCode;
    protected $errorMessage;
    protected $authCode;

    public function __construct(string $approvalCode)
    {
        $this->parse($approvalCode);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getErrorCode()
    {
        return $this->errorCode;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    public function getAuthCode()
    {
        return $this->authCode;
    }

    /**
     * Approval Code is sent as a concatenation of the code ('N:224')
     * and the reason ('Timed out') separated by a ':'.
     * Eg. "N:87:Bad Track Data" or "Y:100000:XYZ:ABC"
     * In success scenario where code starts with Y
     * The second field is auth_code
     *
     * @param string $approvalCode
     * @return array
     */
    protected function parse(string $approvalCode)
    {
        $exploded = explode(':', $approvalCode);

        if ($exploded[0] === 'Y')
        {
            $this->success = true;
            if (isset($exploded[1]) === true)
            {
                $this->authCode = $exploded[1];
            }
        }
        else if ($exploded[0] === 'N')
        {
            $this->errorCode     = implode(':', array_slice($exploded, 0, 2));
            $this->errorMessage  = implode(':', array_slice($exploded, 2));
        }
    }
}
