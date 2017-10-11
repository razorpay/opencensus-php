<?php

namespace RZP\Gateway\Netbanking\Base;

/**
 * This file processor will be used for both generating, and processing response files of Registration, and Debit
 *
 * Create 2 files EMandateRegistration, and EMandateDebit in each gateway, and inherit them from this class
 * Write individual gateway implementation in them
 */
class EMandateFileProcessor
{
    public function generateFile(array $input): array
    {
        // Child classes must implement them all
        $this->validateFileGenerateParams($input);

        $fileData = $this->buildDataToSend($input);

        $generateResponse = $this->generateFile($fileData);

        // Create $response
        $response = $this->emailFile($generateResponse);

        return $response;
    }

    public function reconcileResponseFile(array $input): array
    {
        // Child classes must implement them all
        $this->validateResponseFileParams($input);

        $parsedData = $this->parseFile($input);

        // Create $response
        $response = $this->processFileData();

        return $response;
    }
}