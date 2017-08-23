<?php

namespace RZP\Models\Merchant\FileProcessor;

use RZP\Exception;

class Validator
{
    const MERCHANT_FILE_DETAILS_VALIDATION = [
        Orchestrator::IRCTC
    ];

    /**
     * For emails without attachments, but links, we allow
     * zero attachments during the initial validation.
     * After we get the attachments from the link, we validate
     * it again.
     *
     * @param array $input
     * @param bool  $allowZeroAttachments
     *
     * @throws Exception\BadRequestException
     */
    public function validateAttachments(array & $input)
    {
        //
        // Gets all the attachments found in the input by checking the number of
        // input keys starting with 'attachment-'.
        // Excludes 'attachment-count'.
        //
        $foundAttachments = array_filter(
            $input,
            function($key)
            {
                return (strpos($key, 'attachment-') === 0) and
                       (strpos($key, 'attachment-count') === false);
            },
            ARRAY_FILTER_USE_KEY
        );

        $foundAttachmentsCount = count($foundAttachments);

        //
        // In link based emails, we don't have the attachments at
        // this point. Hence, it'll be 0. This is fine, since we
        // update the attachment-count at a later point.
        //
        // Otherwise, there should be at least 1 attachment present.
        //
        if ($foundAttachmentsCount === 0)
        {
            throw new Exception\BadRequestException(
                'No attachments found in the input.'
            );
        }

        // Sets 'attachment-count' if not present and returns.
        // If present, converts it to int.
        if (isset($input['attachment-count']) === false)
        {
            $input['attachment-count'] = $foundAttachmentsCount;
        }
        else
        {
            $input['attachment-count'] = intval($input['attachment-count']);

            // The input's attachment-count and found attachments count should be equal.
            if ($input['attachment-count'] !== $foundAttachmentsCount)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'The number of attachments found, does not match with the attachment-count input',
                    ['attachments_found' => $foundAttachmentsCount, 'attachment_count' => $input['attachment-count']]
                );
            }
        }
    }

    public function validateFileDetails(array $input, array $fileDetails)
    {
        if (empty($fileDetails) === true)
        {
            throw new Exception\BadRequestException(
                'File Details are empty.'
            );
        }

        $merchant = studly_case($input['merchant']);

        if (in_array($merchant, self::MERCHANT_FILE_DETAILS_VALIDATION) === true)
        {
            $fileDetailsValidator = 'validate' . $merchant . 'FileDetails';

            $this->$fileDetailsValidator($fileDetails);
        }
    }

    public function validateIrctcFileDetails($fileDetails)
    {
        $refundType = false;

        $settlementType = false;

        if (count($fileDetails) !== 2)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The number of attachments sent should be 2'
            );
        }

        foreach ($fileDetails as $fileDetail)
        {
            $fileName = $fileDetail['file_name'];

            if (strpos($fileName, 'refund') !== false)
            {
                $refundType = true;
            }
            else if (strpos($fileName, 'settlement') !==false)
            {
                $settlementType = true;
            }
        }

        if (($refundType === false) or
            ($settlementType === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Both settlement file and refund file needs to be sent'
            );
        }
    }
}
