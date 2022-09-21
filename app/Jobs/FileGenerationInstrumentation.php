<?php

namespace RZP\Jobs;

//use Monolog\Logger;
use RZP\Trace\TraceCode;
use RZP\Models\Gateway\File;
use Razorpay\Trace\Logger as Trace;

class FileGenerationInstrumentation extends Job
{
    const QUEUE_NAME_KEY  = 'emandate_files_instrumentation';

    protected $data;

    protected $gatewayFileId;

    protected $gatewayFile;

    protected $fileId;

    protected $queueConfigKey = self::QUEUE_NAME_KEY;

    public $timeout = 6 * 3600; // 6 hours timeout for  processing large citi data

    public function __construct(string $gatewayFileId, string $fileId, string $mode)
    {
        $this->gatewayFileId = $gatewayFileId;

        $this->fileId = $fileId;

        parent::__construct($mode);
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(TraceCode::FILE_GENERATE_INSTRUMENTATION_JOB_INIT,
            [
                'gateway_file_id' => $this->gatewayFileId,
                'file_id'         => $this->fileId
            ]);

        try
        {
            $gatewayFile = $this->repoManager->gateway_file->findOrFailPublic($this->gatewayFileId);

            $type = $gatewayFile->getType();

            $target = $gatewayFile->getTarget();

            $processor = $this->getProcessor($type, $target);

            $processor->instrumentationProcess($gatewayFile, $this->fileId);

            $this->trace->info(TraceCode::FILE_GENERATE_INSTRUMENTATION_JOB_COMPLETE,
                [
                    'gateway_file_id' => $this->gatewayFileId,
                    'file_id'         => $this->fileId
                ]);

        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::FILE_GENERATE_INSTRUMENTATION_JOB_ERROR,
                [File\Entity::ID => $this->fileId]
            );
        }
    }

    public function getProcessor(string $type, string $target)
    {
        $driver = $this->getProcessorDriver($type, $target);

        return new $driver;
    }

    /**
     * Gets GatewayFile Processor's namespace
     *
     * @param string $type
     * @param string $target
     * @return string
     *
     * $folderStruct is the nested folder structure inside the folder Processor
     * The following 3 lines replace '_' with '\\', and convert every first letter to uppercase
     * For example:
     *      $type = emandate_register
     *      $taget = hdfc
     *      $folderStruct = Emandate\\Register
     *      $driveNameSpace = 'RZP\\Models\\Gateway\\File\\Processor\\Emandate\\Register\\Hdfc'
     */
    protected function getProcessorDriver(string $type, string $target): string
    {
        $baseNamespace = 'RZP\\Models\\Gateway\\File\\Instrumentation\\';

        $folderStruct = explode('_', $type);
        $folderStruct = array_map('ucfirst', $folderStruct);
        $folderStruct = implode('\\', $folderStruct);

        return $baseNamespace . $folderStruct . '\\' . studly_case($target);
    }
}
