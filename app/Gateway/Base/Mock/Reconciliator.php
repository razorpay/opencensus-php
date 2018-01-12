<?php

namespace RZP\Gateway\Base\Mock;

use App;
use RZP\Models\FileStore;
use RZP\Base\RepositoryManager;

class Reconciliator
{
    /**
     * @var App
     */
    protected $app;

    /**
     * @var RepositoryManager
     */
    protected $repo;

    /**
     * Override this variable in the child class
     * @var string
     */
    protected $gateway;

    /**
     * @var string
     */
    protected $fileToWriteName;

    /**
     * Tells us if headers need to be added to the recon file
     * @var bool
     */
    protected $shouldAddHeaders = true;

    protected $fileExtension = FileStore\Format::XLSX;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];
    }

    public function generateReconciliation(array $input)
    {
        $entites = $this->getEntitiesToReconcile();

        $inputData = [];

        foreach ($entites as $entity)
        {
            $data[$entity->getEntity()] = $entity->toArray();

            $this->addGatewayEntityIfNeeded($data);

            $inputData[] = $data;
        }

        return $this->generate($inputData);
    }

    protected function generate(array $input)
    {
        $data = $this->getReconciliationData($input);

        $creator = $this->createFile($data);

        $file = $creator->get();

        return ['local_file_path' => $file['local_file_path']];
    }

    protected function getReconciliationData(array $input)
    {
        return [];
    }

    protected function getEntitiesToReconcile()
    {
        return [];
    }

    protected function createFile(
        array $content,
        string $type = FileStore\Type::MOCK_RECONCILIATION_FILE,
        string $store = FileStore\Store::S3)
    {
        $creator = new FileStore\Creator;

        $creator->extension($this->fileExtension)
                ->content($content)
                ->name($this->fileToWriteName)
                ->store($store)
                ->type($type)
                ->headers($this->shouldAddHeaders)
                ->save();

        return $creator;
    }

    protected function generateText($data, $glue = '~', $ignoreLastNewline = false)
    {
        $txt = '';

        $count = count($data);

        foreach ($data as $row)
        {
            $txt .= implode($glue, array_values($row));

            $count--;

            if (($ignoreLastNewline === false) or
                (($ignoreLastNewline === true) and ($count > 0)))
            {
                $txt .= "\r\n";
            }
        }

        return $txt;
    }

    /**
     * Not all methods need the gateway entity to generate the recon file.
     * The purpose of this method is to eliminate n DB calls for n payments.
     * To eliminate the DB calls, override this method in the base class.
     *
     * @param array $data
     */
    protected function addGatewayEntityIfNeeded(array & $data) {}

    /**
     * This can be used for mock recon content function
     * @param $content
     * @param null $action
     * @return void
     */
    public function content(& $content, $action = null) {}
}
