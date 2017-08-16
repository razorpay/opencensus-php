<?php

namespace RZP\Models\Merchant\FileProcessor;

use Mail;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Trace\Trace;
use RZP\Models\Batch;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\FileStore;
use RZP\Models\Base as BaseModel;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Base extends BaseModel\Core
{
    use FileHandlerTrait;

    /**
     * Lock wait timeout for batch entity
     */
    const MUTEX_LOCK_TIMEOUT = 2500;

    /**
     * The MUTEX instance
     */
    protected $mutex;
    /**
     * The merchant instance
     *
     * @var Merchant\Entity
     */
    protected $merchant;

    /**
     * Holds local file path of input and output file respectively.
     * They are re-used in the flow.
     * E.g.
     * - sending mails with attachment,
     * - unlinking post processing etc..
     */
    protected $inputFileLocalPath;
    protected $outputFileLocalPath;


    public function process(array $fileContents)
    {
        foreach ($fileContents as $row)
        {
            try
            {
                $this->processEntry($row);
            }
            catch (\Exception $e)
            {
                //TODO
                //trace
            }
        }
    }

    /**
     * This method needs to be implemented by the child classes.
     *
     * @param array $entry
     *
     */
    protected function processEntry(array $entry)
    {
        throw new \BadMethodCallException();
    }


    public function deleteFile(string $filePath)
    {
        if (file_exists($filePath))
        {
            $success = unlink($filePath);
        }
    }

    public function getDelimiter()
    {
        return ',';
    }

    public function getHeaders()
    {
        return [];
    }
}
