<?php


namespace RZP\Models\Merchant\Detail\Upload;

use RZP\Models\Base;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\User\Service as UserService;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Detail\Core as DetailCore;
use RZP\Models\Merchant\Detail\Upload\Processors\Factory;

class Core extends Base\Core
{
    protected $mutex;

    protected $userService;

    protected $auth;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->userService = new UserService();

        $this->auth = $this->app['basicauth'];
    }

    public function uploadMerchant(array $input)
    {
        (new Validator)->validateInput('uploadMerchant', $input);

        $format = $input[Constants::FORMAT];

        $parser = Factory::getInstance($format);

        $merchantDetailsInput = $parser->parse($input[Constants::FILE]);

        return $this->mutex->acquireAndRelease(
            $merchantDetailsInput['contact_email'],
            function () use ($merchantDetailsInput) {

                return $this->repo->transactionOnLiveAndTest(function () use(
                    $merchantDetailsInput
                ) {
                    $user = $this->createUser(
                        $merchantDetailsInput['contact_email'],
                        $merchantDetailsInput['business_name']
                    );

                    $merchant = $this->createMerchant(
                        $user, $merchantDetailsInput['contact_email'],
                        $merchantDetailsInput['business_name']
                    );

                    $merchantDetailsInput[DetailEntity::SUBMIT] = '1';

                    return (new DetailCore)->saveMerchantDetails($merchantDetailsInput, $merchant);
                });
        });
    }

    protected function createUser(string $email, string $businessName)
    {
        $confirm_token = gen_uuid();

        $password = gen_uuid();

        $userInput = [
            'email'                 => $email,
            'password'              => $password,
            'password_confirmation' => $password,
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            'confirm_token'         => $confirm_token,
            'name'                  => $businessName
        ];

        return $this->userService->create($userInput);
    }

    protected function createMerchant($user, string $email, string $businessName)
    {
        $merchantInput = [
            'name'  => $businessName,
            'email' => $email,
            'org_id'=> $this->auth->getOrgId()
        ];

        $merchantData = $this->userService->createMerchantFromUser($merchantInput, $user, '', false, [], false);

        return $this->repo->merchant->findOrFailPublic($merchantData[MerchantEntity::ID]);
    }
}
