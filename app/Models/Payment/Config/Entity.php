<?php


namespace RZP\Models\Payment\Config;

use RZP\Models\Base;


class Entity extends Base\PublicEntity
{
    const ID                  = 'id';
    const MERCHANT_ID         = 'merchant_id';
    const NAME                = 'name';
    const TYPE                = 'type';
    const CONFIG              = 'config';
    const RESTRICTIONS        = 'restrictions';
    const IS_DEFAULT          = 'is_default';
    const IS_DELETED          = 'is_deleted';

    const CONVENIENCE_FEE_CONFIG = 'convenience_fee_config';

    const FEE_CONFIG_METHODS = ['card', 'wallet', 'netbanking', 'upi'];

    const FEE_PAYEE = ['business', 'customer'];

    const CARD_TYPES = ['prepaid', 'debit', 'credit'];

    protected static $sign    = 'config';

    protected $entity         = 'config';

    protected $fillable = [
            self::NAME,
            self::CONFIG,
            self::TYPE,
            self::IS_DEFAULT,
    ];

    protected $visible = [
            self::ID,
            self::ENTITY,
            self::NAME,
            self::MERCHANT_ID,
            self::CONFIG,
            self::RESTRICTIONS,
            self::IS_DEFAULT,
            self::CREATED_AT,
            self::UPDATED_AT,
            self::IS_DELETED,
    ];

    protected $public = [
            self::ID,
            self::ENTITY,
            self::NAME,
            self::MERCHANT_ID,
            self::CONFIG,
            self::RESTRICTIONS,
            self::IS_DEFAULT,
            self::CREATED_AT,
            self::UPDATED_AT,
    ];

    protected $publicSetters = [
            self::ID,
            self::ENTITY,
            self::CONFIG,
    ];

    protected $dates = [
            self::CREATED_AT,
            self::UPDATED_AT,
    ];

    protected $defaults = [
            self::IS_DEFAULT      => false,
            self::RESTRICTIONS => null,
            self::IS_DELETED      => false,
    ];

    protected $casts = [
            self::IS_DEFAULT      => 'bool',
            self::IS_DELETED      => 'bool',
    ];

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function build(array $input = [], string $operation = 'create')
    {
        $this->getValidator()->validateInput($operation, $input);

        //Validating and save convenience fee config if applicable
        if((isset($input['type']) === true) and ($input['type'] === TYPE::CONVENIENCE_FEE))
        {
            $input['config'] =  (new Core)->validateAndSaveCustomerFeeConfig($input['config']);
        }

        $this->fillAndGenerateId($input);

        $this->config = json_encode($input['config']);

        if (isset($input['config']['restrictions']) === true)
        {
            $restrictions = $input['config']['restrictions'];

            $this->getValidator()->validateInput('add_restrictions', $restrictions);

            $allow = $restrictions['allow'];

            $this->getValidator()->validateRestrictionJson($allow, $this->merchant);

            $this->restrictions = json_encode($allow);
        }

        return $this;
    }

    public function setType(string $type)
    {
        $this->setAttribute(Entity::TYPE, $type);
    }

    public function setConfig(string $config)
    {
        $this->setAttribute(Entity::CONFIG, $config);
    }

    public function setPublicConfigAttribute(array & $input)
    {
        if (isset($input['config']) === true)
        {
            $input['config'] = json_decode($input['config'], true);
        }
    }

    public function getConfig()
    {
        return $this->getAttribute(Entity::CONFIG);
    }

    public function getFormattedConfig()
    {
        return json_decode($this->getConfig(), true);
    }

    public function getType()
    {
        return $this->getAttribute(Entity::TYPE);
    }
}
