<?php

namespace RZP\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

use RZP\Constants\Mode;

use Elasticsearch\ClientBuilder;

class MigrateNotesToEs extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'rzp:migrateNotesToEs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates data from MySQL to ES';

    protected $client;
    protected $indexName;
    protected $databaseMode;
    protected $entityType;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $this->databaseMode = $this->option('mode');
        $this->entityType = $this->option('entity');

        rzpAssert(in_array($this->entityType, ['payments', 'refunds']));
        rzpAssert(in_array($this->databaseMode, [Mode::LIVE, Mode::TEST]));
        rzpAssert(!empty($this->databaseMode));
        rzpAssert(!empty($this->entityType));

        // TODO: Change to setSlaveDb later
        \Database\DefaultConnection::set($this->databaseMode);
        $this->setUpEs();

        $this->migrateNotes();
    }

    protected function migrateNotes()
    {
        $this->info("<info>Migrating $this->entityType notes from [$this->databaseMode mode] to ES index - [$this->indexName]...</info>");

        $skip = 0;
        $take = 1000;

        while(true)
        {
            $this->info("<info>Getting MySQL rows from $skip...</info>");

            $entities = DB::table($this->entityType)->select('id', 'notes', 'merchant_id', 'created_at')
                                                    ->orderBy('id', 'desc')
                                                    ->skip($skip)
                                                    ->take($take)
                                                    ->get();

            $this->info("<info>Storing $this->entityType in ES...</info>");

            $this->storeNotesInEs($entities);

            $this->info('<info>Sleeping for 1 second...</info>');
            sleep(1);

            if (count($entities) < $take)
            {
                break;
            }

            $skip += $take;

        }
    }

    protected function storeNotesInEs($entities)
    {
        foreach($entities as $entityData)
        {
            $entityId = $entityData->id;
            $merchantId = $entityData->merchant_id;
            $notes = json_decode($entityData->notes);
            $created = $entityData->created_at;

            $params['body'][] = [
                'index' => [
                    '_index' => $this->indexName,
                    '_type'  => $this->entityType,
                    '_id'    => $entityId,
                ]
            ];

            $params['body'][] = [
                'created'     => $created,
                'merchant_id' => $merchantId,
                'notes'       => $notes,
            ];
        }

        // If even one of them fails, the whole bulk fails.
        try
        {
            $updateResponse = $this->client->bulk($params);
            $this->info("<info>".json_encode($updateResponse)."</info>");
        }
        catch(Exception $ex)
        {
            $this->error("<error>Type Name : $this->entityType \n Index Name : $this->indexName \n Entity : ". json_encode($params). "</error>");
        }
    }

    protected function setUpEs()
    {
        $hostName = Config::get('database.es_host');

        $params = [
            'hosts' => [
                $hostName
            ],
        ];

        $this->client = ClientBuilder::fromConfig($params);

        $this->indexName = Config::get('database.es_index')[$this->databaseMode];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        $array = parent::getOptions();

        array_push($array, ['mode', null, InputOption::VALUE_REQUIRED, '[Mandatory] Mode (test/live) to be migrated']);

        array_push($array, ['entity', null, InputOption::VALUE_REQUIRED, '[Mandatory] Entity (payments/refunds) to migrate']);

        array_push($array, ['skip', null, InputOption::VALUE_REQUIRED, '[Optional] Pagination parameter']);

        return $array;
    }
}
