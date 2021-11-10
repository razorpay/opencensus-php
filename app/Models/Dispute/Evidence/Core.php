<?php


namespace RZP\Models\Dispute\Evidence;

use Mail;
use Exception;
use RZP\Models\Base;
use RZP\Models\Dispute;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Method;
use RZP\Models\Dispute\RecoveryMethod;
use RZP\Mail\Dispute\Admin\DisputePresentmentRiskOpsReview;

class Core extends Base\Core
{

    const EVIDENCE_CREATE_HANDLER = "createForDispute";
    const EVIDENCE_UPDATE_HANDLER = "updateForDispute";
    const EVIDENCE_ACCEPT_HANDLER = "acceptForDispute";
    const EVIDENCE_SUBMIT_HANDLER = "submitEvidenceHandler";


    public function handlePatchDisputeEvidence(Dispute\Entity $dispute, array $input): Entity
    {
        $handler = $this->getHandlerForPatchDisputeEvidence($dispute, $input);

        $this->trace->info(TraceCode::DISPUTE_PRESENTMENT_HANDLE_PATCH_EVIDENCE_INPUT, [
            'input'   => $input,
            'handler' => $handler,
        ]);

        $result = null;

        switch ($handler)
        {
            case self::EVIDENCE_CREATE_HANDLER:
                $result = $this->createForDispute($dispute, $input);
                break;
            case self::EVIDENCE_UPDATE_HANDLER:
                $result = $this->updateForDispute($dispute, $input);
                break;
            case self::EVIDENCE_ACCEPT_HANDLER:
                $result = $this->acceptForDispute($dispute, $input);
                break;
            case self::EVIDENCE_SUBMIT_HANDLER:
                $result = $this->submitEvidenceForDispute($dispute, $input);
                break;
        }

        $dispute->refresh();

        return $result;
    }

    protected function getHandlerForPatchDisputeEvidence(Dispute\Entity $dispute, array $input): string
    {
        $action = $input[Constants::ACTION] ?? Action::DRAFT;

        if ($action === Action::ACCEPT)
        {
            return self::EVIDENCE_ACCEPT_HANDLER;
        }

        if ($action === Action::SUBMIT)
        {
            return self::EVIDENCE_SUBMIT_HANDLER;
        }

        if ($dispute->evidence()->first() === null)
        {
            return self::EVIDENCE_CREATE_HANDLER;
        }

        return self::EVIDENCE_UPDATE_HANDLER;
    }

    public function createForDispute(Dispute\Entity $dispute, array $input): Entity
    {
        $this->trace->info(TraceCode::DISPUTE_EVIDENCE_CREATE_FOR_DISPUTE_INPUT, $input);

        (new Validator)->validateInput('create_for_dispute', $input);

        $allowEmpty = $this->canAllowEmptyDocumentEvidence($input);

        (new Document\Core)->createMany($dispute, $input, $allowEmpty);

        return $this->createAndSaveEvidenceForDispute($dispute, $input);
    }

