<?php

namespace RZP\Models\Settings;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function get(string $entity, string $id, string $key): array
    {
        $entity = $this->fetchEntity($entity, $id);

        $settings = Core::for($entity)->get($key);

        return ['settings' => $settings];
    }

    public function getAll(string $entity, string $id): array
    {
        $entity = $this->fetchEntity($entity, $id);

        $settings = Core::for($entity)->all();

        return ['settings' => $settings];
    }

    public function upsert(string $entity, string $id, array $input)
    {
        // Validate input

        $entity = $this->fetchEntity($entity, $id);

        Core::for($entity)->create($input);
    }

    public function delete(string $entity, string $id, string $key)
    {
        $entity = $this->fetchEntity($entity, $id);

        Core::for($entity)->delete($key);
    }

    protected function fetchEntity(string $entity, string $id): Base\PublicEntity
    {
        // Validate Entity whitelisted

        $entity = $this->repo
                       ->$entity
                       ->findOrFailPublic($id);

        return $entity;
    }
}
