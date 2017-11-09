<?php

namespace RZP\Console\Commands;

use App;
use Aws;
use Carbon\Carbon;
use Illuminate\Console\Command;

use RZP\Models\Batch;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;

/**
 * Earlier for batch entries we had S3 URLs in table for input and output files.
 * Later only we started using UFH and had ensured backward compatibility. We
 * are now creating UFH entity for those old batch entries as well and will be
 * removing the table columns and code to handle backward compatibility.
 *
 */
class MigrateOldBatchToUfh extends Command
{
    protected $signature   = 'rzp:batch:migrate_to_ufh

                             {mode : Database mode (live|test)? }

                             {--pretend   : Whether to run the command in pretend mode?}
                             {--skip=0    : Skip offset (eg. skip first 100 rows) }
                             {--take=100  : Take count (eg. 1000 at a time) }
                             {--start_at= : Start value(epoch) for time range query }
                             {--end_at=   : End value(epoch) for time range query }';

    protected $description = 'Migrates old batch entities to UFH';

    protected $mode;
    protected $pretend;
    protected $skip;
    protected $take;
    protected $startAt;
    protected $endAt;

    protected $s3;
    protected $bucket;
    protected $region;
    protected $repo;
    protected $trace;

    public function fire()
    {
        $this->setOptions();

        $this->init();

        $this->migrate();
    }

    protected function setOptions()
    {
        $this->mode    = $this->argument('mode');
        $this->pretend = $this->option('pretend');
        $this->skip    = (int) $this->option('skip');
        $this->take    = (int) $this->option('take');
        $this->startAt = $this->option('start_at');
        $this->endAt   = $this->option('end_at');
    }

    protected function init()
    {
        \Database\DefaultConnection::set($this->mode);

        $app = App::getFacadeRoot();
        $app['rzp.mode'] = $this->mode;

        $this->repo = $app['repo'];
        $this->trace = $app['trace'];

        $config    = $app['config'];
        $awsConfig = $config['aws'];

        // We use bucket_region to construct the s3 instance
        $awsConfig['region'] = $awsConfig['bucket_region'];

        $this->s3 = (new Aws\Sdk($awsConfig))->createClient('s3');

        $this->bucket = $awsConfig['settlement_bucket'];
        $this->region = $awsConfig['region'];
    }

    protected function migrate()
    {
        $skip = $this->skip;

        while (true)
        {
            $batches = $this->repo
                            ->batch
                            ->getBatchesToMigrateToUfh(
                                $skip,
                                $this->take,
                                $this->startAt,
                                $this->endAt);

            $count = $batches->count();

            $this->info('Total: ' . $count);

            if ($count === 0)
            {
                break;
            }

            $skip += $this->take;

            foreach ($batches as $batch)
            {
                $this->info('Id: ' . $batch->getId());

                if ($batch->getUploadFileUrl() !== null)
                {
                    $this->createUfhEntityOfTypeForBatch(FileStore\Type::BATCH_INPUT, $batch);
                }

                if ($batch->getDownloadFileUrl() !== null)
                {
                    $this->createUfhEntityOfTypeForBatch(FileStore\Type::BATCH_OUTPUT, $batch);
                }
            }
        }
    }

    /**
     * Fetches files meta data from S3 and then creates corresponding UFH entity.
     * This method gets called twice for 2 different types (input and output) as
     * every batch has those 2 kind of files.
     *
     * @param string       $type
     * @param Batch\Entity $batch
     */
    protected function createUfhEntityOfTypeForBatch(string $type, Batch\Entity $batch)
    {
        $filePrefix = ($type === FileStore\Type::BATCH_INPUT) ?
                        Batch\Entity::INPUT_FILE_PREFIX :
                        Batch\Entity::OUTPUT_FILE_PREFIX;

        $s3RequestParams = [
            'Bucket' => $this->bucket,
            'Key'    => $filePrefix . $batch->getFileKeyWithExt(),
        ];

        try
        {
            $s3Meta = $this->s3->headObject($s3RequestParams);
        }
        catch (\Throwable $e)
        {
            $this->error($e);

            $this->trace->traceException($e, null, TraceCode::BATCH_MIGRATE_DEBUG);

            return;
        }

        $this->trace->debug(
            TraceCode::BATCH_MIGRATE_DEBUG,
            [
                'operation' => 's3_meta',
                'id'        => $batch->getId(),
                'body'      => [
                    's3_request_params' => $s3RequestParams,
                    's3_response'       => $s3Meta,
                ],
            ]);

        $now = Carbon::now(Timezone::IST)->getTimestamp();

        $ufhCreateParams = [
            FileStore\Entity::ID          => FileStore\Entity::generateUniqueId(),
            FileStore\Entity::MERCHANT_ID => $batch->getMerchantId(),
            FileStore\Entity::TYPE        => $type,
            FileStore\Entity::ENTITY_ID   => $batch->getId(),
            FileStore\Entity::ENTITY_TYPE => $batch->getEntity(),
            FileStore\Entity::EXTENSION   => FileStore\Format::XLSX,
            FileStore\Entity::MIME        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            FileStore\Entity::SIZE        => $s3Meta['ContentLength'],
            FileStore\Entity::NAME        => $filePrefix . $batch->getFileKey(),
            FileStore\Entity::STORE       => FileStore\Store::S3,
            FileStore\Entity::LOCATION    => $filePrefix . $batch->getFileKeyWithExt(),
            FileStore\Entity::BUCKET      => $this->bucket,
            FileStore\Entity::REGION      => $this->region,
            FileStore\Entity::CREATED_AT  => $now,
            FileStore\Entity::UPDATED_AT  => $now,
        ];

        $this->trace->debug(
            TraceCode::BATCH_MIGRATE_DEBUG,
            [
                'operation' => "ufh_create_{$type}",
                'id'        => $batch->getId(),
                'body'      => $ufhCreateParams,
            ]);

        $entity = (new FileStore\Entity)->forceFill($ufhCreateParams);

        if ($this->pretend === true)
        {
            return;
        }

        try
        {
            $this->repo->saveOrFail($entity);
        }
        catch (\Throwable $e)
        {
            $this->error($e);

            $this->trace->traceException(
                $e,
                null,
                TraceCode::BATCH_MIGRATE_DEBUG,
                [
                    'operation' => "ufh_create_{$type}_save",
                    'id'        => $batch->getId(),
                ]);

            return;
        }
    }
}
