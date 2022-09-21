<?php

namespace RZP\Models\P2p\Mandate;

use App;
use Carbon\Carbon;
use RZP\Events\P2p;
use RZP\Models\P2p\Base;
use RZP\Error\P2p\Error;
use RZP\Trace\TraceCode;
use RZP\Error\P2p\ErrorCode;
use RZP\Models\P2p\Mandate\Actions;
use RZP\Exception\BadRequestException;
use RZP\Models\P2p\Base\Libraries\ArrayBag;
use RZP\Models\P2p\Upi\ExpectedHardFailures;
use RZP\Models\P2p\Mandate\Patch\Entity as PatchEntity;
use RZP\Exception\BadRequestValidationFailureException;

/**
 *   * @property Core $core
 */
class Processor extends Base\Processor
{
    /**
     * @param array $input
     *
     * @return array
     */
    public function incomingCollect(array $input): array
    {
        $this->initialize(Action::INCOMING_COLLECT, $input);

        $mandateInput = $this->input->bag(Entity::MANDATE);
        $upiInput     = $this->input->bag(Entity::UPI);

        $mandate = $this->createMandate($this->action, $mandateInput, $upiInput);

        return $mandate->toArrayPublic();
    }

    /**
    * @param array $input
    * This is the method to update the incoming mandate
    *
    * @return array
    */
    public function incomingUpdate(array $input): array
    {
        // TODO :- avoid direct state update - use patch to store data
        $this->initialize(Action::INCOMING_UPDATE, $input);

        // get the existing mandate
        $mandate = $this->core->findByUMN($this->input->bag(Entity::MANDATE)->get(Entity::UMN));

        // update only specific fields (not all the fields are allowed to override)
        $mandate[Entity::AMOUNT]     = $this->input->bag(Entity::MANDATE)->get(Entity::AMOUNT);
        $mandate[Entity::END_DATE]   = $this->input->bag(Entity::MANDATE)->get(Entity::END_DATE);
        $mandate[Entity::UPDATED_AT] = Carbon::now()->getTimestamp();

        $this->updatePatchStatusAccordingToMandateAction($mandate, $this->action);

        return $this->core->update($mandate, $mandate->toArray())->toArrayPublic();
    }


    /**
     * @param array $input
     * This is the method to update the incoming mandate
     *
     * @return array
     */
    public function incomingPause(array $input): array
    {
        // TODO :- avoid direct state update - use patch to store data
        $this->initialize(Action::INCOMING_PAUSE, $input);

        // get the existing mandate
        $mandate = $this->core->findByUMN($this->input->bag(Entity::MANDATE)->get(Entity::UMN));

        $pauseStart = $this->input->bag(Entity::MANDATE)->get(Entity::PAUSE_START);
        $pauseEnd   = $this->input->bag(Entity::MANDATE)->get(Entity::PAUSE_END);

        // if pause start is greater than pause end throw exception
        if($pauseStart > $pauseEnd)
        {
            throw new BadRequestValidationFailureException("Pause Start  Date cannot be greater than Pause End Date");
        }

        $mandate[Entity::PAUSE_START]     = $pauseStart;
        $mandate[Entity::PAUSE_END]       = $pauseEnd;

        $this->updatePatchStatusAccordingToMandateAction($mandate , $this->action);

        return $this->core->update($mandate, $input)->toArrayPublic();
    }


    /**
     * @param array $input
     * This is the method to update the incoming mandate
     *
     * @return array
     */
    public function mandateStatusUpdate(array $input): array
    {
        $this->initialize(Action::MANDATE_STATUS_UPDATE, $input);

        // get the existing mandate
        $mandate = $this->core->findByUMN($this->input->bag(Entity::MANDATE)->get(Entity::UMN));


        $mandate[Entity::STATUS]                = $this->input->bag(Entity::MANDATE)->get(Entity::STATUS);
        $mandate[Entity::INTERNAL_STATUS]       = $this->input->bag(Entity::MANDATE)->get(Entity::INTERNAL_STATUS);

        $this->updatePatchStatusAccordingToMandateAction($mandate, $this->action);

        return $this->core->update($mandate, $mandate->toArray())->toArrayPublic();
    }

