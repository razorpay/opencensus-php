<?php

namespace RZP\Models\Merchant\InternationalEnablement;

use Mail;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Constants as MerchantConstant;
use RZP\Models\Typeform;
use RZP\Models\Merchant;
use RZP\Services\Stork;
use RZP\Trace\TraceCode;
use RZP\Services\Reminders;
use Razorpay\Trace\Logger as Trace;
use RZP\Mail\Merchant\InternationalEnablement as InternationalActivationMail;

class Service extends Base\Service
{
    protected $mutex;

    /**
     * @var Reminders
     */
    protected $reminders;

    /** @var Stork $stork */
    protected $stork;

    const SHARED_MERCHANT_ID = '100000razorpay';

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->reminders = $this->app['reminders'];

        $this->stork = $this->app['stork_service'];
    }

    public function preview(): array
    {
        return $this->core()->preview();
    }

    public function get(): array
    {
        $entity = $this->core()->get();

        if (is_null($entity) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_NO_ENTRY_FOUND);
        }

        return $this->core()->convertToExternalFormat($entity);
    }

    public function draft(array $input): array
    {
        $version = 'v1';

        if (((isset($input[Constants::VERSION])) === true) and
            ($input[Constants::VERSION] === 'v2'))
        {
            $version = $input[Constants::VERSION];

            unset($input[Constants::VERSION]);
        }

        $sanitizedInput = $this->sanitizeExternalPayload($input);

        $this->trace->info(TraceCode::INTERNATIONAL_ENABLEMENT_DATA, [
            'merchant_id'     => $this->merchant->getId(),
            'data'            => $input,
            'sanitized_input' => $sanitizedInput,
        ]);

        $input = $sanitizedInput;

        $this->createReminderForRemarketing($this->merchant->getId());

        $mutexKey = sprintf(Constants::INTERNATIONAL_ENABLEMENT_LOCK_KEY, $this->merchant->getId());

        $entity = $this->mutex->acquireAndRelease(
            $mutexKey,
            function() use ($input, $version)
            {
                $this->handleRequestEnablement($input, true);

                return $this->core()->upsert($input, Detail\Constants::ACTION_DRAFT, $version);
            });

        $this->core()->deleteCancelledDocs($entity, $input);

        return $this->core()->convertToExternalFormat($entity);
    }

    public function submit(array $input): array
    {
        $version = 'v1';

        if (((isset($input[Constants::VERSION])) === true) and
            ($input[Constants::VERSION] === 'v2'))
        {
            $version = $input[Constants::VERSION];

            unset($input[Constants::VERSION]);
        }

        $sanitizedInput = $this->sanitizeExternalPayload($input);

        $this->trace->info(TraceCode::INTERNATIONAL_ENABLEMENT_DATA, [
            'merchant_id'     => $this->merchant->getId(),
            'data'            => $input,
            'sanitized_input' => $sanitizedInput,
        ]);

        $input = $sanitizedInput;

        if ($this->app['api.route']->isWorkflowExecuteOrApproveCall() === true)
        {
            $this->createWorkflowsIfApplicable($input, null, $version);

            return [];
        }

        $mutexKey = sprintf(Constants::INTERNATIONAL_ENABLEMENT_LOCK_KEY, $this->merchant->getId());

        $entity = $this->mutex->acquireAndRelease(
            $mutexKey,
            function() use ($input, $version)
            {
                $this->handleRequestEnablement($input);

                return $this->core()->upsert($input, Detail\Constants::ACTION_SUBMIT, $version);
            });

        $this->createWorkflowsIfApplicable($input, $entity, $version);

        return $this->core()->convertToExternalFormat($entity);
    }

    public function discard()
    {
        $mutexKey = sprintf(Constants::INTERNATIONAL_ENABLEMENT_LOCK_KEY, $this->merchant->getId());

        $discardedEntity = $this->mutex->acquireAndRelease(
            $mutexKey,
            function()
            {
                return $this->core()->discard();
            });

        if(is_null($discardedEntity) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_DISCARD);
        }
    }

    public function createWorkflowsIfApplicable(array $input, $ieDetail = null, $version = 'v1')
    {
        $workflowData = [];

        if (is_null($ieDetail) === false)
        {
            $mode = $this->app['rzp.mode'];

            $merchantId = $ieDetail->getMerchantId();

            $detailId = $ieDetail->getId();

            // add international enablement link
            $workflowData['detail_url'] = sprintf(Constants::IEDetailDashboardLink, $mode, $detailId);

            // add corresponding document download links

            $documentsArr = (new Document\Core)->convertDocObjectsToExternalFormat($ieDetail->documents);

            if (empty($documentsArr) === false)
            {
                $customDocumentsArr = $documentsArr[Document\Constants::OTHERS] ?? [];

                unset($documentsArr[Document\Constants::OTHERS]);

                foreach ($documentsArr as $docType => $docList)
                {
                    foreach ($docList as $idx => $docInfo)
                    {
                        $docId = Document\Entity::stripDefaultSign($docInfo[Document\Entity::ID]);

                        $downloadLinkKey = sprintf('%s_download_url_%d', $docType, $idx + 1);

                        $workflowData[$downloadLinkKey] = sprintf(
                            Constants::IEDocumentDownloadLink, $mode, $merchantId, $docId);
                    }
                }

                // repetition of the above block .. fine as since small enough
                foreach ($customDocumentsArr as $docType => $docList)
                {
                    $customDocType = sprintf('%s_(%s)', Document\Constants::OTHERS, $docType);

                    foreach ($docList as $idx => $docInfo)
                    {
                        $docId = Document\Entity::stripDefaultSign($docInfo[Document\Entity::ID]);

                        $downloadLinkKey = sprintf('%s_download_url_%d', $customDocType, $idx + 1);

                        $workflowData[$downloadLinkKey] = sprintf(
                            Constants::IEDocumentDownloadLink, $mode, $merchantId, $docId);
                    }
                }
            }
        }

        (new Typeform\Core)->processInHouseQuestionnaire($this->merchant, $workflowData, $input, $version);
    }

    private function sanitizeExternalPayload(array $input): ? array
    {
        if (empty($input) === true)
        {
            return null;
        }

        foreach ($input as $key => $value)
        {
            if (is_array($value) === true)
            {
                $input[$key] = $this->sanitizeExternalPayload($value);
            }
            else if (is_string($value) === true)
            {
                $value = trim($value);

                if ($value === "")
                {
                    $input[$key] = null;
                }
            }
        }

        return $input;
    }

    private function handleRequestEnablement(array $input, bool $runningInDraftMode = false)
    {
        // if running in draft mode then run only validations
        $products = $input[Detail\Entity::PRODUCTS] ?? null;

        try
        {
            (new Merchant\Service)->requestInternationalProduct(
                [Detail\Entity::PRODUCTS => $products], $runningInDraftMode);
        }
        catch(Exception\BadRequestException $exc)
        {
            $errors['products'][] = $exc->getError()->getDescription();
            $errors['internal_error_code'] = ErrorCode::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE;

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE,
                null,
                $errors);
        }
        catch(Exception\BadRequestValidationFailureException $exc)
        {
            $errors['products'][] = $exc->getError()->getDescription();
            $errors['internal_error_code'] = ErrorCode::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE;

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE,
                null,
                $errors);
        }
        catch(\Throwable $exc)
        {
            $this->trace->traceException(
                $exc,
                Trace::CRITICAL,
                TraceCode::INTERNATIONAL_REQUEST_ENABLEMENT_FAILED,
                [
                    'running_in_draft_mode' => $runningInDraftMode,
                    'merchant_id'           => $this->merchant->getId(),
                ]
            );

            $errors['products'][] = 'Something went wrong';
            $errors['internal_error_code'] = ErrorCode::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE;

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE,
                null,
                $errors);
        }
    }

    /**
     * This returns data required for international visibility
     * for a merchant.
     * @return array
     */
    public function getInternationalVisibilityInfo(): array
    {
        $data =  $this->core()->getInternationalEnablementDetail();

        $merchantMethods = $this->merchant->getMethods();
        $paypalEnabled = false;

        if($merchantMethods !== null &&
            empty($merchantMethods) === false &&
            isset($merchantMethods['paypal']))
        {
            $paypalEnabled = $merchantMethods['paypal'];
        }

        $data['paypal'] = $paypalEnabled;

        return $data;
    }

    protected function createReminderForRemarketing(string $merchantId)
    {
        $request = $this->getRemindersCreateReminderInput($merchantId);
        try{
            $response = $this->reminders->createReminder($request,self::SHARED_MERCHANT_ID);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::REMINDERS_RESPONSE,
                [
                    'data'        => $request,
                    'merchant_id' => $merchantId,
                ]);
        }
    }

    protected function getRemindersCreateReminderInput(string $merchantId)
    {
        $reminderData = [
            'submitted_at' => Carbon::now()->getTimestamp()
        ];


        $url = sprintf('international_enablement/reminders/%s/%s',$this->mode,$merchantId);

        $request = [
            'namespace'     => 'international_activation_reminder',
            'entity_id'     => $merchantId,
            'entity_type'   => $this->merchant->getEntityName(),
            'reminder_data' => $reminderData,
            'callback_url'  => $url,
        ];

        return $request;
    }

    public function reminderCallBack(string $merchantId)
    {

        $detailEntity = $this->repo->international_enablement_detail->getLatest($merchantId);
        $statusCode = 200;

        if ($detailEntity->isSubmitted()==true)
        {
            $statusCode = 400;
            $finalResponseBody = ['error_response'=> 1];
        }
        else{
            $this->sendNotificationsForMerchant($merchantId);
            $finalResponseBody = ['success_response'=> 1];
        }

        return ['status_code' => $statusCode, 'response_body' => $finalResponseBody];

    }

    protected function sendNotificationsForMerchant(string $merchantId)
    {

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        //SMS
        try {
            $smsPayload = [
                'ownerId'               => $merchant->getId(),
                'ownerType'             => MerchantConstant::MERCHANT,
                'templateName'          => Typeform\Constants::SMS_INTL_ENABLEMENT_REMINDER,
                'templateNamespace'     => 'payments_dashboard',
                'orgId'                 => $merchant->getOrgId(),
                'destination'           => $merchant->merchantDetail->getContactMobile(),
                'sender'                => 'RZRPAY',
                'language'              => 'english',
                'contentParams'         => [
                    'merchant_name'  => $merchant->getName(),
                    'business_name'  => $merchant->merchantDetail->getBusinessName()
                ],
            ];

            $this->stork->sendSms($this->mode,$smsPayload, false);

            $this->trace->info(TraceCode::INTERNATIONAL_ENABLEMENT_SMS_SENT,[
                "merchant_id" => $merchantId,
            ]);
        }
        catch (\Exception $e)
        {
            $this->trace->info(
                TraceCode::INTERNATIONAL_ENABLEMENT_SMS_FAILED,
                [
                "merchant_id" => $merchantId,
                "exception"   => $e,
            ]);
        }

        //mail
        try {
            $mailPayload['merchant'] = $merchant->toArray();

            $mail = new InternationalActivationMail\Reminder($mailPayload);

            Mail::send($mail);

            $this->trace->info(TraceCode::INTERNATIONAL_ENABLEMENT_EMAIL_SENT,[
                "merchant_id"=>$merchantId,
            ]);
        }
        catch (\Exception $e)
        {
            $this->trace->info(
                TraceCode::INTERNATIONAL_ENABLEMENT_EMAIL_FAILED,
                [
                "merchant_id"=>$merchantId,
                "exception"   => $e,
            ]);
        }

        //Whatsapp
        try{
            $receiver = $merchant->merchantDetail->getContactMobile();

            $template = Typeform\Constants::WHATSAPP_INTL_ENABLEMENT_REMINDER;

            $whatsAppPayLoad = [
                'ownerId'   => $merchant->getId(),
                'ownerType' => 'merchant',
                'params'    => [
                    'merchant_name'  => $merchant->getName(),
                    'business_name' => $merchant->merchantDetail->getBusinessName(),
                ],
                'is_cta_template' => true,
                'button_url_param'=> "app/payment-methods",
            ];

            $whatsAppPayLoad['template_name'] = Typeform\Constants::WHATSAPP_INTL_ENABLEMENT_REMINDER_NAME;

            $this->stork->sendWhatsappMessage($this->mode,$template,$receiver,$whatsAppPayLoad);

            $this->trace->info(TraceCode::INTERNATIONAL_ENABLEMENT_WHATSAPP_SENT,[
                "merchant_id" => $merchantId,
            ]);
        }
        catch (\Exception $e)
        {
            $this->trace->info(
                TraceCode::INTERNATIONAL_ENABLEMENT_WHATSAPP_FAILED,
                [
                "merchant_id" => $merchantId,
                "exception"   => $e,
            ]);
        }
    }
}
