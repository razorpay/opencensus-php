<?php

namespace RZP\Models\FundTransfer\Kotak;

use RZP\Exception;

/**
 * This class is used to handle generation of settlement reconciliation
 * files for running tests and in test mode
 */
class FileDeleter
{
    use FileHandlerTrait;

    protected $fileType = [
        'setl_initiate',
        'reconcile',
    ];

    protected $fileTypeMapping = [
        'setl_initiate' => 'Reconciliation\\Mock\\FileGenerator2',
        'reconcile'     => 'Reconciliation\\Processor',
    ];

    public function deleteFileIfExists($setlFileType)
    {
        if (in_array($setlFileType, $this->fileType, true) === false)
        {
            throw new Exception\InvalidArgumentException('Not a valid type: ' . $setlFileType);
        }

        $class = __NAMESPACE__ . '\\' . $this->fileTypeMapping[$setlFileType];

        (new $class)->deleteFileIfExists();
    }
}
