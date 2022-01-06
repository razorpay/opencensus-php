<?php

namespace RZP\Models\Merchant\InternationalEnablement;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Typeform;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class Service extends Base\Service
{
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
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
        $sanitizedInput = $this->sanitizeExternalPayload($input);

        $this->trace->info(TraceCode::INTERNATIONAL_ENABLEMENT_DATA, [
            'merchant_id'     => $this->merchant->getId(),
            'data'            => $input,
            'sanitized_input' => $sanitizedInput,
        ]);

        $input = $sanitizedInput;

        $mutexKey = sprintf(Constants::INTERNATIONAL_ENABLEMENT_LOCK_KEY, $this->merchant->getId());

        $entity = $this->mutex->acquireAndRelease(
            $mutexKey,
            function() use ($input)
            {
                $this->handleRequestEnablement($input, true);

                return $this->core()->upsert($input, Detail\Constants::ACTION_DRAFT);
            });

        return $this->core()->convertToExternalFormat($entity);
    }

    public function submit(array $input): array
    {
        $sanitizedInput = $this->sanitizeExternalPayload($input);

        $this->trace->info(TraceCode::INTERNATIONAL_ENABLEMENT_DATA, [
            'merchant_id'     => $this->merchant->getId(),
            'data'            => $input,
            'sanitized_input' => $sanitizedInput,
        ]);

        $input = $sanitizedInput;

        if ($this->app['api.route']->isWorkflowExecuteOrApproveCall() === true)
        {
            $this->createWorkflowsIfApplicable($input);

            return [];
        }

        $mutexKey = sprintf(Constants::INTERNATIONAL_ENABLEMENT_LOCK_KEY, $this->merchant->getId());

        $entity = $this->mutex->acquireAndRelease(
            $mutexKey,
            function() use ($input)
            {
                $this->handleRequestEnablement($input);

                return $this->core()->upsert($input, Detail\Constants::ACTION_SUBMIT);
            });

        $this->createWorkflowsIfApplicable($input, $entity);

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

    public function createWorkflowsIfApplicable(array $input, $ieDetail = null)
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

        (new Typeform\Core)->processInHouseQuestionnaire($this->merchant, $workflowData, $input);
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
}
