<?php

namespace RZP\Models\P2p;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Customer;
use RZP\Models\Vpa;
use Razorpay\Trace\Logger as Trace;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function create($input)
    {
        $source = $this->setVpaFields($input, 'source');

        $sink = $this->setVpaFields($input, 'sink');

        $p2p = $this->core->create($input);

        $this->eventP2pCreated($p2p);

        return $p2p->toArrayPublic();
    }

    protected function setVpaFields(& $input, $field)
    {
        if ($this->isExternalVpa($input[$field]) === true)
        {
            $this->createVpaIfNeeded($input, $field);
        }

        $vpa = $this->repo->vpa->findByAddress($input[$field]);

        if ($vpa === null)
        {
            throw new Exception\BadRequestException(
                 ErrorCode::BAD_REQUEST_VPA_DOESNT_EXIST,
                 [
                    'vpa'=> $input[$field],
                 ]
            );
        }

        $input[$field . '_id'] = $vpa->getPublicId();

        unset($input[$field]);
    }

    protected function isExternalVpa($address)
    {
        list($username, $handle) = explode(Vpa\Entity::AROBASE, $address);

        return ($handle !== 'razor');
    }

    protected function createVpaIfNeeded($input, $field)
    {
        if ((($field === 'sink') and
             ($input[Entity::TYPE] === Type::SEND)) or
            (($field === 'source') and
             ($input[Entity::TYPE] === Type::COLLECT)))
        {
            // Commenting this block out because we have removed/redesigned vpa related functions.
            //
            // $vpa = $this->repo->vpa->findByAddress($input[$field]);
            //
            // if ($vpa === null)
            // {
            //     $body = [
            //         Vpa\Entity::ADDRESS => $input[$field],
            //     ];
            //
            //     $vpa = (new Vpa\Core)->createVpa($body);
            // }
        }
        else
        {
            throw new Exception\BadRequestException(
                 ErrorCode::BAD_REQUEST_INVALID_P2P,
                 [
                    'message'=> "Invalid $field in p2p",
                    'input'=> $input,
                 ]
            );
        }
    }

    public function reject($id)
    {
        $p2p = $this->core->reject($id);

        $this->eventP2pRejected($p2p);

        return $p2p->toArrayPublic();
    }

    public function getById($id)
    {
        Entity::stripSignWithoutValidation($id);

        $p2p = $this->repo->p2p->findOrFail($id);

        return $p2p->toArrayPublic();
    }

    public function getMultiple($input)
    {
        $p2ps = $this->repo->p2p->fetch($input);

        return $p2ps->toArrayPublic();
    }

    public function authorize(string $id, array $input)
    {
        $response = $this->core->authorize($id, $input);

        Entity::stripSignWithoutValidation($id);

        $p2p = $this->repo->p2p->findOrFail($id);

        $this->eventP2pTransferred($p2p);

        return $response;
    }

    protected function eventP2pCreated($p2p)
    {
        $eventPayload = [
            ApiEventSubscriber::MAIN => $p2p
        ];

        $this->app['events']->dispatch('api.p2p.created', $eventPayload);
    }

    protected function eventP2pRejected($p2p)
    {
        $eventPayload = [
            ApiEventSubscriber::MAIN => $p2p
        ];

        $this->app['events']->dispatch('api.p2p.rejected', $eventPayload);
    }

    protected function eventP2pTransferred($p2p)
    {
        $eventPayload = [
            ApiEventSubscriber::MAIN => $p2p
        ];

        $this->app['events']->dispatch('api.p2p.transferred', $eventPayload);
    }

    public function completeAuthorization(string $id, array $input)
    {
        // Checks the MPIN and completes the payment
        $p2p = $this->core->postAuthorize($id, $input);

        return $p2p;
    }

    public function fetchCollectRequests()
    {
        $customerId = $this->app['basicauth']->getDevice()->getCustomerId();

        return $this->fetchCollectRequestsForCustomer($customerId);
    }

    public function fetchCollectRequestsForCustomer($customerId)
    {
        $p2ps = $this->repo->p2p->fetchPendingCollectRequests($customerId);

        return $p2ps->toArrayPublic();
    }
}
