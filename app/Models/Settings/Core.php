<?php

namespace RZP\Models\Settings;

use Settings;

use RZP\Models\Base;

class Core extends Base\Core
{
    protected $entity;

    protected $id;

    public function __construct(string $entityType, string $entityId)
    {
        parent::__construct();

        $this->entity = $entityType;

        $this->id = $entityId;
    }

    public static function for(string $entityType, string $entityId): self
    {
        // Validate for allowed

        return new static($entityType, $entityId);
    }

    public function create(string $key, string $value)
    {
        $this->setColumns();

        Settings::set($key, $value);

        $this->save();
    }

    public function get(string $key)
    {
        $this->setColumns();

        Settings::get($key);
    }

    public function update(string $key, string $value)
    {
        $this->create($key, $value);
    }

    public function delete(string $key)
    {
        $this->setColumns();

        Settings::forget($key);

        $this->save();
    }

    public function save()
    {
        Settings::save();
    }

    protected function setColumns()
    {
        $filterColumns = [
            'entity_type' => $this->entity,
            'entity_id'   => $this->id,
        ];

        Setting::setExtraColumns($filterColumns);
    }
}
