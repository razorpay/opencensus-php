<?php

namespace RZP\Jobs;

use App;
use Carbon\Carbon;
use RZP\Constants\Product;
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

    const unicodeRegex  = '/[\x00-\x08\x0B\x0C\x0E-\x1F]|\xED[\xA0-\xBF].|\xEF\xBF[\xBE\xBF]/';

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
            if ($e->getCode() === ErrorCode::BAD_REQUEST_MERCHANT_EDIT_OPERATION_IN_PROGRESS)
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
        $input[Consent\Entity::ENTITY_ID]   ??= null;   //added these because php warning is throwing exception
        $input[Consent\Entity::ENTITY_TYPE] ??= null;

        if (empty($input[DEConstants::USER_ID]) === true)
        {
            $product = ($merchant->primaryOwner() === null) ? Product::BANKING : Product::PRIMARY;
            $input[DEConstants::USER_ID] =  $merchant->primaryOwner($product)->getId();
        }

        $detailService = new Merchant\Detail\Service();

        //if legal documents are not present already, store them in database
        if ($this->checkIfConsentsPresent($merchantId, $input) === false)
        {
            $this->trace->info(TraceCode::CREATE_MERCHANT_CONSENTS, [
                'message' => 'Consents are not present.',
                'input'   => $input
            ]);

            $detailService->storeConsents($merchantId, $input, $input[DEConstants::USER_ID]);

            $data = $detailService->getDocumentsDetails($input);

            $documents_detail = [];
            foreach ($data as $document_detail)
            {
                $content = $document_detail['content'];
                $strippedContent = str_replace('</path>', '', $content);
                $document_detail['content'] = preg_replace(self::unicodeRegex, "\xEF\xBF\xBD", $strippedContent);
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

    protected function checkIfConsentsPresent(string $merchantId, array $input) : bool
    {
        $detailService = new Merchant\Detail\Service();

        $milestone = $input[MerchantDetail::ACTIVATION_FORM_MILESTONE];

        $validDocTypes = [$milestone.'_'.Constants::TERMS];

        if ($milestone === Constants::OAUTH)
        {
            $consentDetails = $this->repoManager->merchant_consents->getConsentDetailsForMerchantIdAndEntityId($merchantId, $validDocTypes, $input[Consent\Entity::ENTITY_ID], $input[Consent\Entity::ENTITY_TYPE]);

            if ($consentDetails === null)
            {
                return false;
            }

            return true;
        }

        return $detailService->checkIfConsentsPresent($merchantId, $validDocTypes);
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
