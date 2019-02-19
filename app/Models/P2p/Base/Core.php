<?php

namespace RZP\Models\P2p\Base;

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
        $entities = $this->repo->newP2pQuery()
                               ->where($input)
                               ->get();

        return $entities;
    }

    public function fetch(string $id): Entity
    {
        $query = $this->repo->newP2pQuery();

        $query->getModel()->verifyIdAndSilentlyStripSign($id);

        $entity = $query->findOrFailPublic($id);

        return $entity;
    }

    public function find(string $id): Entity
    {
        return $this->repo->findByPublicId($id);
    }

    public function build(array $input): Entity
    {
        $entity = $this->repo->newP2pEntity();

        $entity->build($input);

        return $entity;
    }
}
