<?php

namespace RZP\Models\P2p\Base;

use RZP\Exception\LogicException;
use RZP\Models\Base\PublicCollection;
use RZP\Models\P2p\Base\Traits\ApplicationTrait;

class Core
{
    use ApplicationTrait;

    /**
     * @var Repository
     */
    protected $repo;

    public function __construct()
    {
        $this->bootApplicationTrait();

        $this->repo = $this->getNewRepository();
    }

    protected function getNewRepository()
    {
        $className = str_replace('\Core', '\Repository', static::class);

        return new $className;
    }

    /**
     * @param array $input
     *
     * @return PublicCollection
     */
    public function fetchAll(array $input): PublicCollection
    {
        $entities = $this->repo->fetch($input);

        return $entities;
    }

    public function fetch(string $id, $withTrashed = false): Entity
    {
        $query = $this->repo->newP2pQuery();

        $query->getModel()->verifyIdAndSilentlyStripSign($id);

        if ($withTrashed === true)
        {
            return $query->withTrashed()->findOrFailPublic($id);
        }

        return $query->findOrFailPublic($id);
    }

    public function find(string $id, bool $signed = true): Entity
    {
        if($signed === false)
        {
            return $this->repo->find($id);
        }

        return $this->repo->findByPublicId($id);
    }

    public function build(array $input): Entity
    {
        $entity = $this->repo->newP2pEntity();

        $entity->build($input);

        return $entity;
    }

    public function deleteAll()
    {
        $query = $this->repo->newP2pQuery();

        if ($query->getModel()->canSoftDelete() === false)
        {
            throw $this->logicException('Can not soft delete the entity', [
                Entity::ENTITY => $query->getModel()->getP2pEntityName(),
            ]);
        }

        return $query->delete();
    }
}
