<?php

namespace RZP\Models\P2p\Base;

use RZP\Models\Base;
use RZP\Base\BuilderEx;
use RZP\Error\P2p\ErrorCode;
use RZP\Http\BasicAuth\Type;
use RZP\Models\P2p\Base\Traits\ApplicationTrait;

class Repository extends Base\Repository
{
    use ApplicationTrait;

    protected $merchantIdRequiredForMultipleFetch = false;

    public function getEntityObject()
    {
        $className = str_replace('\Repository', '\Entity', static::class);

        return new $className;
    }

    public function newP2pEntity(): Entity
    {
        $entity = $this->getEntityObject();

        if ($entity->hasDevice())
        {
            if ($this->context()->isContextDevice() === false)
            {
                throw $this->logicException(ErrorCode::SERVER_ERROR_CONTEXT_DEVICE_REQUIRED);
            }

            $entity->associateDevice($this->context()->getDevice());
        }

        if ($entity->hasMerchant())
        {
            if ($this->context()->isContextMerchant() === false)
            {
                throw $this->logicException(ErrorCode::SERVER_ERROR_CONTEXT_MERCHANT_REQUIRED);
            }

            $entity->associateMerchant($this->context()->getMerchant());
        }

        if ($entity->hasHandle())
        {
            $entity->associateHandle($this->context()->getHandle());
        }

        return $entity;
    }


    public function newP2pQuery(): BuilderEx
    {
        $query = parent::newQuery();

        $this->addCommonQueryParamMerchantId($query, null);

        return $query;
    }

    protected function addCommonQueryParamMerchantId($query, $merchantId)
    {
        // If context is not set, we need to make
        if ($this->context()->getContextType() === null)
        {
            $basicAuth = $this->app['basicauth']->getAuthType();

            // For privilege auth, we don't need to initiate the context
            if (in_array($basicAuth, [Type::PRIVILEGE_AUTH, Type::ADMIN_AUTH]))
            {
                return parent::addCommonQueryParamMerchantId($query, $merchantId);
            }
        }

        if ($query->getModel()->hasDevice())
        {
            if ($this->context()->isContextDevice() === false)
            {
                throw $this->logicException(ErrorCode::SERVER_ERROR_CONTEXT_DEVICE_REQUIRED);
            }

            $query->device($this->context()->getDevice());
        }

        if ($query->getModel()->hasMerchant())
        {
            if ($this->context()->isContextMerchant() === false)
            {
                throw $this->logicException(ErrorCode::SERVER_ERROR_CONTEXT_MERCHANT_REQUIRED);
            }

            $query->merchant($this->context()->getMerchant());
        }

        if ($query->getModel()->hasHandle())
        {
            $query->handle($this->context()->getHandle());
        }
    }
}
