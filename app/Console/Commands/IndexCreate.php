<?php

namespace RZP\Console\Commands;

use Config;
use Illuminate\Console\Command;
use Elasticsearch\ClientBuilder;

/**
 * Creates index for given entity.
 * Picks settings and mappings from configuration file(config/es_mappings.php).
 */
class IndexCreate extends Command
{
    protected $signature = 'rzp:index_create

                            {mode         : Application mode (test|live) }
                            {entity       : Entity name (e.g. item|merchant) }
                            {index_prefix : ES index prefix (e.g. 20171201_beta_api_) }
                            {type_prefix  : ES type prefix (e.g. beta_api_) }
                            {num_shards=5 : Number of shards (Defaults to 5) }
                            {num_reps=1   : Number of replicas (Defaults to 1) }

                            {--pretend    : Whether to run the command in pretend mode?}
                            {--reindex    : Whether to delete existing index?}';

    protected $description = 'Creates index with set mappings for the entity';

    protected $entity;
    protected $index;
    protected $type;
    protected $numShards;
    protected $numReplicas;

    /**
     * Just outputs the settings with which the index will get created.
     * Dry run.
     *
     * @var boolean
     */
    protected $pretend;

    /**
     * Whether to drop and recreate index if it already exits.
     *
     * @var boolean
     */
    protected $reindex;

    public function handle()
    {
        $this->setOptions();

        $params = $this->getEsCreateRequestParams();

        if ($this->pretend === true)
        {
            $this->info("Index will be created with following parameters:\n");
            $this->info(json_encode($params, JSON_PRETTY_PRINT));

            return;
        }

        //
        // If index already exists, just delete it and continue with the flow
        // of creating it again.
        //
        $client = $this->getEsClient();

        $indexParams = ['index' => $this->index];

        if (($this->reindex === true) and
            ($client->indices()->exists($indexParams) === true))
        {
            $client->indices()->delete($indexParams);
        }

        $result = $client->indices()->create($params);

        $this->info(json_encode($result, JSON_PRETTY_PRINT));
    }

    protected function setOptions()
    {
        $this->entity  = $this->argument('entity');
        $this->pretend = $this->option('pretend');
        $this->reindex = $this->option('reindex');
        $this->numShards = (int) $this->argument('num_shards');
        $this->numReplicas = (int) $this->argument('num_reps');

        // Sets index and type names
        $mode        = $this->argument('mode');
        $indexPrefix = $this->argument('index_prefix');
        $typePrefix  = $this->argument('type_prefix');

        $suffix      = "{$this->entity}_{$mode}";
        $this->index = $indexPrefix . $suffix;
        $this->type  = $typePrefix . $suffix;
    }

    protected function getEsClient()
    {
        $host = Config::get('database.es_host');

        $params = ['hosts' => [$host]];

        return ClientBuilder::fromConfig($params);
    }

    protected function getEsCreateRequestParams()
    {
        $config = Config::get('es_mappings');

        $settings = $config['settings'];
        $settings['number_of_shards'] = $this->numShards;
        $settings['number_of_replicas'] = $this->numReplicas;

        //
        // Get mappings:
        // Use default notes entities mappings as base for entities having notes,
        // Overrides with the entity mappings.
        //
        $hasNotes = in_array($this->entity, $config['has_notes'], true);

        $mappings = [];

        if ($hasNotes)
        {
            $mappings = $config['notes_entity_mapping'];
        }

        $entityMappings = $config["{$this->entity}_mapping"] ?? null;

        if ($entityMappings === null)
        {
            throw new \Exception('No mappings found');
        }

        $mappings = array_replace_recursive($mappings, $entityMappings);

        return [
            'index' => $this->index,
            'body'  => [
                'settings' => $settings,
                'mappings' => [$this->type => $mappings],
            ],
        ];
    }
}
