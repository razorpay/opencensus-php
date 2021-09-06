<?php


namespace RZP\Models\Dispute\Evidence\Document;

use App;
use RZP\Base;
use RZP\Trace\TraceCode;
use RZP\Models\GenericDocument;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{

    protected $app;

    /**
     * Validator constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->app = App::getFacadeRoot();
    }

    protected static $createManyRules = [
        Types::SHIPPING_PROOF             => 'sometimes|array',
        Types::BILLING_PROOF              => 'sometimes|array',
        Types::CANCELLATION_PROOF         => 'sometimes|array',
        Types::CUSTOMER_COMMUNICATION     => 'sometimes|array',
        Types::EXPLANATION_LETTER         => 'sometimes|array',
        Types::REFUND_CONFIRMATION        => 'sometimes|array',
        Types::ACCESS_ACTIVITY_LOG        => 'sometimes|array',
        Types::TERMS_AND_CONDITIONS       => 'sometimes|array',
        Types::OTHERS                     => 'sometimes|array',
        Types::REFUND_CANCELLATION_POLICY => 'sometimes|array',
        Types::PROOF_OF_SERVICE           => 'sometimes|array',
    ];

    protected static $createRules = [
        Entity::DISPUTE_ID  => 'required|size:14',
        Entity::DOCUMENT_ID => 'required|size:14|custom',
        Entity::TYPE        => 'required|custom',
        Entity::SOURCE      => 'required|custom',
        Entity::CUSTOM_TYPE => 'required_if:type,others',
    ];

    public function validateCreateManyInput(array $createManyInput, $allowEmpty = false)
    {
        if ((count($createManyInput) === 0) and
            ($allowEmpty === false))
        {
            throw new BadRequestValidationFailureException(Constants::ATLEAST_ONE_EVIDENCE_DOCUMENT_REQUIRED_ERROR_MESSAGE,
                Entity::TYPE);
        }

        $this->validateInput('create_many', $createManyInput);
    }

    public function validateBulkCreateInput($inputs, $allowEmpty = false)
    {
        if ((count($inputs) > 0) or
            ($allowEmpty === true))
        {
            return;
        }

        throw new BadRequestValidationFailureException(Constants::ATLEAST_ONE_EVIDENCE_DOCUMENT_REQUIRED_ERROR_MESSAGE);
    }

    protected function validateDocumentId($attribute, $value)
    {
        $this->app->trace->info(TraceCode::EVIDENCE_DOCUMENT_CREATE_VERIFYING_DOC_ID, [
            Entity::DOCUMENT_ID => $value,
        ]);

        $documentId = 'doc_' . $value;

        // if the document doesnt exist or doesnt belong to this merchant, below line throws an exception which is propagated
        $data = (new GenericDocument\Service())->getDocument([], $documentId);

        if ((isset($data[GenericDocument\Constants::PURPOSE]) === false) or
            ($data[GenericDocument\Constants::PURPOSE] !== GenericDocument\Constants::DISPUTE_EVIDENCE))
        {

            $purpose = $data[GenericDocument\Constants::PURPOSE] ?? '';

            $exceptionData = [
                'document_id' => $documentId,
                'purpose'     => $purpose,
            ];

            $message = "Only documents with purpose 'dispute_evidence' maybe submitted. {$documentId} is of purpose '{$purpose}'";

            throw new BadRequestValidationFailureException($message, GenericDocument\Constants::PURPOSE, $exceptionData);
        }


        $this->app->trace->info(TraceCode::EVIDENCE_DOCUMENT_CREATE_VERIFIED_DOC_ID, [
            Entity::DOCUMENT_ID => $value,
        ]);
    }

    protected function validateType($attribute, $value)
    {
        if (Types::isValidType($value) === true)
        {
            return;
        }

        $exceptionData = [
            $attribute => $value,
        ];

        throw new BadRequestValidationFailureException('invalid document type',
            Entity::TYPE,
            $exceptionData);
    }

    protected function validateSource($attribute, $value)
    {

    }
}