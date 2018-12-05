<?php

namespace RZP\Models\PaymentLink\Template;

use RZP\Models\Base\Entity;

class SettingsAccess implements StorageAccess
{
    /**
     * @var Entity
     */
    protected $entity;

    /**
     * True if schema is already loaded.
     * @var boolean
     */
    protected $schemaLoaded = false;

    /**
     * Loaded schema copy to server multiple reads without hitting database.
     * @var string|null
     */
    protected $schema;

    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }

    /**
     * {@inheritDoc}
     */
    public function exists(): bool
    {
        return ($this->get() !== null);
    }

    /**
     * {@inheritDoc}
     */
    public function get()
    {
        if ($this->schemaLoaded === false)
        {
            $this->schemaLoaded = true;
            $this->schema       = $this->entity->getSettingsScalarElseNull('udf_schema');
        }

        return $this->schema;
    }
}