    /**
     * @param array $input
     * This is the method to initiate authorize mandate flow
     * @return array
     * @throws \RZP\Exception\RuntimeException
     * @throws \Throwable
     */
    public function initiateAuthorize(array $input): array
    {
        $this->initialize(Action::INITIATE_AUTHORIZE, $input);

        $mandate = $this->core->fetch($this->input->get(Entity::ID));

        //TODO: implement validator logic

        $this->initiateCallGateway($mandate);

        return $this->callGateway();

    }

    /**
     * @param array $input
     * This is the function to get mandate data for authorization and call gateway
     * @return array
     * @throws \RZP\Exception\RuntimeException
     * @throws \Throwable
     */
    public function authorizeMandate(array $input): array
    {
        $this->initialize(Action::AUTHORIZE_MANDATE, $input);

        $mandate = $this->core->fetch($this->input->get(Entity::ID));

        $this->initiateCallGateway($mandate);

        return $this->callGateway();
    }

    /**
     * @param array $input
     * This is the function to process gateway response that come back for various actions
     *
     * @return array
     * @throws \RZP\Exception\LogicException
     * @throws \RZP\Exception\RuntimeException
     */
    public function authorizeMandateSuccess(array $input): array
    {
        $this->initialize(Action::AUTHORIZE_MANDATE_SUCCESS, $input);

        $mandateInput = $this->input->bag(Entity::MANDATE);

        $upiInput = $this->input->bag(Entity::MANDATE)[Entity::UPI];

        $mandate = $this->core->fetch($this->input->get(Entity::MANDATE)[Entity::ID]);

        $this->updateMandate($mandate, $mandateInput , new ArrayBag($upiInput), $this->action);

        return $mandate->toArrayPublic();
    }

    /**
     * @param array $input
     * This is the method to initiate reject mandate , takes mandate id as input and initiate reject for it
     *
     * @return array
     * @throws \RZP\Exception\RuntimeException
     * @throws \Throwable
     */
    public function initiateReject(array $input): array
    {
        $this->initialize(Action::INITIATE_REJECT, $input);

        $mandate = $this->core->fetch($this->input->get(Entity::ID));

        $this->initiateCallGateway($mandate);

        return $this->callGateway();
    }

    /**
     * @param array $input
     * This is the method to initiate reject mandate , takes mandate id as input and initiate pause for it
     *
     * @return array
     * @throws \RZP\Exception\RuntimeException
     * @throws \Throwable
     */
    public function initiatePause(array $input): array
    {
        // both the pause start and pause end should be set
        if (isset($input[Entity::PAUSE_START]) === false  or
            isset($input[Entity::PAUSE_END]) === false)
        {
            throw new BadRequestException("Pause start and pause end are required");
        }

        // if pause start is greater than pause end throw exception
        if($input[Entity::PAUSE_START] > $input[Entity::PAUSE_END])
        {
            throw new BadRequestValidationFailureException("Pause Start  Date cannot be greater than Pause End Date");
        }

        $this->initialize(Action::INITIATE_PAUSE, $input);

        $mandate                      = $this->core->fetch($this->input->get(Entity::ID));
        $mandate[Entity::PAUSE_START] = $input[Entity::PAUSE_START];
        $mandate[Entity::PAUSE_END]   = $input[Entity::PAUSE_END];

        $this->initiateCallGateway($mandate);

        return $this->callGateway();
    }

    /**
     * @param array $input
     * This is the function to get mandate data for pausing the data and call gateway
     *
     * @return array
     * @throws \RZP\Exception\RuntimeException
     * @throws \Throwable
     */
    public function pauseMandate(array $input): array
    {
        $this->initialize(Action::PAUSE, $input);

        $mandate = $this->core->fetch($this->input->get(Entity::ID));

        $this->initiateCallGateway($mandate);

        return $this->callGateway();
    }

    /**
     * @param array $input
     * This is the function to process gateway response that come back for various actions
     *
     * @return array
     * @throws \RZP\Exception\LogicException
     * @throws \RZP\Exception\RuntimeException
     */
    public function pauseSuccess(array $input): array
    {
        $this->initialize(Action::PAUSE_SUCCESS, $input);

        $mandateInput = $this->input->bag(Entity::MANDATE);

        $upiInput = $this->input->bag(Entity::MANDATE)[Entity::UPI];

        $mandate = $this->core->fetch($this->input->get(Entity::MANDATE)[Entity::ID]);

        $this->updateMandate($mandate, $mandateInput , new ArrayBag($upiInput), $this->action);

        return $mandate->toArrayPublic();
    }

