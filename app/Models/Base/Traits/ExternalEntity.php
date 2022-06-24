<?php

namespace RZP\Models\Base\Traits;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Base\PublicEntity;
use Razorpay\Trace\Logger as Trace;


trait ExternalEntity
{
    use ExternalFetch;

    public function reload()
    {
        try
        {
            if ($this->isExternal() === false)
            {
                $entity = parent::reload();

                return $entity;
            }
        }
        catch (\Throwable $e)
        {
        }

        return $this->fetchExternalEntity($this->{$this->primaryKey}, '', []);
    }
}
