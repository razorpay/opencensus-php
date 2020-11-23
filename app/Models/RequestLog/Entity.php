<?php

namespace RZP\Models\RequestLog;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Constants\Entity as EntityConstants;

class Entity extends Base\PublicEntity
{
    // properties
    protected $entity = EntityConstants::REQUEST_LOG;

    protected $table = Table::REQUEST_LOG;

    protected $generateIdOnCreate = true;

    // Scheme Constants
    const ID             = 'id';
    const MERCHANT_ID    = 'merchant_id';

    // The "real" IP of the client's device
    const CLIENT_IP      = 'client_ip';

    // PROXY_IP - To store the IP of the dashboard/mobile app server (separate from the client endpoint IP)
    const PROXY_IP       = 'proxy_ip';

    const ROUTE_NAME     = 'route_name'; // name of the route
    const REQUEST_METHOD = 'request_method'; // GET, POST PATCH etc.
    const ENTITY_ID      = 'entity_id';
    const ENTITY_TYPE    = 'entity_type';
    const CREATED_AT     = 'created_at';
    const UPDATED_AT     = 'updated_at';
    // Metadata to be implemented after PII security discussions
    // const METADATA = 'metadata';

    // Scheme Constants end

    // Getters
    public function getClientIp()
    {
        return $this->getAttribute(self::CLIENT_IP);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getProxyIp()
    {
        return $this->getAttribute(self::PROXY_IP);
    }

    public function getRouteName()
    {
        return $this->getAttribute(self::ROUTE_NAME);
    }

    public function getRequestMethod()
    {
        return $this->getAttribute(self::REQUEST_METHOD);
    }

    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    public function getEntityType()
    {
        return $this->getAttribute(self::ENTITY_TYPE);
    }
    // Getters End

    // Setters
    public function setClientIp(string $value = null)
    {
        return $this->setAttribute(self::CLIENT_IP, $value);
    }

    public function setMerchantId(string $value = null)
    {
        return $this->setAttribute(self::MERCHANT_ID, $value);
    }

    public function setProxyIp(string $value = null)
    {
        return $this->setAttribute(self::PROXY_IP, $value);
    }

    public function setRouteName(string $value = null)
    {
        return $this->setAttribute(self::ROUTE_NAME, $value);
    }

    public function setRequestMethod(string $value = null)
    {
        return $this->setAttribute(self::REQUEST_METHOD, $value);
    }

    public function setEntityId(string $value = null)
    {
        return $this->setAttribute(self::ENTITY_ID, $value);
    }

    public function setEntityType(string $value = null)
    {
        return $this->setAttribute(self::ENTITY_TYPE, $value);
    }
    // Setters End
}