    /**
     * @param array $input
     * This is the method to initiate un pause action
     *
     * @return array
     * @throws \Throwable
     */
    public function initiateUnpause(array $input): array
    {
        $this->initialize(Action::INITIATE_UNPAUSE, $input);

        $mandate = $this->core->fetch($this->input->get(Entity::ID));

        if ($mandate[Entity::INTERNAL_STATUS] !== Status::PAUSED)
        {
            throw new BadRequestException('Mandate is not paused, cannot unpause. Current mandate status is ' .$mandate[Entity::INTERNAL_STATUS]);
        }

        $this->initiateCallGateway($mandate);

        return $this->callGateway();
    }


    /**
     * @param array $input
     * This is the function to get mandate data for pausing the data and call gateway
     *
     * @return array
     * @throws \RZP\Exception\RuntimeException
     * @throws \Throwable
     */
    public function unpauseMandate(array $input): array
    {
        $this->initialize(Action::UNPAUSE, $input);

        $mandate = $this->core->fetch($this->input->get(Entity::ID));

        $this->initiateCallGateway($mandate);

        return $this->callGateway();
    }

    /**
     * @param array $input
     * This is the function to process gateway response that come back for unpause actions
     *
     * @return array
     * @throws \RZP\Exception\LogicException
     * @throws \RZP\Exception\RuntimeException
     */
    public function unpauseSuccess(array $input): array
    {
        $this->initialize(Action::UNPAUSE_SUCCESS, $input);

        $mandateInput = $this->input->bag(Entity::MANDATE);

        $upiInput = $this->input->bag(Entity::MANDATE)[Entity::UPI];

        $mandate = $this->core->fetch($this->input->get(Entity::MANDATE)[Entity::ID]);

        $this->updateMandate($mandate, $mandateInput , new ArrayBag($upiInput), $this->action);

        return $mandate->toArrayPublic();
    }

    /**
     * @param array $input
     * This is the method to initiate revoke mandate flow
     *
     * @return array
     * @throws \RZP\Exception\RuntimeException
     * @throws \Throwable
     */
    public function initiateRevoke(array $input): array
    {
        $this->initialize(Action::INITIATE_REVOKE, $input);

        $mandate = $this->core->fetch($this->input->get(Entity::ID));

        $this->initiateCallGateway($mandate);

        return $this->callGateway();
    }

    /**
     * @param array $input
     * This is the function to get mandate data for pausing the data and call gateway
     *
     * @return array
     * @throws \RZP\Exception\RuntimeException
     * @throws \Throwable
     */
    public function revokeMandate(array $input): array
    {
        $this->initialize(Action::REVOKE, $input);

        $mandate = $this->core->fetch($this->input->get(Entity::ID));

        $this->initiateCallGateway($mandate);

        return $this->callGateway();
    }

    /**
     * @param array $input
     * This is the function to process gateway response that come back for revoke actions
     *
     * @return array
     * @throws \RZP\Exception\LogicException
     * @throws \RZP\Exception\RuntimeException
     */
    public function revokeSuccess(array $input): array
    {
        $this->initialize(Action::REVOKE_SUCCESS, $input);

        $mandateInput = $this->input->bag(Entity::MANDATE);

        $upiInput = $this->input->bag(Entity::MANDATE)[Entity::UPI];

        $mandate = $this->core->fetch($this->input->get(Entity::MANDATE)[Entity::ID]);

        $this->updateMandate($mandate, $mandateInput , new ArrayBag($upiInput), $this->action);

        return $mandate->toArrayPublic();
    }

    /**
     * This is the method to intitiate gateway callback for the given mandate
     * @param Entity $mandate
     */
    protected function initiateCallGateway(Entity $mandate)
    {
        $this->gatewayInput->putMany([
             Entity::MANDATE      => $mandate ,
             Entity::PAYER        => $mandate->payer ,
             Entity::PAYEE        => $mandate->payee ,
             Entity::BANK_ACCOUNT => $mandate->bankAccount ,
             Entity::UPI          => $mandate->upi ,
         ]);

        $this->callbackInput->push($mandate->getPublicId());
    }

