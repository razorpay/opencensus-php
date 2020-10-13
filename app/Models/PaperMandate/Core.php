<?php

namespace RZP\Models\PaperMandate;

use Storage;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Models\Terminal;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\BankAccount;
use RZP\Exception\LogicException;
use RZP\Exception\BadRequestException;
use RZP\Models\SubscriptionRegistration\Metric;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class Core extends Base\Core
{
    public function create(array $input, Customer\Entity $customer): Entity
    {
        $traceInput = $input;
        unset($traceInput[Entity::BANK_ACCOUNT]);

        $this->trace->info(TraceCode::PAPER_MANDATE_CREATE_REQUEST,
            [
                'input'       => $traceInput,
                'customer_id' => $customer->getId()
            ]);

        $paperMandate = (new Entity)->generateId();

        $paperMandate->merchant()->associate($this->merchant);

        $paperMandate->customer()->associate($customer);

        $this->setDefaultValuesForPaperMandate($paperMandate);

        $this->setTerminalDataForPaperMandate($paperMandate);

        $paperMandate->build($input);

        $bankAccount = $this->createBankAccount($input[Entity::BANK_ACCOUNT], $customer);

        $paperMandate->getValidator()->validateBankAccount($bankAccount);

        $paperMandate->bankAccount()->associate($bankAccount);

        $this->repo->saveOrFail($paperMandate);

        $this->repo->loadRelations($paperMandate);

        $this->trace->info(TraceCode::PAPER_MANDATE_CREATED,
            [
                'paper_mandate_id' => $paperMandate->getId(),
            ]);

        $this->trace->count(Metric::AUTH_LINK_PAPER_NACH_CREATED, ['mode' => $this->mode]);

        return $paperMandate;
    }

    public function authenticate(Entity $paperMandate, array $input): PaperMandateUpload\Entity
    {
        $this->trace->info(
            TraceCode::PAPER_MANDATE_AUTHENTICATE_REQUEST,
            [
                'paper_mandate_id' => $paperMandate->getId(),
            ]
        );

        if (empty($input[Entity::PAPER_MANDATE_UPLOAD_ID]) === false)
        {
            $paperMandateUpload = $this->repo->paper_mandate_upload->findByPublicIdAndMerchant(
                $input[Entity::PAPER_MANDATE_UPLOAD_ID],
                $this->merchant);

            if ($paperMandateUpload->paperMandate->getId() !== $paperMandate->getId())
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
            }
        }
        else
        {
            $paperMandateUpload = (new PaperMandateUpload\Core)->create($input, $paperMandate);
        }

        $uploadedFileId = $paperMandateUpload->getEnhancedFileId();

        if ($paperMandateUpload->getStatus() === PaperMandateUpload\Status::ACCEPTED)
        {
            $paperMandate->setUploadedFileId($uploadedFileId);

            $paperMandate->saveOrFail();
        }

        return $paperMandateUpload;
    }

    public function validate(Entity $paperMandate, array $input): PaperMandateUpload\Entity
    {
        $this->trace->info(
            TraceCode::PAPER_MANDATE_VALIDATE_REQUEST,
            [
                'paper_mandate_id' => $paperMandate->getId(),
            ]
        );

        return (new PaperMandateUpload\Core)->create($input, $paperMandate);
    }

    public function uploadNachFormForPayment(Entity $paperMandate, array $input): PaperMandateUpload\Entity
    {
        $this->trace->info(
            TraceCode::PAPER_MANDATE_FILE_UPLOAD_REQUEST,
            [
                'paper_mandate_id' => $paperMandate->getId(),
            ]
        );

        $paperMandateUpload = (new PaperMandateUpload\Core)->createForPayment($input, $paperMandate);

        $uploadedFileId = $paperMandateUpload->getEnhancedFileId();

        if ($paperMandateUpload->getStatus() === PaperMandateUpload\Status::ACCEPTED)
        {
            $paperMandate->setUploadedFileId($uploadedFileId);

            $paperMandate->saveOrFail();
        }

        return $paperMandateUpload;
    }

    protected function setDefaultValuesForPaperMandate(Entity $paperMandate)
    {
        $startAtAfter = '+' . Constants::PAPER_MANDATE_START_AFTER_DAYS . ' days';

        $startAt = (new Carbon($startAtAfter))->setTime(0, 0, 0, 0);

        $paperMandate->setStartAt($startAt->timestamp);
    }

    protected function setTerminalDataForPaperMandate(Entity $paperMandate)
    {
        $terminal = $this->getTerminalForNachMethod();

        if ($terminal === null)
        {
            throw new LogicException(
                'terminal selected can\'t be null'
            );
        }

        $paperMandate->setTerminalId($terminal->getId());

        if (($this->mode !== Mode::LIVE) and
            ($terminal->getId() === Terminal\Shared::SHARP_RAZORPAY_TERMINAL))
        {
            $paperMandate->setUtilityCode('NACH00000000010000');

            $paperMandate->setSponsorBankCode('RANDOMBANK');
        }
        else
        {
            $paperMandate->setUtilityCode($terminal->getGatewayMerchantId2());

            $paperMandate->setSponsorBankCode($terminal->getGatewayAccessCode());
        }
    }

    protected function getTerminalForNachMethod()
    {
        $paymentArray = (new Payment\Entity)->getDummyPaymentArray(Payment\Method::NACH);

        $paymentProcessor = new PaymentProcessor($this->merchant);

        return $paymentProcessor->processAndReturnTerminal($paymentArray);
    }

    public function generateMandateForm(Entity $paperMandate)
    {
        $data = (new HyperVerge)->generatePaperMandateForm($paperMandate);

        $generatedFileId = (new FileUploader($paperMandate))->saveCreatedMandateAndFileId($data[Entity::GENERATED_IMAGE]);

        $this->trace->info(
            TraceCode::PAPER_MANDATE_FORM_GENERATED,
            [
                Entity::ID                => $paperMandate->getPublicId(),
                Entity::GENERATED_FILE_ID => $generatedFileId,
            ]);

        $paperMandate->setGeneratedFileId($generatedFileId);

        $paperMandate->setFormChecksum($data[Entity::FORM_CHECKSUM]);

        $paperMandate->saveOrFail();
    }

    protected function createBankAccount(array $bankAccountInput, Customer\Entity $customer): BankAccount\Entity
    {
        $this->setDefaultValuesForBank($bankAccountInput, $customer);

        $bankAccountCore = new BankAccount\Core();

        $bankAccount = $bankAccountCore->addOrUpdateBankAccountForCustomer($bankAccountInput, $customer);

        return $bankAccount;
    }

    protected function setDefaultValuesForBank(array & $bankInput, Customer\Entity $customer)
    {
        if ((array_key_exists(BankAccount\Entity::BENEFICIARY_EMAIL, $bankInput) == false) and
            (empty($customer->getEmail()) === false))
        {
            $bankInput[BankAccount\Entity::BENEFICIARY_EMAIL] = $customer->getEmail();
        }

        if ((array_key_exists(BankAccount\Entity::BENEFICIARY_MOBILE, $bankInput) == false) and
            (empty($customer->getContact()) === false))
        {
            $bankInput[BankAccount\Entity::BENEFICIARY_MOBILE] = $customer->getContact();
        }

        if (isset($bankInput[BankAccount\Entity::ACCOUNT_TYPE]) === false)
        {
            $bankInput[BankAccount\Entity::ACCOUNT_TYPE] = BankAccount\AccountType::SAVINGS;
        }
    }
}
