<?php

namespace RZP\Models\Merchant\FileProcessor;

use RZP\Exception;

class Validator
{
    const MERCHANTS = [
        Orchestrator::IRCTC
    ];

    /**
     * Validate the input should have `merchant` key set and should be one of the valid merchants.
     *
     * @param array $input
     * @throws Exception\BadRequestException
     */
    public function validateMerchant(array $input)
    {
        if ((isset($input['merchant']) === false) and
            (in_array($input['merchant'], self::MERCHANTS, true) === false))
        {
            throw new Exception\BadRequestException('No merchant found in the input.');
        }
    }

    /**
     * Validate the input should have `attachment-` files
     *
     * @param array $input
     * @throws Exception\BadRequestException
     */
    public function validateAttachments(array $input)
    {
        // Gets all the attachments found in the input by checking the number of
        // input keys starting with 'attachment-'.
        $foundAttachments = array_filter(
            $input,
            function($key)
            {
                return (strpos($key, 'attachment-') === 0);
            },
            ARRAY_FILTER_USE_KEY
        );

        $foundAttachmentsCount = count($foundAttachments);

        if ($foundAttachmentsCount === 0)
        {
            throw new Exception\BadRequestException('No attachments found in the input.');
        }
    }

    public function validateFileDetails(array $fileDetails, string $merchant)
    {
        if (empty($fileDetails) === true)
        {
            throw new Exception\BadRequestException('File Details are empty.');
        }

        $fileDetailsValidator = 'validate' . studly_case($merchant) . 'FileDetails';

        if (function_exists($fileDetailsValidator) === true)
        {
            $this->$fileDetailsValidator($fileDetails);
        }
    }

    /**
     * 1. Validate only 2 files are present
     * 2. Validate only `refund_` and `settlement_` files are present
     *
     * @param array $fileDetails
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function validateIrctcFileDetails(array $fileDetails)
    {
        if (count($fileDetails) !== 2)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The number of attachments sent should be 2');
        }

        $fileNames = array_column($fileDetails, 'file_name');

        $files = array_filter(
            $fileNames,
            function($name)
            {
                return ((strpos($name, 'refund_') === 0) or
                        (strpos($name, 'settlement_') === 0));
            },
            ARRAY_FILTER_USE_BOTH
        );

        if (count($files) === 2)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Both settlement file and refund file needs to be sent');
        }
    }
}
