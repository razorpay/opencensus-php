<?php

namespace RZP\Jobs;

use App;
use Carbon\Carbon;
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

            $this->createLegalDocumentsIfApplicable($this->input, $merchant, $this->milestone);

            $this->delete();
        }
        catch (\Throwable $e)
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

            $this->trace->info(TraceCode::CREATE_MERCHANT_CONSENTS, [
                'message' => 'Consents are not present.',
                'input'   => $input
            ]);

            $detailService->storeConsents($merchantId, $input, $merchant->primaryOwner()->getId());

            $documents_detail = $detailService->getDocumentsDetails($input);

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
