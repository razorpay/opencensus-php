<?php

namespace RZP\Models\Merchant\Request;

use Mail;

use RZP\Models\Base;
use RZP\Models\State;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Models\State\Reason;
use RZP\Models\Base\PublicEntity as PublicEntity;
use RZP\Models\Merchant\Detail\RejectionReasons as RejectionReasons;


class Core extends Base\Core
{
    public function create(array $input)
    {
        $questions = [];

        if ($input[Entity::TYPE] === Type::PRODUCT and isset($input[Entity::SUBMISSIONS]) === true)
        {
            $questions = $input[Entity::SUBMISSIONS];

            unset($input[Entity::SUBMISSIONS]);
        }

        $request = new Entity();

        $merchant = $this->merchant;

        $request->generateId();

        $request->build($input);

        $this->transaction(function() use($request, $merchant, $input, $questions)
        {
            $this->createInitialStateForRequest($request, $merchant);

            $this->repo->saveOrFail($request);

            if ($request->isProductRequest() === true and empty($questions) === false)
            {
                (new Feature\Core)->postOnboardingSubmissions($merchant, $questions, $input[Entity::NAME]);
            }
        });

        return $request;
    }

    public function update(Entity $request, array $input)
    {
        $request->edit($input);

        $this->repo->saveOrFail($request);

        return $request;
    }

    private function createState(string $state, Entity $request, PublicEntity $maker)
    {
        if (empty($state) === false)
        {
            $input = [
                State\Entity::NAME => $state,
            ];

            $stateObj = (new State\Core)->createForMakerAndEntity($input, $maker, $request);

            return $stateObj;
        }

        return null;
    }

    private function createRejectionReasons(array $rejectionReasons, State\Entity $state)
    {

        if (empty($rejectionReasons) === false and empty($state) === false)
        {
            return (new Reason\Core)->addRejectionReasons($rejectionReasons, $state);
        }

        return null;
    }

    protected function createInitialStateForRequest(Entity $request, Merchant\Entity $merchant)
    {
        return $this->createState(Status::UNDER_REVIEW, $request, $merchant);
    }

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
                $useWorkflow === true and $this->app['workflow']
                    ->setEntity($request->getEntity())
                    ->setOriginal($oldRequestDetails)
                    ->setDirty($newRequestDetails)
                    ->handle();

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

            if ($status === Status::REJECTED and $useWorkflow === true)
            {
                $this->triggerWorkflowForRejectionStatusChange(
                    $oldRequestDetails,
                    $newRequestDetails,
                    $rejectionReasons
                );
            }

            $state = $this->createState($status, $request, $admin);

            $this->createRejectionReasons($rejectionReasons, $state);

            $this->repo->saveOrFail($request);
        });

        return $request;

    }

    /*
     * Create a merchant request for a given type, name if not already present
     */
    public function createMerchantRequestIfApplicable(
        Merchant\Entity $merchant,
        array $input)
    {
        // Find by type and name first, to not to create a request again if it exists
        $request = $this->repo->merchant_request->findByMerchantIdAndTypeAndName(
            $merchant->getId(),
            $input[Entity::NAME],
            $input[Entity::TYPE]);

        if (empty($request) === true)
        {
            $input[Entity::MERCHANT_ID] = $merchant->getId();
            $input[Entity::STATUS] = Status::UNDER_REVIEW;

            $request = $this->create($input);
        }

        return $request;
    }

    /*
     * This function will be used to create/edit a merchant request from old feature flow
     * till the time the old code isnt deprecated. Hence first either the merchant request is created or found,
     * and then the respective status is marked if needed.
     */
    public function addRequestForcefullyIfApplicable(
        Merchant\Entity $merchant,
        string $feature,
        string $type,
        string $onboardingStatus)
    {
        $requestStatus = Constants::mapOnboardingStatusToRequestStatus($onboardingStatus);

        $request = $this->createMerchantRequestIfApplicable(
            $merchant,
            [Entity::NAME => $feature, Entity::TYPE => $type]);

        if ($request->getStatus() !== $requestStatus)
        {
            $request = $this->changeStatus($request, [Entity::STATUS => $requestStatus], false);
        }

        return $request;
    }

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

        $workflow = $this->app['workflow']
            ->setEntity($newDetails->getEntity())
            ->handle($oldMerchantDetailsArray, $newMerchantDetailsArray);
    }

    public function fetch(array $input)
    {
        $response = $this->repo->merchant_request->fetch($input);

        $response = $response->toArrayPublic();

        $response[Entity::QUESTIONS] = (new Feature\Core)->getOnboardingQuestions(Feature\Constants::PRODUCT_FEATURES);

        return $response;
    }

    public function getMerchantRequestDetails(string $id, string $merchantId)
    {
        $merchantRequest = $this->repo->merchant_request->getRequestDetails($id, $merchantId)->first();

        $returnData = $merchantRequest->toArray();

        if ($merchantRequest->isProductRequest() === true)
        {
            $returnData['questions'] = (new Feature\Core)->getOnboardingSubmissions(
                $merchantRequest->merchant,
                $merchantRequest->getName());
        }

        return $returnData;
    }

    public function updateMerchantRequest(Entity $request, array $input)
    {
        (new Validator)->validateInput('update', $input);

        //(new Validator)->validateQuestions($request->getType(), $input);

        $this->transaction(function() use($request, $input) {

            // Check form submissions on update
            if ($request->isProductRequest() === true and isset($input[Entity::SUBMISSIONS]) === true)
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
                }

                $this->changeStatus($request, $statusChangeInput, true);
            }

            unset($input[Entity::STATUS]);

            $this->update($request, $input);
        });
    }

    public function createMerchantRequest(array $input)
    {
        (new Validator)->validateInput('create_request', $input);

        (new Validator)->validateQuestions($input[Entity::TYPE], $input);

        (new Validator)->validateProduct($input[Entity::TYPE], $input[Entity::NAME]);

        return $this->createMerchantRequestIfApplicable($this->merchant, $input);
    }
}
