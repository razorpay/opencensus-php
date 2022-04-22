<?php

namespace RZP\Models\Dispute\DebitNote;

use RZP\Models\Base;
use RZP\Models\Dispute;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use mikehaertl\wkhtmlto\Pdf;
use RZP\Models\PaperMandate\HyperVerge;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use RZP\Models\Merchant\FreshdeskTicket\Constants as FreshdeskConstants;

class Core extends Base\Core
{


    public function postDebitNote(array $input)
    {
        $input[Entity::BASE_AMOUNT] = $this->getBaseAmountAggregate($input[Constants::PAYMENT_IDS]);

        $this->validateDebitNoteCreate($input);

        $entity = (new Entity)->build($input);


        $this->repo->transaction(function () use ($input, $entity)
        {
            $this->repo->debit_note->saveOrFail($entity);

            $entity->reload();

            foreach ($input[Constants::PAYMENT_IDS] as $paymentId)
            {
                (new Detail\Service)->create([
                    Detail\Entity::DEBIT_NOTE_ID => $entity->getId(),
                    Detail\Entity::DETAIL_TYPE   => Detail\Type::DISPUTE,
                    Detail\Entity::DETAIL_ID     => $this->getEligibleDisputeForPayment($paymentId, $entity->getMerchantId())->getId(),
                ]);
            }

            $this->sendCommunications($entity);
        });


        $this->trace->info(TraceCode::DEBIT_NOTE_CREATED, $entity->toArray());

        return $entity;
    }


    protected function getBaseAmountAggregate(array $paymentIds)
    {
        Payment\Entity::verifyIdAndSilentlyStripSignMultiple($paymentIds);

        return (new Payment\Repository())->newQueryOnSlave()
            ->whereIn(Payment\Entity::ID, $paymentIds)
            ->sum(Payment\Entity::BASE_AMOUNT);
    }

    protected function validateDebitNoteCreate(array $input)
    {
        $validator = new Validator;

        $validator->validateInput('create', $input);

    }

    protected function getEligibleDisputeForPayment(string $paymentId, string $merchantId)
    {
        return (new Dispute\Repository)->fetch([
            Dispute\Entity::PAYMENT_ID => $paymentId,
        ], $merchantId)->firstOrFail();
    }

    protected function sendCommunications(Entity $debitNote)
    {
        $merchant = $debitNote->merchant;

        $isEligibleForMobileSignUp = (new Merchant\RiskMobileSignupHelper())::isEligibleForMobileSignUp($merchant);

        $action = Merchant\Action::DEBIT_NOTE_CREATE_EMAIL_SIGNUP;

        if ($isEligibleForMobileSignUp === true)
        {
            $action = Merchant\Action::DEBIT_NOTE_CREATE_MOBILE_SIGNUP;
        }

        $communicationData = $this->getCommunicationData($debitNote);

        $pdfPath = $this->generateDebitNotePdf($debitNote, $communicationData);

        (new Merchant\MerchantActionNotification)->sendMerchantRiskActionNotifications($merchant,
            $action,
            $communicationData,
            [
                'type'         => 'Incident',
                'tags'         => ['bulk_debit_note', 'chargeback_debit_note'],
                'status'       => 2,
                'category'     => FreshdeskConstants::CHARGEBACKS_CATEGORY,
                'sub_category' => FreshdeskConstants::SERVICE_CHARGEBACK_SUBCATEGORY,
                'attachments'  => [
                    new UploadedFile($pdfPath, $debitNote->getPublicId() . '.pdf'),
                ],
            ]);

        unlink($pdfPath); // nosemgrep : php.lang.security.unlink-use.unlink-use

    }

    protected function getCommunicationData(Entity $debitNote): array
    {
        $baseAmountInRupees = (float)$debitNote->getBaseAmount() / 100;

        $debitNoteDataTable = [];

        foreach ($debitNote->disputes as $dispute)
        {
            $debitNoteDataTable[] = [
                'payment_id' => $dispute->getPaymentId(),
                'amount'     => $dispute->getBaseAmount() / 100, // convert to rupees from paise for purpose of display
            ];
        }

        return [
            'baseAmount'         => $baseAmountInRupees,
            'baseAmountWords'    => (new HyperVerge())->getAmountInWords($baseAmountInRupees * 100),
            'debitNoteDataTable' => $debitNoteDataTable,
            'template_namespace' => 'payments_risk',
            'serial'             => $this->generateDebitNoteSerial($debitNote),
        ];
    }

    protected function generateDebitNotePdf(Entity $debitNote, $communicationData)
    {
        $html = view('emails.merchant.risk.debit_note.created', $communicationData);

        $pdf = (new Pdf);

        $pdf->addPage($html);

        $pdfContent = $pdf->toString();

        $path = sprintf(Constants::DEBIT_NOTE_PDF_PATH, $debitNote->getId());

        $fd = fopen($path, 'w');

        fwrite($fd, $pdfContent);

        fclose($fd);

        return $path;
    }

    protected function generateDebitNoteSerial(Entity $entity)
    {
        $date = date(Constants::DEBIT_NOTE_SERIAL_NUMBER_DATE_FORMAT);

        $key = sprintf(Constants::DEBIT_NOTE_SERIAL_NUMBER_KEY, $date);

        $incr = $this->app['redis']->incr($key);

        return sprintf(Constants::DEBIT_NOTE_SERIAL_NUMBER_FORMAT, $date, $incr, $entity->getPublicId());
    }
}
