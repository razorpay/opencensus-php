<?php


namespace RZP\Models\Merchant\Cron\Collectors\Core;


use RZP\Constants\Entity;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;

abstract class DbDataCollector extends BaseCollector
{
    abstract protected function collectDataFromSource(): CollectorDto;

    /**
     * Sometimes we need to run the cron job manually on a specific data set (for testing purposes or if cron fails)
     */
    private function collectDataFromArgs(): CollectorDto
    {
        $data = $this->args['input'] ?? null;

        return CollectorDto::create($data);
    }

    public function collect(): CollectorDto
    {
        $dto = $this->collectDataFromArgs();

        if(empty($dto->getData()) === false)
        {
            return $dto;
        }

        return $this->collectDataFromSource();
    }

    public function getRepository(string $entityName)
    {
        $repo = Entity::getEntityRepository($entityName);

        return new $repo;
    }
}
