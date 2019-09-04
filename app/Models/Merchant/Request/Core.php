<?php

namespace RZP\Models\Merchant\Request;

use Mail;

use RZP\Exception;
use RZP\Base\Common;
use RZP\Models\Base;
use RZP\Models\State;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\State\Reason;
use RZP\Models\Settings\Accessor;
use RZP\Exception\LogicException;
use RZP\Models\Base\PublicEntity;
use RZP\Error\PublicErrorDescription;
use RZP\Mail\Merchant\RequestRejection;
use RZP\Mail\Merchant\RequestNeedsClarification;

class Core extends Base\Core
{
    /**
     * This function creates the following :
     * 1. Create and save request entity
     * 2. Create Initial State for Request
     * 3. Also save Onboarding Submissions, if any for a product type request
     *
     * @param array $input
     *
     * @return Entity
     */
    public function create(array $input, Merchant\Entity $merchant)
    {
        $submissions = $input[Constants::SUBMISSIONS] ?? [];

        unset($input[Constants::SUBMISSIONS]);

        $request = new Entity();

        $request->generateId();

        $request->build($input);

        $request->merchant()->associate($merchant);

        $this->repo->transactionOnLiveAndTest(function() use($request, $input, $submissions)
        {
            $this->repo->saveOrFail($request);

            $this->createState(Status::UNDER_REVIEW, $request, $request->merchant);

            $this->postSubmissions($request, $input, $submissions);
        });

        return $request;
    }

    /**
     * @param Entity $request
     * @param array  $input
     *
     * @return Entity
     */
    public function update(Entity $request, array $input)
    {
        $request->edit($input);

        $this->repo->saveOrFail($request);

        return $request;
    }

    /**
     * Creates a State Entity for the Request entity
     *
     * @param string       $state
     * @param Entity       $request
     * @param PublicEntity $maker
     * @param int          $statusDate
     *
     * @return State\Entity
     */
    protected function createState(string $state, Entity $request, PublicEntity $maker, int $statusDate = null)
    {
        $params = [
            State\Entity::NAME => $state,
        ];

        if ($statusDate !== null)
        {
            $params[State\Entity::CREATED_AT] = $statusDate;
        }

        $stateObj = (new State\Core)->createForMakerAndEntity($params, $maker, $request);

        return $stateObj;
    }

    /**
     * This function does the following :
     * 1. Updates Request Entity with the new status
     * 2. Add new State for Request
     * 3. Save Rejection Reason if any
     *
     * @param Entity $request
     * @param array  $input
     * @param bool   $useWorkflow
     * @param bool   $validateStatusChange
     *
     * @return Entity
     * @throws \Exception
     * @throws \Throwable
     */
    public function changeStatus(Entity $request, array $input, $useWorkflow = true, $validateStatusChange = true)
    {
        $statusDate = $input[State\Entity::CREATED_AT] ?? null;

        //
        // Unset this because state's created at is not a part of the merchant request entity
        // @todo: Remove the flow where the created_at is taken from the input - Bulk update merchant requests flow
        //
        unset($input[State\Entity::CREATED_AT]);

        $request->getValidator()->validateInput('change_status', $input);

        if ($validateStatusChange === true)
        {
            $request->getValidator()->validateActivationStatusChange($request->getStatus(), $input[Entity::STATUS]);
        }

        $rejectionReason = $input[Constants::REJECTION_REASON] ?? [];

        $needsClarificationText = $input[Constants::NEEDS_CLARIFICATION_TEXT] ?? [];

        unset($input[Constants::REJECTION_REASON]);

        unset($input[Constants::NEEDS_CLARIFICATION_TEXT]);

        $oldRequestDetails = clone $request;

        $request->edit($input);

        $newRequestDetails = clone $request;

        $admin = $this->app['basicauth']->getAdmin();

        //Putting in the merchant id as the admin to enable instant activation
        if ( (empty($admin) === true) and
             (Merchant\Request\Constants::isAutoApproveFeatureRequest($this->merchant, $request->getName()) === true))
        {
            $admin = $this->merchant;
        }

        $status = $input[Entity::STATUS];

        $this->repo->transactionOnLiveAndTest(function() use(
            $request,
            $oldRequestDetails,
            $newRequestDetails,
            $admin,
            $status,
            $rejectionReason,
            $useWorkflow,
            $needsClarificationText,
            $statusDate)
        {
            if ($useWorkflow === true)
            {
                $this->triggerWorkflowOnNewStatus(
                    $request,
                    $status,
                    $oldRequestDetails,
                    $newRequestDetails,
                    $rejectionReason
                );
            }

            $this->repo->saveOrFail($request);

            $stateEntity = $this->createState($status, $request, $admin, $statusDate);

            //
            // `addFeatureIfNotEnabled` involves saving on both live and test, and the functions called above act
            // only on live mode. This may have lead to erroneous data in case we were updating synced entities in
            // between unsynced ones and if some exception happened after the synced entities were internally committed.
            // Elaborately :
            //
            // transactionOnLive {
            //
            //      // commands on live mode
            //
            //      transactionOnLiveAndTest {
            //          //commands
            //      }
            //
            //      // commands
            //      // any exception thrown here will lead to rollback of only live mode and not the test mode
            // queries since the enclosing connection is of live only.
            //
            // }
            // The issue is because the enclosing outer transaction is only set on live and not on both the
            // connections. Hence for solving this problem we have wrapped the transaction on both live and test
            // connections using `transactionOnLiveAndTest`, so that any rollback if it happens, happens on both the
            // connections.
            //

            // Perform actions based on respective status
            switch ($status)
            {
                case Status::REJECTED:
                    if (empty($rejectionReason) === false)
                    {
                        (new Reason\Core)->addRejectionReasons([$rejectionReason], $stateEntity);

                        $this->sendRejectionEmail($request, $rejectionReason);
                    }

                    break;

                case Status::ACTIVATED:
                    $this->activated($request);

                    break;

                case Status::NEEDS_CLARIFICATION:
                    if (empty($needsClarificationText) === false)
                    {
                        $this->sendNeedsClarificationEmail($request, $needsClarificationText);
                    }

                    break;
            }
        });

        return $request;
    }

