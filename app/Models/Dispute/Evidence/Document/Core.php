<?php


namespace RZP\Models\Dispute\Evidence\Document;


use RZP\Models\Base;
use RZP\Models\Dispute;
use RZP\Trace\TraceCode;
use RZP\Constants\Table;

class Core extends Base\Core
{

    public function createMany(Dispute\Entity $dispute, array $merchantInput, $allowEmpty = false)
    {
        $createManyInput = $this->makeInputForCreateMany($merchantInput);

        $this->app->trace->info(TraceCode::EVIDENCE_DOCUMENT_CREATE_MANY_INPUT, $createManyInput);

        (new Validator)->validateCreateManyInput($createManyInput, $allowEmpty);

        $bulkCreateInput = $this->makeBulkCreateInputFromCreateManyInput($dispute, $createManyInput);

        (new Validator)->validateBulkCreateInput($bulkCreateInput, $allowEmpty);

        $documents = $this->repo->dispute_evidence_document->transaction(function () use ($dispute, $bulkCreateInput)
        {
            $documents = [];
            foreach ($bulkCreateInput as $row)
            {
                $documents[] = ($this->create($dispute, $row, true)->toDualWriteArray());
            }

            $this->app['disputes']->sendDualWriteToDisputesService([Dispute\Constants::EVIDENCE_DOCUMENTS => $documents], Table::DISPUTE_EVIDENCE_DOCUMENT, Dispute\Constants::CREATE);
            return $documents;
        });
    }

    protected function create(Dispute\Entity $dispute, $input, $bulk = false): Entity
    {
        $this->trace->info(TraceCode::EVIDENCE_DOCUMENT_CREATE_INPUT, $input);

        $document = (new Entity)->build($input);

        if($bulk === true)
        {
            $this->repo->dispute_evidence_document->saveOrFail($document);
            $document->refresh();
            return $document;
        }

        return $this->repo->transaction(function() use ($document){
            $this->repo->dispute_evidence_document->saveOrFail($document);

            $document->refresh();

            $this->app['disputes']->sendDualWriteToDisputesService([Dispute\Constants::EVIDENCE_DOCUMENTS => $document->toDualWriteArray()], Table::DISPUTE_EVIDENCE_DOCUMENT, Dispute\Constants::CREATE);

            return $document;
        });

    }

    protected function makeBulkCreateInputFromCreateManyInput(Dispute\Entity $dispute, array $createManyInput)
    {
        $result = [];

        foreach ($createManyInput as $proofType => $proof)
        {
            $inputs = $this->makeInputForCreate($dispute, $proofType, $proof);

            $result = array_merge($result, $inputs);
        }

        return $result;
    }

    protected function makeInputForCreateMany($input)
    {
        $result = array_filter($input, function ($attribute)
        {
            return Types::isValidType($attribute) === true;
        }, ARRAY_FILTER_USE_KEY);

        return $result;
    }

    protected function makeInputForCreate(Dispute\Entity $dispute, $proofType, $proof)
    {
        switch ($proofType)
        {
            case Types::OTHERS:
                return $this->makeInputForCreateOthersType($dispute, $proofType, $proof);
            default:
                return $this->makeInputForCreateDefaultType($dispute, $proofType, $proof);
        }
    }

    protected function makeInputForCreateOthersType(Dispute\Entity $dispute, $proofType, $othersTypeProofs)
    {
        $result = [];

        foreach ($othersTypeProofs as $othersTypeProof)
        {
            foreach ($othersTypeProof['document_ids'] as $documentId)
            {
                array_push($result, [
                    Entity::DISPUTE_ID  => $dispute->getId(),
                    Entity::TYPE        => $proofType,
                    Entity::CUSTOM_TYPE => $othersTypeProof[Entity::TYPE],
                    Entity::DOCUMENT_ID => Entity::stripDefaultSign($documentId),
                    Entity::SOURCE      => (new Dispute\Evidence\Core)->getSourceForCreateEvidence(),
                ]);
            }
        }

        return $result;
    }

    protected function makeInputForCreateDefaultType(Dispute\Entity $dispute, $proofType, $documentIds)
    {
        $result = [];

        foreach ($documentIds as $documentId)
        {
            array_push($result, [
                Entity::DISPUTE_ID  => $dispute->getId(),
                Entity::TYPE        => $proofType,
                Entity::DOCUMENT_ID => Entity::stripDefaultSign($documentId),
                Entity::SOURCE      => (new Dispute\Evidence\Core)->getSourceForCreateEvidence(),
            ]);
        }

        return $result;
    }
}
