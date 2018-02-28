<?php

namespace RZP\Models\Merchant\Request;

use Mail;
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
        $submissions = [];

        if (($input[Entity::TYPE] === Type::PRODUCT) and (isset($input[Entity::SUBMISSIONS]) === true))
        {
            $submissions = $input[Entity::SUBMISSIONS];

            unset($input[Entity::SUBMISSIONS]);
        }

        $request = new Entity();

        $merchant = $this->merchant;

        $request->generateId();

        $request->build($input);

        $this->transaction(function() use($request, $merchant, $input, $submissions)
        {
            $this->createState(Status::UNDER_REVIEW, $request, $merchant);

            $this->repo->saveOrFail($request);

            if ($request->isProductRequest() === true and empty($submissions) === false)
            {
                (new Feature\Core)->postOnboardingSubmissions($merchant, $submissions, $input[Entity::NAME]);
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

        $stateObj = (new State\Core)->createForMerchantRequest($params, $request, $maker);

        return $stateObj;
    }

    /**
     * This function does the following :
     * 1. Change Request Entity
     * 2. Add new State for Request
     * 3. Save Rejection Reasons if any
     *
     * @param Entity $request
     * @param array  $input
     * @param bool   $useWorkflow
     *
     * @return Entity
     */
    public function changeStatus(Entity $request, array $input, $useWorkflow = true)
    {
        // Ignore workflows if not a product request
        if ($request->isProductRequest() === false)
        {
            $useWorkflow = false;
        }

        $oldStatus = $request->getStatus();

        $request->getValidator()->validateInput('change_status', $input);

        $request->getValidator()->validateActivationStatusChange($oldStatus, $input[Entity::STATUS]);

        $rejectionReasons = [];

        if (empty($input[Entity::REJECTION_REASONS]) === false)
        {
            $rejectionReasons = $input[Entity::REJECTION_REASONS];

            unset($input[Entity::REJECTION_REASONS]);
        }

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
                if ($useWorkflow === true)
                {
                    $this->app['workflow']
                        ->setEntity($request->getEntity())
                        ->setOriginal($oldRequestDetails)
                        ->setDirty($newRequestDetails)
                        ->handle();
                }

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

            if (($status === Status::REJECTED) and ($useWorkflow === true))
            {
                $this->triggerWorkflowForRejectionStatusChange(
                    $oldRequestDetails,
                    $newRequestDetails,
                    $rejectionReasons
                );
            }

            $stateEntity = $this->createState($status, $request, $admin);

            (new Reason\Core)->addRejectionReasons($rejectionReasons, $stateEntity);

            $this->repo->saveOrFail($request);
        });

        return $request;
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
     * This function will be used to create/edit a merchant request from old feature flow
     * till the time the old code isnt deprecated. Hence first either the merchant request is created or found,
     * and then the respective status is marked if needed.
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

        $request = $this->findOrCreateMerchantRequest(
            $merchant,
            [Entity::NAME => $feature, Entity::TYPE => $type]);

        if ($request->getStatus() !== $requestStatus)
        {
            $request = $this->changeStatus($request, [Entity::STATUS => $requestStatus], false);
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
     *
     * @return mixed
     */
    public function fetch(array $input, string $merchantId = null)
    {
        $response = $this->repo->merchant_request->fetch($input, $merchantId);

        $response = $response->toArrayPublic();

        if ((isset($input[Entity::TYPE]) === true) and ($input[Entity::TYPE] === Type::PRODUCT))
        {
            if (isset($input[Entity::NAME]) === true)
            {
                $features = [$input[Entity::NAME]];
            }
            else
            {
                $features = Feature\Constants::PRODUCT_FEATURES;
            }

            $response[Entity::QUESTIONS] = (new Feature\Core)->getOnboardingQuestions($features);
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

        $returnData = $merchantRequest->toArray();

        if ($merchantRequest->isProductRequest() === true)
        {
            $returnData['questions'] = (new Feature\Core)->getOnboardingSubmissions(
                $merchantRequest->merchant,
                $merchantRequest->getName());
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

        //(new Validator)->validateQuestions($request->getType(), $input);

        $this->transaction(function() use($request, $input) {

            // Check form submissions on update
            if (($request->isProductRequest() === true) and (isset($input[Entity::SUBMISSIONS]) === true))
            {
                $questions = $input[Entity::SUBMISSIONS];

                unset($input[Entity::SUBMISSIONS]);

                (new Feature\Service)->updateOnboardingSubmissions($questions, $request->getName());
            }

            if ($input[Entity::STATUS] !== $request->getStatus())
            {
                $statusChangeInput = [
                    Entity::STATUS => $input[Entity::STATUS]
                ];

                if (isset($input[Entity::REJECTION_REASONS]) === true)
                {
                    $statusChangeInput[Entity::REJECTION_REASONS] = $input[Entity::REJECTION_REASONS];

                    unset($input[Entity::REJECTION_REASONS]);
                }

                $this->changeStatus($request, $statusChangeInput, true);
            }

            unset($input[Entity::STATUS]);

            $this->update($request, $input);
        });
    }

    /**
     * @param array $input
     *
     * @return Entity
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function createMerchantRequest(array $input): Entity
    {
        (new Validator)->validateInput('create_request', $input);

        (new Validator)->validateQuestions($input[Entity::TYPE], $input);

        (new Validator)->validateProduct($input[Entity::TYPE], $input[Entity::NAME]);

        return $this->findOrCreateMerchantRequest($this->merchant, $input);
    }
}
