<?php

namespace RZP\Models\Merchant\Request;

use Mail;
use RZP\Exception;
use RZP\Base\Common;
use RZP\Models\Base;
use RZP\Models\State;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Models\State\Reason;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Merchant\Detail\RejectionReasons;

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
    public function create(array $input)
    {
        $submissions = $input[Entity::SUBMISSIONS] ?? [];

        unset($input[Entity::SUBMISSIONS]);

        $request = new Entity();

        $request->generateId();

        $request->build($input);

        $this->transaction(function() use($request, $input, $submissions)
        {
            $this->createState(Status::UNDER_REVIEW, $request, $request->merchant);

            $this->repo->saveOrFail($request);

            if ($request->isProductRequest() === true and empty($submissions) === false)
            {
                (new Feature\Core)->postOnboardingSubmissions($request->merchant, $submissions, $input[Entity::NAME]);
            }
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
     *
     * @return State\Entity
     */
    protected function createState(string $state, Entity $request, PublicEntity $maker)
    {
        $params = [
            State\Entity::NAME => $state,
        ];

        $stateObj = (new State\Core)->createForMakerAndEntity($params, $maker, $request);

        return $stateObj;
    }

    /**
     * This function does the following :
     * 1. Updates Request Entity with the new status
     * 2. Add new State for Request
     * 3. Save Rejection Reasons if any
     *
     * @param Entity $request
     * @param array  $input
     * @param bool   $useWorkflow
     * @param bool   $validateStatusChange
     *
     * @return Entity
     */
    public function changeStatus(Entity $request, array $input, $useWorkflow = true, $validateStatusChange = true)
    {
        // Ignore workflows if not a product request
        if ($request->isProductRequest() === false)
        {
            $useWorkflow = false;
        }

        $request->getValidator()->validateInput('change_status', $input);

        if ($validateStatusChange === true)
        {
            $request->getValidator()->validateActivationStatusChange($request->getStatus(), $input[Entity::STATUS]);
        }

        $rejectionReasons = $input[Entity::REJECTION_REASONS] ?? [];

        unset($input[Entity::REJECTION_REASONS]);

        $oldRequestDetails = clone $request;

        $request->edit($input);

        $newRequestDetails = clone $request;

        $admin = $this->app['basicauth']->getAdmin();

        $status = $input[Entity::STATUS];

        $this->transaction(function() use(
            $request,
            $oldRequestDetails,
            $newRequestDetails,
            $admin,
            $status,
            $rejectionReasons,
            $useWorkflow)
        {
            if ($status === Status::ACTIVATED)
            {
                $this->addFeatureIfNotEnabled($request);
            }

            $this->triggerWorkflowOnNewStatus(
                $request,
                $status,
                $useWorkflow,
                $oldRequestDetails,
                $newRequestDetails,
                $rejectionReasons
            );

            $stateEntity = $this->createState($status, $request, $admin);

            (new Reason\Core)->addRejectionReasons($rejectionReasons, $stateEntity);

            $this->repo->saveOrFail($request);
        });

        return $request;
    }

    /**
     * Trigger a workflow on activation/rejection if applicable
     *
     * @param Entity $request
     * @param string $status
     * @param bool   $useWorkflow
     * @param Entity $oldRequestDetails
     * @param Entity $newRequestDetails
     * @param array  $rejectionReasons
     *
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    protected function triggerWorkflowOnNewStatus(Entity $request,
                                                     string $status,
                                                     bool $useWorkflow,
                                                     Entity $oldRequestDetails,
                                                     Entity $newRequestDetails,
                                                     array $rejectionReasons)
    {
        if ($useWorkflow === false)
        {
            return;
        }

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
                $rejectionReasons
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

            // Adds to live mode
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
        $request = $this->repo
                        ->merchant_request
                        ->fetch(
                            [
                                Entity::NAME => $input[Entity::NAME],
                                Entity::TYPE => $input[Entity::TYPE]
                            ],
                            $merchant->getId()
                        )
                        ->first();

        if (empty($request) === true)
        {
            $input[Entity::MERCHANT_ID] = $merchant->getId();

            $input[Entity::STATUS] = Status::UNDER_REVIEW;

            $request = $this->create($input);
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
        $requestStatus = Constants::mapOnboardingStatusToRequestStatus($onboardingStatus);

        return $this->forceUpsertMerchantRequest($merchant, $feature, $type, $requestStatus);
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
     *
     * @return Entity
     */
    public function forceUpsertMerchantRequest(Merchant\Entity $merchant,
                                                  string $feature,
                                                  string $type,
                                                  string $requestStatus)
    {
        $request = $this->findOrCreateMerchantRequest($merchant, [Entity::NAME => $feature, Entity::TYPE => $type]);

        if ($request->getStatus() !== $requestStatus)
        {
            $request = $this->changeStatus($request, [Entity::STATUS => $requestStatus], false, false);
        }

        return $request;
    }

    /**
     * Function to be used to replace rejection reason_codes with the actual descriptions to store in ES
     * and show it to the team on admin dashboard
     *
     * @param Entity $oldDetails
     * @param Entity $newDetails
     * @param array  $rejectionReasons
     *
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    protected function triggerWorkflowForRejectionStatusChange(
        Entity $oldDetails,
        Entity $newDetails,
        array $rejectionReasons)
    {
        $oldMerchantDetailsArray = $oldDetails->toArray();

        $newMerchantDetailsArray = $newDetails->toArray();

        $rejectionReasonDescriptions = [];

        foreach ($rejectionReasons as $rejectionReason)
        {
            $rejectionReasonCode = $rejectionReason[Reason\Entity::REASON_CODE] ?? "";

            $rejectionReasonDescriptions[] = RejectionReasons::getReasonDescriptionByReasonCode($rejectionReasonCode);
        }

        $newMerchantDetailsArray[Entity::REJECTION_REASONS] = $rejectionReasons;

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
     * @return mixed
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

        if ((isset($input[Entity::TYPE]) === true) and ($input[Entity::TYPE] === Type::PRODUCT))
        {
            if (isset($input[Entity::NAME]) === true)
            {
                $response[Entity::QUESTIONS] = (new Feature\Core)->getOnboardingQuestions([$input[Entity::NAME]]);
            }
        }

        return $response;
    }

    /**
     * @param string $id
     *
     * @return mixed
     */
    public function getMerchantRequestDetails(string $id)
    {
        $merchantRequest = $this->repo->merchant_request->getRequestDetails($id)->first();

        $returnData = $merchantRequest->toArrayPublic();

        if ($merchantRequest->isProductRequest() === true)
        {
            $returnData[Entity::SUBMISSIONS] = (new Feature\Core)->getOnboardingSubmissions(
                $merchantRequest->merchant,
                $merchantRequest->getName());

            $returnData[Entity::QUESTIONS] = (new Feature\Core)->getOnboardingQuestions(
                [$merchantRequest->getName()]);
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
            if (($request->isProductRequest() === true) and (isset($input[Entity::SUBMISSIONS]) === true))
            {
                $submissions = $input[Entity::SUBMISSIONS];

                unset($input[Entity::SUBMISSIONS]);

                (new Feature\Service)->updateOnboardingSubmissions($submissions, $request->getName());
            }

            if ($input[Entity::STATUS] !== $request->getStatus())
            {
                $statusChangeInput = [
                    Entity::STATUS            => $input[Entity::STATUS],
                    Entity::REJECTION_REASONS => $input[Entity::REJECTION_REASONS] ?? [],
                ];

                unset($input[Entity::REJECTION_REASONS]);

                $this->changeStatus($request, $statusChangeInput, true);
            }

            unset($input[Entity::STATUS]);

            $this->update($request, $input);
        });
    }

    /**
     * Validate submissions in case of product request, and check for valid product feature. If all is good, then
     * find or create the required Merchant Request.
     *
     * @param array $input
     *
     * @return Entity
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function createMerchantRequest(array $input): Entity
    {
        (new Validator)->validateSubmissions($input[Entity::TYPE], $input);

        (new Validator)->validateTypeAndProduct($input[Entity::TYPE], $input[Entity::NAME]);

        return $this->findOrCreateMerchantRequest($this->merchant, $input);
    }

    /**
     * Accepts a merchant map (merchantId => [[name, type, status], ...]) and updates the status
     *
     * @param array  $merchantMap
     *
     * @return array
     */
    public function bulkUpdateMerchantRequests(array $merchantMap): array
    {
        $success = 0;
        $failedItems  = [];

        foreach ($merchantMap as $merchantId => $requests)
        {
            $merchant = $this->repo->merchant->find((string) $merchantId);

            foreach ($requests as $request)
            {
                try
                {
                    if (empty($merchant) === true)
                    {
                        throw new Exception\LogicException('Unknown Merchant, hence feature not updated');
                    }

                    $response = $this->forceUpsertMerchantRequest(
                        $merchant,
                        $request[Entity::NAME],
                        $request[Entity::TYPE],
                        $request[Entity::STATUS]
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
}
