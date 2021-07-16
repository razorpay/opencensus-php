<?php


namespace RZP\Models\Dispute\Evidence;

use RZP\Models\Base;
use RZP\Models\Dispute;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    const PATCH_DISPUTE_EVIDENCE_CREATE_HANDLER = "createForDispute";
    const PATCH_DISPUTE_EVIDENCE_UPDATE_HANDLER = "updateForDispute";



    public function handlePatchDisputeEvidence(Dispute\Entity $dispute, array $input) : Entity
    {
        $handler = $this->getHandlerForPatchDisputeEvidence($dispute, $input);

        $this->trace->info(TraceCode::DISPUTE_PRESENTMENT_HANDLE_PATCH_EVIDENCE_INPUT, [
            'input'   => $input,
            'handler' => $handler,
        ]);

        switch ($handler)
        {
            case self::PATCH_DISPUTE_EVIDENCE_CREATE_HANDLER:
                return $this->createForDispute($dispute, $input);
            case self::PATCH_DISPUTE_EVIDENCE_UPDATE_HANDLER:
                return $this->updateForDispute($dispute, $input);
        }
    }

    public function createForDispute(Dispute\Entity $dispute, array $input) : Entity
    {
        $this->trace->info(TraceCode::DISPUTE_EVIDENCE_CREATE_FOR_DISPUTE_INPUT, $input);

        (new Validator)->validateInput('create_for_dispute', $input);

        (new Document\Core)->createMany($dispute, $input);

        $createInput = $this->getInputForCreateForDispute($dispute, $input);

        $this->validateInputForCreateForDispute($dispute, $createInput);

        $entity = (new Entity)->build($createInput);

        $this->repo->dispute_evidence->saveOrFail($entity);

        $entity->dispute()->associate($dispute);

        return $entity;
    }

    protected function updateForDispute(Dispute\Entity $dispute, array $updateInput): Entity
    {
        $this->trace->info(TraceCode::DISPUTE_EVIDENCE_UPDATE_FOR_DISPUTE_INPUT, $updateInput);

        $createInput = $this->getInputForCreateForDisputeFromUpdateRequest($dispute, $updateInput);

        $this->repo->dispute_evidence_document->deleteDocumentsForDispute($dispute->getId());

        $this->repo->dispute_evidence->deleteOrFail($dispute->evidence()->firstOrFail());

        $result = $this->createForDispute($dispute, $createInput);

        return $result;
    }

    protected function validateInputForCreateForDispute(Dispute\Entity $dispute, array $createInput)
    {
        $validator = (new Validator);

        $validator->validateInput('create', $createInput);

        $validator->validateAmount($dispute, $createInput);

        $validator->validateActionForDisputeStatus($dispute, $createInput[Constants::ACTION]);
    }

    protected function getInputForCreateForDispute(Dispute\Entity $dispute, $input): array
    {
        return [
            Entity::DISPUTE_ID => $dispute->getId() ?? '',
            Entity::SUMMARY    => $input[Entity::SUMMARY] ?? '',
            Entity::AMOUNT     => $input[Entity::AMOUNT] ?? $dispute->getAmount(),
            Entity::CURRENCY   => $dispute->getCurrency(),
            Entity::SOURCE     => $this->getSourceForCreateEvidence(),
            Constants::ACTION  => $input[Constants::ACTION] ?? Action::DRAFT,
        ];
    }

    public function getSourceForCreateEvidence(): string
    {
        if ($this->app['basicauth']->isPrivateAuth() === true)
        {
            return Source::PRIVATE_AUTH;
        }

        return Source::UNKNOWN;
    }

    protected function getHandlerForPatchDisputeEvidence(Dispute\Entity $dispute, array $input): string
    {
        //todo handle inputs based on action as well[action can be "submit" later on..]
        if ($dispute->evidence()->first() === null)
        {
            return self::PATCH_DISPUTE_EVIDENCE_CREATE_HANDLER;
        }

        return self::PATCH_DISPUTE_EVIDENCE_UPDATE_HANDLER;
    }

    protected function getInputForCreateForDisputeFromUpdateRequest(Dispute\Entity $dispute, array $newInput)
    {
        if ($dispute->evidence()->first() === null)
        {
            return $newInput;
        }

        $inputFromCurrentEvidence = $dispute->evidence()->first()->toArrayPublic();

        $inputFromCurrentEvidence = array_filter($inputFromCurrentEvidence, function($key)
        {
            return in_array($key, Constants::IGNORE_FIELDS_FOR_UPDATE_REQUEST, true) === false;
        }, ARRAY_FILTER_USE_KEY);

        $result = [];

        foreach ($inputFromCurrentEvidence as $key => $currentValue)
        {
            //1. key is not present in new input => prefer existing value
            if ((array_key_exists($key, $newInput) === false) and
                (empty($currentValue) === false))
            {
                $result[$key] = $currentValue;

                continue;
            }

            if (empty($newInput[$key]) === true)
            {
                continue;
            }


            if ($key === Dispute\Evidence\Document\Types::OTHERS)
            {
                $result[$key] = $this->mergeOthersTypeProofForUpdateRequest($currentValue, $newInput[Dispute\Evidence\Document\Types::OTHERS]);
            }
            else
            {
                $result[$key] = $newInput[$key];
            }
        }

        return $result;
    }

    protected function mergeOthersTypeProofForUpdateRequest($currentOthersProof, $newOthersProof)
    {
        if ((empty($newOthersProof) === true) or
            (is_sequential_array($newOthersProof) === false))

        {
            return [];
        }

        $customProofTypeDocumentIdsMap = $this->fillCustomProofTypeDocumentIdsMap($currentOthersProof);

        $customProofTypeDocumentIdsMap = $this->fillCustomProofTypeDocumentIdsMap($newOthersProof, $customProofTypeDocumentIdsMap);

        $result = [];

        foreach ($customProofTypeDocumentIdsMap as $customProofType => $documentIds)
        {
            array_push($result, [
                Document\Entity::TYPE   => $customProofType,
                Constants::DOCUMENT_IDS => $documentIds,
            ]);
        }

        return $result;
    }

    protected function fillCustomProofTypeDocumentIdsMap($othersProof, $customProofTypeDocumentIdsMap = []): array
    {
        foreach ($othersProof as $proof)
        {
            $customType = $proof[Document\Entity::TYPE];

            $documentIds = $proof[Constants::DOCUMENT_IDS];

            $customProofTypeDocumentIdsMap[$customType] = $documentIds;
        }

        return $customProofTypeDocumentIdsMap;
    }
}