<?php

namespace RZP\Reconciliator\RequestProcessor;

use RZP\Exception;

class Manual extends Base
{
    /**
     * Validations and getting file details are handled by this function
     * when reconciliation route is hit via REST Client/dashboard.
     *
     * @param array $input The input received from the route.
     * @return array Details of all the files received from the input.
     */
    public function process(array $input): array
    {
        // Validates the input received.
        // All the attachment files names should start with 'attachment-'
        // Also, adds attachment-count to input, if not present already.
        $this->validator->validateAttachments($input);

        $inputDetails = $this->getInputDetails($input);

        // Figures out the gateway and
        // sets the gateway reconciliator object for the orchestrator
        $this->setGatewayFromInput($inputDetails);

        $allFilesDetails = $this->getFileDetailsFromInput($inputDetails, $input);

        return [
            self::FILE_DETAILS  => $allFilesDetails,
            self::INPUT_DETAILS => $inputDetails,
        ];
    }

    /**
     * Gets the required details from the input, structured.
     * This includes the gateway for which the reconciliation
     * needs to be done and the number of attachments. This is an
     * optional parameter.
     *
     * @param array $input
     * @return array Structured input details
     */
    protected function getInputDetails(array $input): array
    {
        $inputDetails = [
            self::ATTACHMENT_COUNT => $input['attachment-count'],
            self::GATEWAY          => $input['gateway'],
            self::FORCE_UPDATE     => $input[self::FORCE_UPDATE] ?? [],
        ];

        $this->validator->validateManualInput($inputDetails);

        return $inputDetails;
    }

    /**
     * Uses the gateway input sent in the route, to set the gateway
     * reconciliator object for the class. The gateway should be
     * present in the GATEWAY_SENDER_MAPPING list.
     *
     * @param array $inputDetails
     * @throws Exception\ReconciliationException
     */
    protected function setGatewayFromInput(array $inputDetails)
    {
        // In manual, the input params should contain what gateway is it.
        $this->gateway = $inputDetails[self::GATEWAY];

        // Sets the gateway reconciliator object for the orchestrator.
        $this->setGatewayReconciliatorObject();
    }
}
