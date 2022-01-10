<?php


namespace RZP\Models\Merchant\Cron\Traits;


use RZP\Models\Merchant\Cron\Dto\CollectorDto;

trait ConnectionFallbackMechanism
{
    protected $connection;

    abstract public function getPrimaryConnection();

    abstract public function getFallbackConnection();

    public function getRepository(string $entityName)
    {
        $repoInstance = parent::getRepository($entityName);

        return $repoInstance->connection($this->connection);
    }

    public function collectDataFromSource(): CollectorDto
    {
        try
        {
            $this->connection = $this->getPrimaryConnection();
            return parent::collectDataFromSource();
        }
        catch (\Throwable $e)
        {
            $this->connection = $this->getFallbackConnection();
            return parent::collectDataFromSource();
        }
    }
}
