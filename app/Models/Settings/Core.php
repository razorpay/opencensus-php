<?php

namespace RZP\Models\Settings;

use Razorpay\Spine\DataTypes\Dictionary;
use Setting;

use RZP\Models\Base;

class Core extends Base\Core
{
    protected $entity;

    protected $id;

    public function __construct(Base\PublicEntity $entity)
    {
        parent::__construct();

        $this->entity = $entity->getEntity();

        $this->id = $entity->getId();
    }

    public static function for(Base\PublicEntity $entity): self
    {
        // Validate for allowed entities

        return new static($entity);
    }

    public function create($key, string $value = null)
    {
        $this->setColumns();

        Setting::set($key, $value);

        return $this;
    }

    public function get(string $key)
    {
        $this->setColumns();

        $settings = Setting::get($key);

        return $this->serializeSettings($settings);
    }

    public function all()
    {
        $this->setColumns();

        $settings = Setting::all();

        return $this->serializeSettings($settings);
    }

    public function update($key, string $value = null)
    {
        return $this->create($key, $value);
    }

    public function delete(string $key)
    {
        $this->setColumns();

        Setting::forget($key);

        return $this;
    }

    public function save()
    {
        Setting::save();
    }

    protected function setColumns()
    {
        $filterColumns = [
            'entity_type' => $this->entity,
            'entity_id'   => $this->id,
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
