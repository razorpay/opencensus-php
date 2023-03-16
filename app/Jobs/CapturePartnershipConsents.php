<?php

namespace RZP\Jobs;

use App;
use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Entity;
use RZP\Models\Merchant\Constants;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Consent as Consent;
use RZP\Models\Merchant\Detail\Constants as DEConstants;
use RZP\Models\Merchant\Detail\Entity as MerchantDetail;
use RZP\Models\Merchant\Consent\Processor\Factory as ProcessorFactory;

class CapturePartnershipConsents extends Job
{
    const RETRY_INTERVAL = 300;

    const MAX_RETRY_ATTEMPT = 1;

    const MUTEX_KEY_PREFIX = 'capture_consent';

    /**
     * @var string
     */
    protected $queueConfigKey = 'commission';

    protected $metricsEnabled = true;

    protected $merchantId;

    protected $input;

    protected $milestone;


    public function __construct($mode, array $input,  string $merchantId, string $milestone)
    {
        parent::__construct($mode);

        $this->merchantId  = $merchantId;
        $this->input       = $input;
        $this->milestone   = $milestone;
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(
            TraceCode::CAPTURE_CONSENT_ASYNC_JOB,
            [
                'merchant_id'  => $this->merchantId,
                'milestone'    => $this->milestone,
            ]
        );

        try
        {
            $merchant  = $this->repoManager->merchant->findOrFailPublic($this->merchantId);

            $this->mutex->acquireAndRelease(
                self::MUTEX_KEY_PREFIX.$this->milestone.$this->merchantId,
                function() use($merchant){
                    $this->createLegalDocumentsIfApplicable($this->input, $merchant, $this->milestone);
                },
                Constants::MERCHANT_MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_MERCHANT_EDIT_OPERATION_IN_PROGRESS,
                Constants::MERCHANT_MUTEX_RETRY_COUNT);

            $this->delete();
        }
        catch (\Throwable $e)
        {
            if($e->getCode() === ErrorCode::BAD_REQUEST_MERCHANT_EDIT_OPERATION_IN_PROGRESS)
            {
                 $this->delete();
            }
            else
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::CAPTURE_CONSENT_ERROR,
                    [
                        'merchant_id'  => $this->merchantId,
                        'milestone'    => $this->milestone,
                    ]
                );

                $this->checkRetry($e);
            }
        }
    }

    protected function createLegalDocumentsIfApplicable(array $input, Entity $merchant, string $milestone = Constants::PARTNERSHIP)
    {
        $merchantId   = $merchant->getId();
        $activationFormMilestone = $input[MerchantDetail::ACTIVATION_FORM_MILESTONE] = $milestone;

        $detailService = new Merchant\Detail\Service();

        //if legal documents are not present already, store them in database
        if($detailService->checkIfConsentsPresent($merchantId, [$activationFormMilestone.'_'.Constants::TERMS]) === false)
        {
            $input[Consent\Entity::ENTITY_ID]   =  null;
            $input[Consent\Entity::ENTITY_TYPE] =  null;
            $userId = $input[DEConstants::USER_ID] ?? $merchant->primaryOwner()->getId();

            $this->trace->info(TraceCode::CREATE_MERCHANT_CONSENTS, [
                'message' => 'Consents are not present.',
                'input'   => $input
            ]);

            $detailService->storeConsents($merchantId, $input, $userId);

            $data = $detailService->getDocumentsDetails($input);

            $documents_detail = [];
            foreach ($data as $document_detail)
            {
                $content = $document_detail['content'];
                $document_detail['content'] = str_replace('</path>', '', $content);
                array_push($documents_detail, $document_detail);
            }

            $legalDocumentsInput = [
                DEConstants::DOCUMENTS_DETAIL => $documents_detail
            ];

            $processor = (new ProcessorFactory())->getLegalDocumentProcessor();

            $processor->setMerchant($merchant);

            $response = $processor->processLegalDocuments($legalDocumentsInput);

            $responseData = $response->getResponseData();

            $documentDetailsInput = $input[DEConstants::DOCUMENTS_DETAIL];

            foreach ($documentDetailsInput as $documentDetailInput)
            {
                $type = $activationFormMilestone.'_'.$documentDetailInput['type'] ;

                $merchantConsentDetail = $this->repoManager->merchant_consents->fetchMerchantConsentDetails($merchantId, $type);

                $updateInput = [
                    'status'     => Consent\Constants::INITIATED,
                    'updated_at' => Carbon::now()->getTimestamp(),
                    'request_id' => $responseData['id']
                ];

                (new Consent\Core())->updateConsentDetails($merchantConsentDetail, $updateInput);
            }
        }
        else
        {
            $this->trace->info(TraceCode::CREATE_MERCHANT_CONSENTS, [
                'message' => 'Consents are already present.'
            ]);
        }
    }


    protected function checkRetry(\Throwable $e)
    {
        $this->countJobException($e);

        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::CAPTURE_CONSENT_ASYNC_JOB_MESSAGE_DELETE, [
                'job_attempts' => $this->attempts(),
                'message'      => 'Deleting the job after configured number of tries. Still unsuccessful.',
                'merchant_id'  => $this->merchantId,
                'milestone'    => $this->milestone,
            ]);


            $this->delete();
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }
}