    /**
     * @param Entity   $mandate
     * @param ArrayBag $input
     * This is the function to update mandate statuses
     * @return mixed
     * @throws \RZP\Exception\LogicException
     * @throws \RZP\Exception\RuntimeException
     */
    protected function updateMandateStatus(Entity $mandate, ArrayBag $input):Actions
    {
        switch ($input->get(Entity::INTERNAL_STATUS))
        {
            case Status::COMPLETED:
                $actions = $this->setMandateCompleted($mandate, $input);
                break;

            case Status::APPROVED:
                $actions =  $this->setMandateApproved($mandate, $input);
                break;

            case Status::REJECTED:
                $actions = $this->setMandateRejected($mandate, $input);
                break;

            case Status::PAUSED:
                $actions = $this->setMandatePaused($mandate, $input);
                break;

            case Status::REVOKED:
                $actions = $this->setMandateRevoked($mandate, $input);
                break;

            case Status::FAILED:
                $actions = $this->setMandateFailed($mandate, $input);
                break;

            case Status::REQUESTED:
                $actions = $this->setMandateRequested($mandate, $input);
                break;

            default:
                throw $this->logicException('Invalid internal status for mandate', [
                    Entity::MANDATE         => $input,
                    Entity::ID              => $mandate->getId(),
                ]);
        }

        if ($actions->shouldUpdate() === true)
        {
            $this->core->update($mandate, $input->toArray());
        }

        return $actions;
    }

    /**
     * @param Entity   $mandate
     * @param ArrayBag $input
     * This is the method to set mandate status to be authorized
     * @throws \RZP\Exception\LogicException
     */
    protected function setMandateApproved(Entity $mandate, ArrayBag $input):Actions
    {
        $actions = new Actions();

        // if the mandate is not in an completed state throw the error
        if ($this->isExpired($mandate) === true)
        {
            throw $this->logicException('mandate has expired cannot mark it as approved', [
                Entity::MANDATE => $input,
                Entity::ID      => $mandate->getId(),
            ]);
        }

        if ($mandate->isFailed() === true || $mandate->isRevoked() === true )
        {
            throw $this->logicException('mandate can not be marked completed', [
                Entity::MANDATE         => $input,
                Entity::ID              => $mandate->getId(),
            ]);
        }
        else if ($mandate->isApproved() === true)
        {
            return $actions->setShouldUpdate(false);
        }

        $mandate->markApproved();

        $actions->setEvent(new P2p\MandateStatusUpdate($this->context(), $mandate));

        return $actions;
    }

    /**
     * @param Entity   $mandate
     * @param ArrayBag $input
     * This is the method to set mandate status to be completed
     * @throws \RZP\Exception\LogicException
     */
    protected function setMandateCompleted(Entity $mandate, ArrayBag $input):Actions
    {
        $actions = new Actions();

        if ($mandate->isFailed() === true)
        {
            throw $this->logicException('mandate can not be marked completed', [
                Entity::MANDATE         => $input,
                Entity::ID              => $mandate->getId(),
            ]);
        }
        else if ($mandate->isCompleted() === true)
        {
            return $actions->setShouldUpdate(false);
        }

        $mandate->markCompleted();

        $actions->setEvent(new P2p\MandateCompleted($this->context(), $mandate));

        return $actions;
    }

    /**
     * @param Entity   $mandate
     * @param ArrayBag $input
     * This is the method to set mandate status to be completed
     * @throws \RZP\Exception\LogicException
     */
    protected function setMandateRejected(Entity $mandate, ArrayBag $input):Actions
    {
        $actions = new Actions();

        // if the mandate is not in an completed state throw the error
         if ($this->isExpired($mandate) === true)
         {
            throw $this->logicException('mandate has expired cannot mark it as rejected', [
                Entity::MANDATE => $input,
                Entity::ID      => $mandate->getId(),
            ]);
        }

        if (($mandate->isFailed() === true ) or
            ($mandate->isRevoked() === true) or
            ($mandate->isApproved() === true))
        {
            throw $this->logicException('mandate can not be marked rejected', [
                Entity::MANDATE         => $input,
                Entity::ID              => $mandate->getId(),
            ]);
        }
        else if ($mandate->isRejected() === true)
        {
            return $actions->setShouldUpdate(false);
        }

        $mandate->markRejected();

        $actions->setEvent(new P2p\MandateStatusUpdate($this->context(), $mandate));

        return $actions;
    }

