<?php

namespace RZP\Models\Settings;

use Setting;
use Razorpay\Spine\DataTypes\Dictionary;

use RZP\Models\Base;

class Accessor extends Base\Core
{
    /**
     * @var string
     */
    protected $entity;

    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $module;

    public function __construct(Base\PublicEntity $entity, string $module)
    {
        parent::__construct();

        $this->entity   = $entity->getEntity();

        $this->id       = $entity->getId();

        $this->module   = $module;
    }

    /**
     * @param Base\PublicEntity $entity
     * @param string            $module
     *
     * @return Accessor
     */
    public static function for(Base\PublicEntity $entity, string $module): Accessor
    {
        // TODO: Validate for allowed entities.

        return new static($entity, $module);
    }

    public function all()
    {
        $this->setColumns();

        $settings = Setting::all();

        return $this->serializeSettings($settings);
    }

    public function get(string $key)
    {
        $this->setColumns();

        $settings = Setting::get($key);

        return $this->serializeSettings($settings);
    }

    public function upsert($key, string $value = null): Accessor
    {
        $this->setColumns();

        Setting::set($key, $value);

        return $this;
    }

    public function delete(string $key): Accessor
    {
        $this->setColumns();

        Setting::forget($key);

        return $this;
    }

    public function save()
    {
        Setting::save();
    }

    /**
     * Set the extra columns that we filter on
     * https://github.com/anlutro/laravel-settings#example
     */
    protected function setColumns()
    {
        $filterColumns = [
            'entity_type' => $this->entity,
            'entity_id'   => $this->id,
            'module'      => $this->module
        ];

        Setting::setExtraColumns($filterColumns);
    }

    protected function serializeSettings($settings)
    {
        if ((is_array($settings) === true) or
            (is_null($settings) === true))
        {
            $settings = new Dictionary((array) $settings);
        }

        return $settings;
    }
}