    protected function createAndSaveEvidenceForDispute(Dispute\Entity $dispute, array $input): Entity
    {
        $createInput = $this->getInputForCreateForDispute($dispute, $input);

        $this->validateInputForCreateForDispute($dispute, $createInput);

        $entity = (new Entity)->build($createInput);

        $this->repo->dispute_evidence->saveOrFail($entity);

        $entity->dispute()->associate($dispute);

        return $entity;
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

    protected function validateInputForCreateForDispute(Dispute\Entity $dispute, array $createInput)
    {
        $validator = (new Validator);

        $validator->validateInput('create', $createInput);

        $validator->validateAmount($dispute, $createInput);

        $validator->validateActionForDisputeStatus($dispute, $createInput[Constants::ACTION]);

        $validator->validateDisputeHasNotExpired($dispute);
    }

    protected function updateForDispute(Dispute\Entity $dispute, array $updateInput): Entity
    {
        $this->trace->info(TraceCode::DISPUTE_EVIDENCE_UPDATE_FOR_DISPUTE_INPUT, $updateInput);

        $createInput = $this->getInputForCreateForDisputeFromUpdateRequest($dispute, $updateInput);

        $this->purgeDisputeEvidence($dispute);

        return $this->createForDispute($dispute, $createInput);
    }

    protected function getInputForCreateForDisputeFromUpdateRequest(Dispute\Entity $dispute, array $newInput)
    {
        if ($dispute->evidence()->first() === null)
        {
            return $newInput;
        }

        $inputFromCurrentEvidence = $dispute->evidence()->first()->toArrayPublic();

        $inputFromCurrentEvidence = array_filter($inputFromCurrentEvidence, function ($key)
        {
            return in_array($key, Constants::IGNORE_FIELDS_FOR_UPDATE_REQUEST, true) === false;
        }, ARRAY_FILTER_USE_KEY);

        $result = [];

        foreach ($inputFromCurrentEvidence as $key => $currentValue)
        {
            //1. key is not present in new input => prefer existing value
            if ((array_key_exists($key, $newInput) === false) and
                (empty($currentValue) === false)
            )
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

        $result[Constants::ACTION] = $newInput[Constants::ACTION] ?? Action::DRAFT;

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
        if (empty($othersProof) === true)
        {
            return $customProofTypeDocumentIdsMap;
        }

        foreach ($othersProof as $proof)
        {
            $customType = $proof[Document\Entity::TYPE];

            $documentIds = $proof[Constants::DOCUMENT_IDS];

            $customProofTypeDocumentIdsMap[$customType] = $documentIds;
        }

        return $customProofTypeDocumentIdsMap;
    }

    protected function purgeDisputeEvidence(Dispute\Entity $dispute): void
    {
        $this->repo->dispute_evidence_document->deleteDocumentsForDispute($dispute->getId());

        if ($dispute->evidence()->first() === null)
        {
            return;
        }

        $this->repo->dispute_evidence->deleteOrFail($dispute->evidence()->firstOrFail());
    }

    protected function acceptForDispute(Dispute\Entity $dispute, array $input)
    {
        $acceptDisputeInput = $this->makeInputForAcceptDispute($dispute);

        $this->purgeDisputeEvidence($dispute);

        $result = $this->createAndSaveEvidenceForDispute($dispute, $acceptDisputeInput);

        $this->recoverAmountFromMerchantOnDisputeAccept($dispute);


        return $result;
    }

    protected function makeInputForAcceptDispute(Dispute\Entity $dispute)
    {
        return [
            Entity::SUMMARY   => 'dispute accepted',
            Entity::AMOUNT    => 0, // if merchant accepts dispute, then evidence is for 0 amount, because they are not denying the contest
            Constants::ACTION => Action::ACCEPT,
        ];
    }

    /**
     * @param Dispute\Entity $dispute
     * Adding the functionality here and not in Dispute/Core. Reason: the recovery flow is applicable only to dispute presentment flows
     * and not to all disputes in general.
     * When it becomes applicable to all disputes, the code can be moved into Dispute/Core
     */
    protected function recoverAmountFromMerchantOnDisputeAccept(Dispute\Entity $dispute)
    {
        $recoveryOption = (new Dispute\Core)->getRecoveryMethodForDisputeAccept($dispute);

        $traceData = [
            'recovery_method' => $recoveryOption,
            'dispute_id'      => $dispute->getId(),
        ];

        $this->trace->info(TraceCode::DISPUTE_RECOVER_FROM_MERCHANT_ON_ACCEPT_INITIATE, $traceData);

        try
        {
            $this->repo->transaction(function () use ($dispute, $recoveryOption)
            {
                switch ($recoveryOption)
                {
                    case RecoveryMethod::ADJUSTMENT:
                        $this->recoverAmountFromMerchantOnDisputeAcceptViaAdjustment($dispute);
                        break;
                    case RecoveryMethod::REFUND:
                        $this->recoverAmountFromMerchantOnDisputeAcceptViaRefund($dispute);
                        break;
                    case RecoveryMethod::RISK_OPS_REVIEW:
                        $this->recoverAmountFromMerchantOnDisputeAcceptViaRiskOpsReview($dispute);
                        break;
                }
            });

            $success = true;
        }
        catch (Exception $exception)
        {
            $this->trace->traceException($exception);

            $success = false;

            $traceData['exception'] = [
                'message' => $exception->getMessage(),
            ];

            $this->recoverAmountFromMerchantOnDisputeAcceptViaRiskOpsReview(
                $dispute,
                Constants::RISK_OPS_REVIEW_REASON_ADJUSTMENT_OR_REFUND_CREATION_FAILED,
                $exception->getMessage());
        }

        $traceData['success'] = $success;


        $this->trace->info(TraceCode::DISPUTE_RECOVER_FROM_MERCHANT_ON_ACCEPT_COMPLETE, $traceData);
    }

    protected function recoverAmountFromMerchantOnDisputeAcceptViaAdjustment(Dispute\Entity $dispute)
    {
        (new Dispute\Core)->update($dispute, [
            Dispute\Entity::BACKFILL        => false,
            Dispute\Entity::STATUS          => Dispute\Status::LOST,
            Dispute\Entity::INTERNAL_STATUS => Dispute\InternalStatus::LOST_MERCHANT_DEBITED,
        ]);
    }

    protected function recoverAmountFromMerchantOnDisputeAcceptViaRefund(Dispute\Entity $dispute)
    {
        $payment = $this->repo->payment->findOrFail($dispute->getPaymentId());

        // need to explicitly set and save here because if a payment is already in disputed state
        // we dont allow refunds on it. in case of an error, this is in a txn block and the txn will be rolled back
        $payment->setDisputed(false);

        $this->repo->payment->saveOrFail($payment);

        (new Dispute\Core)->createRefundAndUpdateDispute($dispute);

        (new Dispute\Core)->update($dispute, [
            Dispute\Entity::BACKFILL       => false,
            Dispute\Entity::STATUS         => Dispute\Status::LOST,
            Dispute\Entity::SKIP_DEDUCTION => true,
            Dispute\Entity::INTERNAL_STATUS=> Dispute\InternalStatus::LOST_MERCHANT_DEBITED,
        ]);
    }

    protected function recoverAmountFromMerchantOnDisputeAcceptViaRiskOpsReview(Dispute\Entity $dispute,
                                                                                $reasonForReview = Constants::RISK_OPS_REVIEW_REASON_BEHAVIOR_NOT_SPECIFIED,
                                                                                $reviewMessage = '')
    {
        $dispute->reload();

        if ($dispute->isLost() === false)
        {
            (new Dispute\Core)->update($dispute, [
                Dispute\Entity::BACKFILL        => false,
                Dispute\Entity::SKIP_DEDUCTION  => true,
                Dispute\Entity::STATUS          => Dispute\Status::LOST,
                Dispute\Entity::INTERNAL_STATUS => Dispute\InternalStatus::LOST_MERCHANT_NOT_DEBITED,
            ]);
        }


        $data = (new Dispute\Core)->getSendDisputeMailToAdminData($dispute);

        $data['reason_for_review'] = $reasonForReview;

        $data['review_message'] = $reviewMessage;

        $mail = (new DisputePresentmentRiskOpsReview($data))->build();

        Mail::queue($mail);
    }

    protected function submitEvidenceForDispute(Dispute\Entity $dispute, array $input)
    {
        $result = $this->updateForDispute($dispute, $input);

        (new Dispute\Core)->update($dispute, [
            Dispute\Entity::STATUS   => Dispute\Status::UNDER_REVIEW,
            Dispute\Entity::BACKFILL => false,
        ]);

        return $result;
    }

    protected function canAllowEmptyDocumentEvidence($input): bool
    {
        $action = $input[Constants::ACTION] ?? Action::DRAFT;

        return ($action === Action::DRAFT);
    }

}