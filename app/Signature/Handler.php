<?php

namespace RZP\Signature;

use RZP\Exception;

class Handler
{
    protected $params;

    protected $signer;

    protected $type;

    public function __construct(string $type, array $params)
    {
        $this->params = $params;
        $this->type = $type;
        $this->signer = $this->getSigner($type);
    }

    public function signFile(string $filePath)
    {
        $data = file_get_contents($filePath);
        $signature = $this->sign($data);
        file_put_contents($filePath, $signature);
    }

    public function sign(string $data)
    {
        return $this->signer->sign($data);
    }

    protected function getSigner(string $type)
    {
        switch ($type)
        {
            case Type::PFX_SIGNATURE :
                return new PfxSignature($this->params);
            default:
                throw new Exception\LogicException('Not a valid signature type');
        }
    }
}