    /**
     * @param Entity   $mandate
     * @param ArrayBag $input
     * This is the method to set mandate status to be authorized
     *
     * @throws \RZP\Exception\LogicException
     */
    protected function setMandatePaused(Entity $mandate, ArrayBag $input):Actions
    {
        $actions = new Actions();

        // if the mandate is not in an completed state throw the error
        if ($this->isExpired($mandate) === true)
        {
            throw $this->logicException('mandate has expired cannot mark it as paused', [
                Entity::MANDATE => $input,
                Entity::ID      => $mandate->getId(),
            ]);
        }

        // if the mandate creation has already failed throw the error
        if ($mandate->isFailed() === true)
        {
            throw $this->logicException('mandate cannot be marked paused as the mandate has already failed', [
                Entity::MANDATE => $input,
                Entity::ID      => $mandate->getId(),
            ]);
        }

        // if the mandate is in revoked state throw the error
        else if ($mandate->isRevoked() === true)
        {
            throw $this->logicException('mandate cannot be marked paused as the mandate has already been revoked', [
                Entity::MANDATE => $input,
                Entity::ID      => $mandate->getId(),
            ]);
        }

        // if the mandate is not in an completed state throw the error
        else if ($mandate->isCompleted() === true)
        {
            throw $this->logicException('mandate is not in progress and already completed', [
                Entity::MANDATE => $input,
                Entity::ID      => $mandate->getId(),
            ]);
        }

        else if ($mandate->isPaused() === true)
        {
            return $actions->setShouldUpdate(false);
        }

        $mandate->markPaused();

        $actions->setEvent(new P2p\MandateStatusUpdate($this->context(), $mandate));

        return $actions;
    }

    /**
     * @param Entity   $mandate
     * @param ArrayBag $input
     * This is the method to set mandate status to be revoked
     *
     * @throws \RZP\Exception\LogicException
     */
    protected function setMandateRevoked(Entity $mandate, ArrayBag $input):Actions
    {
        $actions = new Actions();

        // if the mandate is not in an completed state throw the error
        if ($this->isExpired($mandate) === true)
        {
            throw $this->logicException('mandate has expired cannot mark it as revoked', [
                Entity::MANDATE => $input,
                Entity::ID      => $mandate->getId(),
            ]);
        }

        // if the mandate creation has already failed throw the error
        if ($mandate->isFailed() === true)
        {
            throw $this->logicException('mandate can not be marked completed', [
                Entity::MANDATE => $input,
                Entity::ID      => $mandate->getId(),
            ]);
        }

        // if the mandate is not in an completed state throw the error
        if ($mandate->isCompleted() === true)
        {
            throw $this->logicException('mandate cannot be marked as revoked as its already completed', [
                Entity::MANDATE => $input,
                Entity::ID      => $mandate->getId(),
            ]);
        }

        // no need to update the status if the mandate has already been revoked

        if ($mandate->isRevoked() === true)
        {
            return $actions->setShouldUpdate(false);
        }

        $mandate->markRevoked();

        $actions->setEvent(new P2p\MandateStatusUpdate($this->context(), $mandate));

        return $actions;
    }

    /**
     * This is the function to set mandate status as failed
     * @param Entity   $mandate
     * @param ArrayBag $input
     *
     * @throws \RZP\Exception\LogicException
     */
    protected function setMandateFailed(Entity $mandate, ArrayBag $input):Actions
    {
        $actions = new Actions();

        if ($mandate->isCompleted() === true)
        {
            throw $this->logicException('Transaction can not be marked failed', [
                Entity::MANDATE         => $input,
                Entity::ID              => $mandate->getId(),
            ]);
        }
        else if ($mandate->isFailed() === true)
        {
            return $actions->setShouldUpdate(false);
        }

        $mandate->setInternalStatus($input[Entity::INTERNAL_STATUS]);

        $error = new Error($input[Entity::INTERNAL_ERROR_CODE]);

        $mandate->setErrorCode($error->getPublicErrorCode());
        $mandate->setErrorDescription($error->getDescription());

        $actions->setEvent(new P2p\MandateFailed($this->context(), $mandate));

        return $actions;
    }

