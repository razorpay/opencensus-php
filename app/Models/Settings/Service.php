<?php

namespace RZP\Models\Settings;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function get(string $entity, string $id, string $module, string $key): array
    {
        $entity = $this->fetchEntity($entity, $id);

        $settings = Accessor::for($entity, $module)->get($key);

        return ['settings' => $settings];
    }

    public function getDefined(string $key): array
    {
        $settings = Types::getWithDescriptions($key);

        return ['settings' => $settings];
    }

    public function getAll(string $entity, string $id, string $module): array
    {
        $entity = $this->fetchEntity($entity, $id);

        $settings = Accessor::for($entity, $module)->all();

        return ['settings' => $settings];
    }

    public function upsert(string $entity, string $id, $module, array $input)
    {
        // Validate input?

        $entity = $this->fetchEntity($entity, $id);

        Accessor::for($entity, $module)->create($input)->save();
    }

    public function delete(string $entity, string $id, string $module, string $key)
    {
        $entity = $this->fetchEntity($entity, $id);

        Accessor::for($entity, $module)->delete($key)->save();
    }

    protected function fetchEntity(string $entity, string $id): Base\PublicEntity
    {
        // TODO: Validate Entity whitelisted

        $entity = $this->repo
                       ->$entity
                       ->findOrFailPublic($id);

        return $entity;
    }
}
