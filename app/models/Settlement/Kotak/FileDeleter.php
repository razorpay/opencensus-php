<?php

namespace Models\Settlement\Kotak;

use EE\Exception;

/**
 * This class is used to handle generation of settlement reconciliation
 * files for running tests and in test mode
 */
class FileDeleter
{
    use FileHandlerTrait;

    protected $fileType = array(
        'setl_initiate',
        'reconcile',
        'return');

    protected $fileTypeMapping = array(
        'setl_initiate' => 'ReconciliationGenerator',
        'reconcile'     => 'Reconciler',
        'return'        => 'ReturnTransactions');

    public function deleteFileIfExists($setlFileType)
    {
        if (in_array($setlFileType, $this->fileType) === false)
        {
            throw new Exception\InvalidArgumentException('Not a valid type: ' . $setlFileType);
        }

        $class = __NAMESPACE__ . '\\' . $this->fileTypeMapping[$setlFileType];

        (new $class)->deleteFileIfExists();
    }
}