    /**
     * @param Entity   $mandate
     * @param ArrayBag $input
     * This is the method to set mandate status to be requested
     *
     * @throws \RZP\Exception\LogicException
     */
    protected function setMandateRequested(Entity $mandate, ArrayBag $input):Actions
    {
        $actions = new Actions();

        // if the mandate is not in an completed state throw the error
        if ($this->isExpired($mandate) === true)
        {
            throw $this->logicException('mandate has expired cannot mark it as requested', [
                Entity::MANDATE => $input,
                Entity::ID      => $mandate->getId(),
            ]);
        }

        // if the mandate creation has already failed throw the error
        if ($mandate->isFailed() === true)
        {
            throw $this->logicException('mandate cannot be marked as requested state as the mandate has already failed', [
                Entity::MANDATE => $input,
                Entity::ID      => $mandate->getId(),
            ]);
        }

        // if the mandate is in revoked state throw the error
        if ($mandate->isRevoked() === true)
        {
            throw $this->logicException('mandate cannot be marked as requested state as the mandate has already been revoked', [
                Entity::MANDATE => $input,
                Entity::ID      => $mandate->getId(),
            ]);
        }

        // if the mandate is not in an completed state throw the error
        if ($mandate->isCompleted() === true)
        {
            throw $this->logicException('mandate cannot be marked as requested state as the mandate has already been completed', [
                Entity::MANDATE => $input,
                Entity::ID      => $mandate->getId(),
            ]);
        }

        $mandate->markRequested();

        $actions->setEvent(new P2p\MandateStatusUpdate($this->context(), $mandate));

        return $actions;
    }
    /**
     * @param string   $action
     * @param ArrayBag $input
     * @param ArrayBag $upiInput
     * This is the method to create mandate and create upi and store it in the system
     * @return Entity
     * @throws \RZP\Exception\LogicException
     * @throws \RZP\Exception\P2p\BadRequestException
     * @throws \RZP\Exception\RuntimeException
     */
    protected function createMandate(string $action, ArrayBag $input, ArrayBag $upiInput): Entity
    {
        $mandateInput = clone $input;

        $properties = new Properties($this->context(), $this->action, $input);

        $mandate = $this->core->build($mandateInput->toArray());

        $properties->attachToMandate($mandate);

        $upi = $this->core->buildUpi($mandate, $action, $upiInput->toArray());

        $lock = $upi->getAction() . $upi->getNetworkTransactionId();

        return $this->app['api.mutex']->acquireAndRelease($lock,
            function() use ($mandate, $input, $upi , $action) {
                return $this->repo()->transaction(function() use ($mandate, $input, $upi, $action) {

                    $this->checkForDuplicate($upi);

                    $actions = $this->updateMandateStatus($mandate, $input);

                    $upi->associateMandate($mandate);

                    $this->core->updateUpi($upi, []);

                    $actionInput = [Entity::ACTION => $action, Entity::STATUS => Status::REQUESTED, PatchEntity::ACTIVE => false];

                    $this->createPatch($mandate, $actionInput);

                    $this->performMandateActions($actions, $mandate);

                    return $mandate;
                });
            });
    }

    /**
     * @param Entity   $mandate
     * @param ArrayBag $input
     * @param ArrayBag $upiInput
     * @param String   $action
     * Update the mandate data which is coming in from gateway
     * @return mixed
     * @throws \RZP\Exception\LogicException
     * @throws \RZP\Exception\RuntimeException
     */
    protected function updateMandate(Entity $mandate, ArrayBag $input, ArrayBag $upiInput ,String $action)
    {
        $lock = $mandate->upi->getAction() . $mandate->upi->getNetworkTransactionId();

        return $this->app['api.mutex']->acquireAndRelease($lock,
            function() use ($mandate, $input, $upiInput , $action) {
                $mandate->reload();

                return $this->repo()->transaction(function() use ($mandate, $input, $upiInput , $action) {

                    $actions = $this->updateMandateStatus($mandate, $input);

                    $this->core->updateUpi($mandate->upi, $upiInput->toArray());

                    $this->updatePatchStatusAccordingToMandateAction($mandate, $action);

                    $this->performMandateActions($actions, $mandate);

                    return $mandate;
                });
            }
        );
    }

