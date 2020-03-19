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
            self::IS_DEFAULT
    ];

    protected $public = [
            self::ID,
            self::ENTITY,
            self::NAME,
            self::MERCHANT_ID,
            self::CONFIG,
            self::IS_DEFAULT,
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
            self::DEFAULT      => false,
            self::RESTRICTIONS => null,
    ];

    protected $casts = [
            self::DEFAULT      => 'bool',
    ];

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function build(array $input = [], string $operation = 'create')
    {
        $this->getValidator()->validateInput($operation, $input);

        $this->fillAndGenerateId($input);

        $this->config = json_encode($input['config']);

        if (isset($input['config']['allow']))
        {
            $this->restrictions = json_encode($input['config']['allow']);
        }

        return $this;
    }

    public function setType(string $type)
    {
        $this->setAttribute(Entity::TYPE, $type);
    }

    public function setPublicConfigAttribute(array & $input)
    {
        if (isset($input['config']))
        {
            $input['config'] = json_decode($input['config'], true);
        }
    }
}
