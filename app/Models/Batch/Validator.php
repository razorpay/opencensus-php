<?php

namespace RZP\Models\Batch;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace;
use RZP\Trace\TraceCode;
use RZP\Models\Settlement\Kotak\FileHandlerTrait;


class Validator extends Base\Validator
{
    use FileHandlerTrait;

    protected static $fileToReadName = 'Batch_File';

    protected static $createRules = array(
        'amount'                    => 'sometimes|integer',
        'uploaded_file_url'         => 'sometimes|string|max:100',
        'download_file_url'         => 'sometimes|string|max:100',
        'total_count'               => 'sometimes|integer',
        'type'                      => 'sometimes|string|max:100'

    );

    protected static $createValidators = array(

    );

    public function validateInputFile($input)
    {
        if (!isset($input['file']))
        {
            throw new Exception\BadRequestException('Input file not set');
        }

        $this->validateType($input);
    }

    public function validateType($input)
    {
        if (!isset($input['type']))
        {
            throw new Exception\BadRequestException('Input file type is not set');
        }
    }

    public function validateEntries($entries, $type)
    {
        $totalEntries = count($entries);

        if ($totalEntries > 1000)
        {
           throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FILE_EXCEED_LIMIT);
        }

        $validator = 'validate' .ucfirst($type);
        $this->$validator($entries);
    }

    public function validateRefund($entries)
    {
        $headers = array('payment_id', 'refund_amount');

        foreach ($entries as $entry)
        {
            $entryMap = array_combine($headers, $entry);

            $amount = $entryMap['refund_amount'];
            $paymentId = $entryMap['payment_id'];

            if (!isset($paymentId))
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FILE_VALIDATION);
            }
            elseif (!isset($amount))
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FILE_VALIDATION);
            }
        }
    }
}
