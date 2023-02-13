<?php

namespace RZP\Listeners;

use RZP\Http\RequestHeader;

class BaseListener
{
    private const DUMMY_ID    = '100000Razorpay';
    private const DUMMY_EMAIL = 'dummy-email@razorpay.in';

    private const USER_TYPE  = 'user';
    private const ADMIN_TYPE  = 'admin';
    private const MERCHANT_TYPE  = 'merchant';

    /** @var \RZP\Http\BasicAuth\BasicAuth $ba */
    protected $ba;

    /** @var \RZP\Base\RepositoryManager */
   protected $repo;

    /** @var \Razorpay\Trace\Logger $trace */
    protected $trace;

    /** @var \Illuminate\HTTP\Request $request */
    protected $request;

    public function __construct()
    {
        $this->ba = app('basicauth');
        $this->repo = app('repo');
        $this->trace = app('trace');
        $this->request = app('request');
    }

    protected function getActor(): array
    {
        $userId = null;
        $userEmail = null;
        $userType = null;

        if($this->ba->isBatchApp() === true)
        {
            $userId = $this->request->headers->get(RequestHeader::X_Creator_Id, null);
            $userType = $this->request->headers->get(RequestHeader::X_Creator_Type, null);

            switch($userType)
            {
                case self::USER_TYPE:
                    $userEmail = $this->repo->user->getUserFromId($userId)->getEmail();
                    break;
                case self::ADMIN_TYPE:
                    $userEmail = $this->repo->admin->getAdminFromId($userId)->getEmail();
                    break;
            }
        } elseif ($this->ba->isAdminAuth() === true)
        {
            $userId = $this->ba->getAdmin()->getId();
            $userEmail = $this->ba->getAdmin()->getEmail();
            $userType = self::ADMIN_TYPE;
        }
        elseif (empty($this->ba->getUser()) === false)
        {
            $userId = $this->ba->getUser()->getId();
            $userEmail = $this->ba->getUser()->getEmail();
            $userType = self::USER_TYPE;
        }
        elseif ((empty($this->ba->getMerchant()) === false))
        {
            $userId = $this->ba->getMerchant()->getId();
            $userEmail = $this->ba->getMerchant()->getEmail();
            $userType = self::MERCHANT_TYPE;
        }

        return [
            'actor_id'      => $userId ?? self::DUMMY_ID,
            'actor_email'   => $userEmail ?? self::DUMMY_EMAIL,
            'actor_type'    => $userType ?? self::MERCHANT_TYPE,
        ];
    }
}
