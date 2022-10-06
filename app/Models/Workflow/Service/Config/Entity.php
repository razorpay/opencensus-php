<?php

namespace RZP\Models\Workflow\Service\Config;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Admin\Org;
use RZP\Constants\Entity as EntityConstants;

class Entity extends Base\PublicEntity
{
    protected $entity = EntityConstants::WORKFLOW_CONFIG;
    protected $table  = Table::WORKFLOW_CONFIG;

    const CONFIG_ID              = 'config_id';
    const CONFIG_TYPE            = 'config_type';
    const MERCHANT_ID            = 'merchant_id';
    const MERCHANT_IDS           = 'merchant_ids';
    const ORG_ID                 = 'org_id';
    const ENABLED                = 'enabled';
    const CONFIG                 = 'config';

    const OWNER_ID               = 'owner_id';
    const OWNER_TYPE             = 'owner_type';
    const TYPE                   = 'type';

    const NAMESPACE              = 'namespace';
    const NAME                   = 'name';
    const TEMPLATE               = 'template';
    const ASL_TEMPLATE           = 'asl_template';
    const VERSION                = 'version';
    const CONTEXT                = 'context';
    const SERVICE                = 'service';

    protected $fillable = [
        self::ID,
        self::CONFIG_ID,
        self::CONFIG_TYPE,
        self::MERCHANT_ID,
        self::ORG_ID,
        self::ENABLED,
    ];

    protected $visible = [
        self::ID,
        self::CONFIG_ID,
        self::CONFIG_TYPE,
        self::MERCHANT_ID,
        self::ORG_ID,
        self::ENABLED,
        self::NAMESPACE,
        self::NAME,
        self::TEMPLATE,
        self::VERSION,
        self::CONTEXT,
        self::SERVICE,
    ];

    protected $public = [
        self::ID,
        self::CONFIG_ID,
        self::CONFIG_TYPE,
        self::MERCHANT_ID,
        self::ORG_ID,
        self::ENABLED,
        self::NAMESPACE,
        self::NAME,
        self::TEMPLATE,
        self::VERSION,
        self::CONTEXT,
        self::SERVICE,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    // ============================= RELATIONS =============================

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function org()
    {
        return $this->belongsTo(Org\Entity::class);
    }

    // ============================= END RELATIONS =============================

    // ============================= GETTERS =============================

    public function getId()
    {
        return $this->getAttribute(self::ID);
    }

    public function getConfigId()
    {
        return $this->getAttribute(self::CONFIG_ID);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getOrgId()
    {
        return $this->getAttribute(self::ORG_ID);
    }

    public function isEnabled()
    {
        return $this->getAttribute(self::ENABLED);
    }

    public function getType()
    {
        return $this->getAttribute(self::CONFIG_TYPE);
    }

    // ============================= END GETTERS =============================

    // ============================= SETTERS =============================

    public function setConfigId($configId)
    {
        $this->setAttribute(self::CONFIG_ID, $configId);
    }

    public function setOrgId($orgId)
    {
        $this->setAttribute(self::ORG_ID, $orgId);
    }

    public function setEnabled($enabled)
    {
        $this->setAttribute(self::ENABLED, $enabled);
    }

    public function setConfigType($type)
    {
        $this->setAttribute(self::CONFIG_TYPE, $type);
    }

    public function setMerchantId($merchantId)
    {
        $this->setAttribute(self::MERCHANT_ID, $merchantId);
    }

    public function setNameSpace($nameSpace)
    {
        $this->setAttribute(self::NAMESPACE, $nameSpace);
    }

    public function setName($name)
    {
        $this->setAttribute(self::NAME, $name);
    }

    public function setTemplate($template)
    {
        $this->setAttribute(self::TEMPLATE, $template);
    }

    public function setVersion($version)
    {
        $this->setAttribute(self::VERSION, $version);
    }

    public function setContext($context)
    {
        $this->setAttribute(self::CONTEXT, $context);
    }

    public function setService($service)
    {
        $this->setAttribute(self::SERVICE, $service);
    }

    // ============================= END SETTERS =============================
}