    /**
     * @param UpiMandate\Entity $upi
     * This is the method to check for the duplicate upi mandate data before proceeding
     * @throws \RZP\Exception\LogicException
     * @throws \RZP\Exception\P2p\BadRequestException
     */
    protected function checkForDuplicate(UpiMandate\Entity $upi)
    {
        $existing = $this->core->findAllUpi($upi->toArray());

        if ($existing->count() > 0)
        {
            throw $this->badRequestException(ErrorCode::BAD_REQUEST_DUPLICATE_TRANSACTION, [
                Entity::UPI => $upi,
            ]);
        }
    }

    /**
     * Create patch with given status
     * @param Entity $mandate
     * @param string $action
     * @param string $status
     * @param bool   $active
     */
    protected function createPatch(Entity $mandate, Array $input)
    {
        $patch = $this->getPatchObject($mandate, $input);

        $patch = (new Patch\Core)->build($patch);

        return (new Patch\Core)->update($patch, []);
    }

    /**
     *
     * @param Entity $mandate
     * @param String $action
     */
    protected function updatePatchStatusAccordingToMandateAction(Entity $mandate, String $action)
    {
        switch($action)
        {
            case Action::AUTHORIZE_MANDATE_SUCCESS:

                $existingPatch = $this->core->findPatchByMandateIdAndActive($mandate->getId(), false);

                (new Patch\Core)->updatePatchStatus($mandate , $existingPatch , true);

                break;

            // these are user flows which are sent in by merchant directly and does not require update request
            /**
             * create a new patch for the belowing flow and make the old patch inactive for the below flows
             */
            case Action::INCOMING_UPDATE:
            case Action::INCOMING_PAUSE:
            case Action::PAUSE_SUCCESS:
            case Action::UNPAUSE_SUCCESS:
            case Action::REVOKE_SUCCESS:
            case Action::MANDATE_STATUS_UPDATE:

                $actionInput = [Entity::ACTION => $action, Entity::STATUS => $mandate->getInternalStatus(), PatchEntity::ACTIVE => false];

                $newPatch = $this->createPatch($mandate, $actionInput);

                $oldPatch = $this->core->findPatchByMandateIdAndActive($mandate->getId(), true);

                // make the old patch in active

                $oldPatch->setActive(false);

                (new Patch\Core)->update($oldPatch, []);

                // make the new patch active
                (new Patch\Core)->updatePatchStatus($mandate , $newPatch , true);

                break;
        }
    }

    /**
     * @param Entity $mandate
     * @param string $action
     * @param string $status
     * @param string $active
     * This is the method to get patch object
     * @return patch object related to mandate
     */
    public function getPatchObject(Entity $mandate, Array $input)
    {
        $details = $this->getDetails($mandate);

        $default = [
            Patch\Entity::MANDATE_ID               => $mandate->getId(),
            Patch\Entity::STATUS                   => $input[Entity::STATUS],
            Patch\Entity::ACTION                   => (new Patch\Action())->getAction($input[Entity::ACTION]),
            Patch\Entity::ACTIVE                   => $input[PatchEntity::ACTIVE],
            Patch\Entity::DETAILS                  => $details,
            Patch\Entity::EXPIRE_AT                => $mandate->getExpiry(),
            Patch\Entity::REMARKS                  => $mandate->getDescription()
        ];

        return $default;
    }

    /**
     * This is the method to get mandate details
     * @param Entity $mandate
     */
    public function getDetails(Entity $mandate)
    {
        return  [
            Entity::AMOUNT          => $mandate->getAmount(),
            Entity::AMOUNT_RULE     => $mandate->getAmountRule(),
            Entity::START_DATE      => $mandate->getStartDate(),
            Entity::END_DATE        => $mandate->getEndDate(),
        ];
    }

    /**
     * This is the method to perform mandate action
     * @param \RZP\Models\P2p\Mandate\Actions $actions
     * @param Entity                          $mandate
     */
    protected function performMandateActions(Actions $actions, Entity $mandate)
    {
        if ($actions->hasEvent() === true)
        {
            $this->app['events']->dispatch($actions->getEvent());
        }
    }

    /**
     * This is the function to check if its expired.
     * @param Entity $mandate
     */
    protected function isExpired(Entity $mandate)
    {
        if(!($mandate ->isEmpty  === true))
        {
            // if the expiry time is lesser than current time
            if ($mandate->getExpiry() < Carbon::now()->getTimestamp())
            {
                return true;
            }
        }
        return false;
    }

}