    /**
     * Handles request activation flow
     *
     * @param Entity $request
     *
     * @throws Exception\BadRequestException
     */
    protected function activated(Entity $request)
    {
        switch (true)
        {
            case $request->isProductRequest():
                $this->addFeatureIfNotEnabled($request);
                break;

            case $request->isPartnerActivationRequest():
                $this->markAsPartner($request);
                break;

            case $request->isPartnerDeactivationRequest():
                (new Merchant\Core)->unmarkAsPartner($request->merchant);
                break;

            default:
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_REQUEST_INVALID_NAME,
                    Entity::NAME,
                    [
                        Entity::ID   => $request->getId(),
                        Entity::NAME => $request->getName(),
                    ]);
        }
    }

    /**
     * @param Entity $merchantRequest
     *
     * @return Merchant\Entity
     * @throws LogicException
     */
    protected function markAsPartner(Entity $merchantRequest): Merchant\Entity
    {
        $submissions = $this->getPartnerSubmissions($merchantRequest);

        if (empty($submissions[Merchant\Entity::PARTNER_TYPE]) === true)
        {
            throw new LogicException(
                PublicErrorDescription::BAD_REQUEST_MERCHANT_REQUEST_SUBMISSIONS_MISSING,
                ErrorCode::BAD_REQUEST_MERCHANT_REQUEST_SUBMISSIONS_MISSING,
                $submissions);
        }

        $partnerType = $submissions[Merchant\Entity::PARTNER_TYPE];

        $merchant = $merchantRequest->merchant;

        $partner = (new Merchant\Core)->markAsPartner($merchant, $partnerType);

        return $partner;
    }

    /**
     * @param Entity $merchantRequest
     *
     * @return array
     */
    public function getPartnerSubmissions(Entity $merchantRequest): array
    {
        $settings = Accessor::for($merchantRequest, Merchant\Constants::PARTNER)->all();

        $response = $settings->toArray();

        return $response;
    }

    /**
     * Trigger a workflow on activation/rejection if applicable
     *
     * @param Entity $request
     * @param string $status
     * @param Entity $oldRequestDetails
     * @param Entity $newRequestDetails
     * @param array  $rejectionReason
     *
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    protected function triggerWorkflowOnNewStatus(
        Entity $request,
        string $status,
        Entity $oldRequestDetails,
        Entity $newRequestDetails,
        array $rejectionReason)
    {
        if ($status === Status::ACTIVATED)
        {
            $this->app['workflow']
                 ->setEntity($request->getEntity())
                 ->setOriginal($oldRequestDetails)
                 ->setDirty($newRequestDetails)
                 ->handle();
        }

        if ($status === Status::REJECTED)
        {
            $this->triggerWorkflowForRejectionStatusChange(
                $oldRequestDetails,
                $newRequestDetails,
                $rejectionReason
            );
        }
    }

    /**
     * Adds the feature to a merchant if its not already enabled
     *
     * @param Entity $request
     */
    protected function addFeatureIfNotEnabled(Entity $request)
    {
        // Check if feature is not already enabled, else add it.
        if ($request->merchant->isFeatureEnabled($request->getName()) === false)
        {
            // Add the feature
            $params = [
                Feature\Entity::ENTITY_TYPE => Constants::MERCHANT,
                Feature\Entity::ENTITY_ID   => $request->merchant->getId(),
                Feature\Entity::NAME        => $request->getName()
            ];

            (new Feature\Core)->create($params, true);
        }
    }

    /**
     * Create a merchant request for a given type, name if not already present
     *
     * @param Merchant\Entity $merchant
     * @param array           $input
     *
     * @return Entity
     */
    public function findOrCreateMerchantRequest(
        Merchant\Entity $merchant,
        array $input)
    {
        $fetchInput = [
            Entity::NAME => $input[Entity::NAME],
            Entity::TYPE => $input[Entity::TYPE]
        ];

        $merchantId = $merchant->getId();

        $request = $this->repo
                        ->merchant_request
                        ->fetch($fetchInput, $merchantId)
                        ->first();

        if (empty($request) === true)
        {
            $input[Entity::MERCHANT_ID] = $merchantId;

            $input[Entity::STATUS]      = Status::UNDER_REVIEW;

            $request = $this->create($input, $merchant);
        }

        return $request;
    }

    /**
     * Get the relevant status of merchant request from onboarding submission and then upsert it.
     *
     * @param Merchant\Entity $merchant
     * @param string          $feature
     * @param string          $type
     * @param string          $onboardingStatus
     *
     * @return Entity
     */
    public function syncOnboardingSubmissionToMerchantRequest(
        Merchant\Entity $merchant,
        string $feature,
        string $type,
        string $onboardingStatus)
    {
        $requestStatus = Constants::getRequestStatusForOnboardingStatus($onboardingStatus);

        $response = $this->forceUpsertMerchantRequest($merchant, $feature, $type, $requestStatus);

        return $response;
    }

    /**
     * This function will be used to create/edit a merchant request from old feature flow
     * till the time the old code isnt deprecated. Hence first either the merchant request is created or found,
     * and then the respective status is marked if needed.
     *
     * @param Merchant\Entity $merchant
     * @param string          $feature
     * @param string          $type
     * @param string          $requestStatus
     * @param int             $statusDate
     *
     * @return Entity
     */
    public function forceUpsertMerchantRequest(
        Merchant\Entity $merchant,
        string $feature,
        string $type,
        string $requestStatus,
        int $statusDate = null)
    {
        $request = $this->findOrCreateMerchantRequest($merchant, [Entity::NAME => $feature, Entity::TYPE => $type]);

        if ($request->getStatus() !== $requestStatus)
        {
            $request = $this->changeStatus(
                            $request,
                            [
                                Entity::STATUS     => $requestStatus,
                                Entity::CREATED_AT => $statusDate,
                            ],
                            false,
                            false);
        }
        else if ($statusDate !== null)
        {
            //
            // If the status is changed the date is handled in the above code block under the if condition
            // If only the created_at date has to be updated (status unchanged), it is handled here.
            //

            $stateEntity = $this->repo->state->findLastMerchantRequestState($request);

            $stateEntity->setCreatedAt($statusDate);

            $this->repo->saveOrFail($stateEntity);
        }

        return $request;
    }

    /**
     * Function to be used to replace rejection reason_codes with the actual descriptions to store in ES
     * and show it to the team on admin dashboard
     *
     * @param Entity $oldDetails
     * @param Entity $newDetails
     * @param array  $rejectionReason
     *
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    protected function triggerWorkflowForRejectionStatusChange(
        Entity $oldDetails,
        Entity $newDetails,
        array $rejectionReason)
    {
        $oldMerchantDetailsArray = $oldDetails->toArray();

        $newMerchantDetailsArray = $newDetails->toArray();

        $rejectionReasonCode = $rejectionReason[Reason\Entity::REASON_CODE] ?? "";

        $rejectionReasonDescription = RejectionReasons::getReasonDescriptionByReasonCode($rejectionReasonCode);

        $newMerchantDetailsArray[Constants::REJECTION_REASON] = $rejectionReasonDescription;

        $this->app['workflow']
             ->setEntity($newDetails->getEntity())
             ->handle($oldMerchantDetailsArray, $newMerchantDetailsArray);
    }

    /**
     * Fetches the request entites and also the questions to be shown to the person for the respective form.
     *
     * @param array       $input
     * @param string|null $merchantId
     * @param bool        $fetchFirst
     *
     * @return array
     */
    public function fetch(array $input, string $merchantId = null, bool $fetchFirst = false)
    {
        $response = $this->repo->merchant_request->fetch($input, $merchantId);

        if ($fetchFirst === true)
        {
            $response = $response->first();
        }

        if (empty($response) !== true)
        {
            $response = $response->toArrayPublic();
        }
        else
        {
            $response = [];
        }

        if ((isset($input[Entity::TYPE]) === true) and
            ($input[Entity::TYPE] === Type::PRODUCT))
        {
            if (isset($input[Entity::NAME]) === true)
            {
                $response[Constants::QUESTIONS] = (new Feature\Core)->getOnboardingQuestions([$input[Entity::NAME]]);
            }
        }

        return $response;
    }

    /**
     * @param string $id
     *
     * @return array
     */
    public function getMerchantRequestDetails(string $id)
    {
        $merchantRequest = $this->repo->merchant_request->getRequestDetails($id)->first();

        $returnData = $merchantRequest->toArrayPublic();

        if ($merchantRequest->isProductRequest() === true)
        {
            $featureCore = new Feature\Core;

            $returnData[Constants::SUBMISSIONS] = $featureCore->getOnboardingSubmissions(
                $merchantRequest->merchant,
                $merchantRequest->getName());

            $returnData[Constants::QUESTIONS] = $featureCore->getOnboardingQuestions(
                [$merchantRequest->getName()]);
        }

        if ($merchantRequest->isPartnerRequest() === true)
        {
            $returnData[Constants::SUBMISSIONS] = $this->getPartnerSubmissions($merchantRequest);
        }

        return $returnData;
    }

    /**
     * This function does the following :
     * 1. Update Onboarding submissions, if any
     * 2. Update Status/Rejection Reasons if any
     * 3. Update the Request Entity
     *
     * @param Entity $request
     * @param array  $input
     */
    public function updateMerchantRequest(Entity $request, array $input)
    {
        (new Validator)->validateInput('update', $input);

        $this->transaction(function() use($request, $input) {

            // Check form submissions on update
            if (($request->isProductRequest() === true) and
                (isset($input[Constants::SUBMISSIONS]) === true))
            {
                $submissions = $input[Constants::SUBMISSIONS];

                unset($input[Constants::SUBMISSIONS]);

                (new Feature\Core)->processOnboardingSubmissions(
                    Feature\Constants::UPDATE,
                    [$request->getName() => $submissions],
                    $request->merchant);
            }

            if ((isset($input[Entity::STATUS]) === true) and
                ($input[Entity::STATUS] !== $request->getStatus()))
            {
                $statusChangeInput = [
                    Entity::STATUS                      => $input[Entity::STATUS],
                    Constants::REJECTION_REASON         => $input[Constants::REJECTION_REASON] ?? [],
                    Constants::NEEDS_CLARIFICATION_TEXT => $input[Constants::NEEDS_CLARIFICATION_TEXT] ?? "",
                ];

                unset($input[Constants::REJECTION_REASON]);

                unset($input[Constants::NEEDS_CLARIFICATION_TEXT]);

                $this->changeStatus($request, $statusChangeInput, true);
            }

            unset($input[Entity::STATUS]);

            $this->update($request, $input);
        });
    }

    /**
     * This function does the following :
     * 1. Validates input to create the merchant request
     * 2. Validates the submissions if the request is for a product feature
     * 3. Validates the type-product combination
     * 4. Finds or creates the merchant request
     *
     * @param array $input
     *
     * @return Entity
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function createMerchantRequest(array $input): Entity
    {
        (new Validator)->validateCreateMerchantRequest($input);

        $type = $input[Entity::TYPE];

        //
        // @todo: Once the product activation requests are migrated to the merchant requests table,
        // remove the flow with findOrCreateMerchantRequest function. Create a new request every time.
        //
        if ($type === Type::PRODUCT)
        {
            $request = $this->findOrCreateMerchantRequest($this->merchant, $input);

            return $request;
        }

        //
        // Force set the database connection and mode to live.
        // @todo Instead of forcing live connection, block requests from test connection
        //
        if (in_array($type, Type::$liveModeRequestTypes, true) === true)
        {
            $liveMode = $this->app['basicauth']->getLiveConnection();

            // Sets the mode for the request, and database connection
            $this->setModeAndDefaultConnection($liveMode);
        }

        $request = $this->create($input, $this->merchant);

        return $request;
    }

    /**
     * @param array $merchantMap
     *
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     */
    public function bulkUpdateMerchantRequests(array $merchantMap): array
    {
        $success = 0;

        $failedItems  = [];

        (new Validator)->validateBulkUpdateMerchantRequests($merchantMap);

        foreach ($merchantMap as $merchantId => $requests)
        {
            $merchant = $this->repo->merchant->find((string) $merchantId);

            foreach ($requests as $request)
            {
                try
                {
                    // The timestamp when the request status was updated
                    $statusDate = (int) ($request[State\Entity::CREATED_AT] ?? null);

                    if ((isset($request[State\Entity::CREATED_AT]) === true) and ($statusDate <= 0))
                    {
                        throw new Exception\LogicException(
                            'The timestamp must be a valid epoch',
                            null,
                            [
                                Entity::MERCHANT_ID      => $merchantId,
                                State\Entity::CREATED_AT => $statusDate,
                            ]);
                    }

                    if (empty($merchant) === true)
                    {
                        throw new Exception\LogicException('Unknown Merchant, hence feature not updated');
                    }

                    $response = $this->forceUpsertMerchantRequest(
                        $merchant,
                        $request[Entity::NAME],
                        $request[Entity::TYPE],
                        $request[Entity::STATUS],
                        $statusDate
                    );

                    if (empty($response) === true)
                    {
                        throw new Exception\LogicException('Feature status could not be updated');
                    }

                    $success++;
                }
                catch (\Exception $ex)
                {
                    $failedItem = $request;

                    $failedItem[Common::MERCHANT_ID] = (string) $merchantId;

                    $failedItem['error'] = $ex->getMessage();

                    $this->trace->traceException(
                        $ex,
                        null,
                        null,
                        $failedItem);

                    $failedItems[] = $failedItem;
                }
            }
        }

        $response = [
            'success'     => $success,
            'failed'      => count($failedItems),
            'failedItems' => $failedItems,
        ];

        return $response;
    }

    /**
     * Sends out an email to the merchant with the particular message for rejection category specified in the
     * rejection reason
     *
     * @param Entity          $request
     * @param array           $rejectionReason
     */
    public function sendRejectionEmail(Entity $request, array $rejectionReason)
    {
        $merchant = $request->merchant;

        $merchantEmail  = $merchant->getEmail();

        $merchantId     = $merchant->getId();

        $featureName    = $request->getName();

        if ($request->isProductRequest() === false)
        {
            return;
        }

        $visibleFeatures = Feature\Constants::$visibleFeaturesMap;

        $data = [
            'feature'         => $visibleFeatures[$featureName]['display_name'],
            'documentation'   => $visibleFeatures[$featureName]['documentation'],
            'contact_name'    => $merchant->getName(),
            'contact_email'   => $merchantEmail,
            'merchant_id'     => $merchantId,
            'reason_category' => $rejectionReason[Reason\Entity::REASON_CATEGORY],
        ];

        $requestRejectionEmail = new RequestRejection($data);

        Mail::queue($requestRejectionEmail);
    }

    /**
     * Sends out an email to the merchant with the particular message for asking clarifications for the request
     *
     * @param Entity $request
     * @param string $needClarificationText
     */
    public function sendNeedsClarificationEmail(Entity $request, string $needClarificationText)
    {
        $merchant = $request->merchant;

        $merchantEmail  = $merchant->getEmail();

        $merchantId     = $merchant->getId();

        $featureName    = $request->getName();

        if ($request->isProductRequest() === false)
        {
            return;
        }

        $visibleFeatures = Feature\Constants::$visibleFeaturesMap;

        // Replacing empty new lines with breaks and enclosing them in paragraphs. Since this text would be coming
        // from frontend, we need to do this to format it in html.
        $needClarificationText = str_replace("\n", "\n<br/>\n", $needClarificationText);

        $data = [
            'feature'                  => $visibleFeatures[$featureName]['display_name'],
            'documentation'            => $visibleFeatures[$featureName]['documentation'],
            'contact_name'             => $merchant->getName(),
            'contact_email'            => $merchantEmail,
            'merchant_id'              => $merchantId,
            'needs_clarification_text' => $needClarificationText,
        ];

        $requestNeedsClarificationEmail = new RequestNeedsClarification($data);

        Mail::queue($requestNeedsClarificationEmail);
    }


    protected function postSubmissions(Entity $request, array $input, array $submissions)
    {
        if (empty($submissions) === true)
        {
            return;
        }

        switch (true)
        {
            case $request->isProductRequest():
                (new Feature\Core)->postOnboardingSubmissions($request->merchant, $submissions, $input[Entity::NAME]);
                break;

            // Partner deactivation requests do not have any submissions to store
            case $request->isPartnerActivationRequest():
                (new Merchant\Core)->postPartnerSubmissions($request, $submissions);
                break;
        }
    }
}
