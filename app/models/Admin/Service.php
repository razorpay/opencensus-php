<?php

namespace Models\Admin;

use AWS;
use Config;
use Models\Base;
use Models\Admin;
use Models\Merchant;
use Models\MerchantDetails;

class Service extends Base\Service
{
    public function login(array $input)
    {
        $error = (new Admin\Validator)->validateInput('login', $input)->messages();

        $verify = false;

        if (empty($error))
        {
            $verify = \Auth::admin()->attempt($input);
        }

        $error = ($verify) ? [] : ['Username or password is invalid.'];

        return [$error, null];
    }

    /**
     * Changes password oflogged in admin
     *
     * @param  $input input array
     * @param  $admin Admin\Entity Object
     * @return  Status
     */
    public function changePassword($input, $admin)
    {
        $error = $admin->changePassword($input);

        if (empty($error))
        {
            $admin->saveOrFail();
        }

        return [$error, null];
    }

    public function listMerchants($input)
    {
        $data = Merchant\Entity::join('merchant_details', 'merchants.id', '=', 'merchant_details.merchant_id')
                                ->select('id', 'name', 'email', 'confirm_token', 'activated', 'steps_finished', 'merchants.created_at', 'merchant_details.updated_at', 'submitted_at', 'archived_at');

        if(isset($input['archived']))
        {
            $data = $data->whereNotNull('archived_at')->get();
        }
        else
        {
            $data = $data->whereNull('archived_at')->get();
        }

        // $data = Merchant\Entity::with('merchantDetails')->where('archived', '', 0)->get();

        if(reset($input) !== false)
        {
            list($key, $value) = each($input);

            switch($key)
            {
                case "activated":
                    $response = $data->filter(function($merchant) use($value)
                    {
                        return ($merchant->activated == $value);
                    });
                    break;

                case "pending":
                    $response = $data->filter(function($merchant)
                    {
                        return ($merchant->activated == 0 and $merchant->merchant_details->submitted == 1);
                    });
                    break;

                case "confirmed":
                    $response = $data->filter(function($merchant) use($value)
                    {
                        if($value)
                            return ($merchant->confirm_token == null);
                        else
                            return !($merchant->confirm_token == null);
                    });
                    break;

                case "dead":
                    $response = $data->filter(function($merchant) use($value)
                    {
                        if($value)
                            return ($merchant->created_at < time() - 24*7*3600 and empty($merchant->merchant_details->steps_finished));
                        else
                            return !($merchant->created_at < time() - 24*7*3600 and empty($merchant->merchant_details->steps_finished));
                    });
                    break;

                default:
                   $response = $data;
            }
        }
        else
        {
            $response = $data;
        }

        $response = $response->toArray();

        return ['count'=>count($response), 'data'=>$response];
    }

    public function getAdmins()
    {
        return Admin\Entity::get()->toArray();
    }

    public function deleteAdmin($id)
    {
        $error = array();

        if ($id === \Auth::admin()->id())
        {
            $error[] = 'You can not delete yourself.';
        }

        $admin = Admin\Entity::findorfail($id);

        $admin->delete();

        return $error;
    }

    /* Adds a new admin
     * @param $data input array
     * @return Status
     */
    public function add($input)
    {
        $admin = new Admin\Entity;
        $error = $admin->build($input);

        if (empty($error))
        {
            $admin->saveOrFail();
        }

        return [$error, $admin->toArray()];
    }

    public function fetchMerchantActivationDetails($id)
    {
        $merchant_details =  MerchantDetails\Entity::findorfail($id);

        $response = $merchant_details->filterDetails();

        $response['data'] = MerchantDetails\Validator::sortDataInSteps($response['data']);

        foreach ($response['files'] as $key => &$file)
        {
            $extension_position = strrpos($file, '.', -1);
            $extension  = substr($file, $extension_position + 1);

            $s3 =  \AWS::get('s3');

            try
            {
                $result = $s3->getObjectUrl(
                            $_ENV['AWS_ACTIVATION_BUCKET'],
                            $id.'/'.$key.'.'.$extension,
                            '+10 minutes'
                );

                $file = $result;
            }
            catch(\Exception $e)
            {
                $file = 'ERROR';
            }
        }

        return $response;
    }

    public function fetchMerchantAndActivationDetails($id)
    {
        if ($id === null)
        {
            return [['id' => 'Merchant id cannot be null'], []];
        }

        $details = $this->fetchMerchantDetails($id);

        $activationDetails = $this->fetchMerchantActivationDetails($id);

        $data = array(
            'activation' => $activationDetails,
            'merchant'   => $details
        );

        return [[], $data];
    }

    public function fetchMerchantDetails($id)
    {
        $merchant = Merchant\Entity::findorfail($id);

        if ($merchant->confirm_token !== null)
        {
            return $merchant->toArray();
        }

        $merchant_details = MerchantDetails\Entity::findorfail($id);

        $this->setApiCredentials();

        $data = $this->api->merchant->fetch($id)->toArray();

        $data['merchant_details'] = $merchant_details->toArray();


        // @todo This is failing tests on wercker, fix
        // $merchant = Merchant\Entity::findorfail($id);
        // Merchant\Validator::checkAPIMatch($merchant, $response);

        $response = array(
            'archived_at'       => $merchant['archived_at'],
            'steps_finished'    => $merchant_details['steps_finished'],
            'locked'            => $merchant_details['locked'],
            'submitted'         => $merchant_details['submitted']
        ) + $data;

        return $response;
    }

    public function fetchMerchantBalance($id)
    {
        $this->setApiCredentials(null, 'test');

        $test = $this->api->merchant->fetch($id)->fetchBalance()->toArray();

        $this->setApiCredentials(null, 'live');

        $live = $this->api->merchant->fetch($id)->fetchBalance()->toArray();

        return compact('test', 'live');
    }

    public function fetchMerchantBanks($id)
    {
        $this->setApiCredentials();

        $response = $this->api->merchant->fetch($id)->fetchBanks()->toArray();

        return $response;
    }

    public function postEditMerchant($id, $input)
    {
        $error = (new Merchant\Validator)->validateInput('edit', $input)->messages();

        $data = [];

        if (empty($error))
        {
            $this->setApiCredentials();

            try
            {
                $data = $this->api->merchant->fetch($id)->edit($input)->toArray();
            }
            catch(\Razorpay\Api\Errors\BadRequestError $e)
            {
                $error[] = $e->getMessage();
            }
        }

        return array($error, $data);
    }

    public function postEditMerchantEmail($id, $input)
    {
        $data = $error = [];
        $this->setApiCredentials();

        try
        {
            $data = $this->api->merchant->fetch($id)->editEmail($input)->toArray();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return array($error, $data);
    }

    public function postEditMerchantComment($id, $comment)
    {
        $error = array();

        $merchant_details = MerchantDetails\Entity::findorfail($id);

        $merchant_details->comment = $comment;
        $merchant_details->save();

        return array($error, $comment);
    }

    public function postMerchantBanks($id, $input)
    {
        $error = (new Merchant\Validator)->validateInput('banks', $input)->messages();

        $data = [];

        if (empty($error))
        {
            $this->setApiCredentials();

            try
            {
                $data = $this->api->merchant->fetch($id)->setBanks($input)->toArray();
            }
            catch(\Razorpay\Api\Errors\BadRequestError $e)
            {
                $error[] = $e->getMessage();
            }
        }

        return array($error, $data);
    }

    public function postAddAdjustment($id, $input)
    {
        $data = [];
        $error = [];

        $mode = $input['mode'];
        unset($input['mode']);

        $this->setApiCredentials($id, $mode);

        try
        {
            $data = $this->api->adjustment->create($input)->toArray();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return array($error, $data);
    }

    public function postInitiateSetl($channel)
    {
        $data = [];
        $error = [];

        $this->setApiCredentials();

        try
        {
            $data = $this->api->settlement->initiate($channel)->toArray();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return array($error, $data);
    }

    public function postAddIIN($input)
    {
        $data = [];
        $error = [];

        $this->setApiCredentials();

        try
        {
            $data = $this->api->IIN->create($input)->toArray();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return array($error, $data);
    }

    public function getVerifyPayment($id)
    {
        $data = [];
        $error = [];

        $this->setApiCredentials();

        try
        {
            $data = $this->api->admin->fetchEntityById('payment', $id)
                                    ->verify()
                                    ->toArray();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return array($error, $data);
    }

    public function authorizeFailedPayment($id)
    {
        $data = [];
        $error = [];

        $this->setApiCredentials();

        try
        {
            $data = $this->api->admin->fetchEntityById('payment', $id)
                ->authorizeFailed()
                ->toArray();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return array($error, $data);
    }

    public function lockMerchant($id)
    {
        $error = array();

        $merchant_details = MerchantDetails\Entity::findorfail($id);

        if ($merchant_details->isLocked())
        {
            $error[] = 'Merchant already locked.';

            return $error;
        }

        $merchant_details->locked = 1;
        $merchant_details->save();

        return $error;
    }

    public function unlockMerchant($id)
    {
        $error = array();

        $merchant_details = MerchantDetails\Entity::findorfail($id);

        if ($merchant_details->locked === 0)
        {
            $error[] = 'Merchant already unlocked.';
            return $error;
        }

        $merchant_details->locked = 0;
        $merchant_details->save();

        return $error;
    }

    public function fetchMerchantTerminal($id)
    {
        $this->setApiCredentials();

        $live_terminals = $this->api->merchant->fetch($id)->fetchTerminals()->toArray();

        $this->setApiCredentials(null, 'test');

        $test_terminals = $this->api->merchant->fetch($id)->fetchTerminals()->toArray();

        foreach($test_terminals['items'] as &$item)
        {
            $item['mode'] = 'test';
        }

        foreach($live_terminals['items'] as &$item)
        {
            $item['mode'] = 'live';
        }

        $response = array(
            'entity'    => 'collection',
            'count'     => $live_terminals['count'] + $test_terminals['count'],
            'items'     => array_merge($live_terminals['items'], $test_terminals['items'])
        );

        return $response;
    }

    public function postMerchantTerminal($id, $input)
    {
        $error = (new Merchant\Validator)->validateInput('terminal', $input)->messages();

        $data = [];

        $mode = $input['mode'];

        if (empty($error))
        {
            unset($input['gateway_terminal_password_confirmation']);
            unset($input['mode']);

            $this->setApiCredentials(null, $mode);

            try
            {
                $data = $this->api->merchant->fetch($id)->setTerminal($input)->toArray();
            }
            catch(\Razorpay\Api\Errors\BadRequestError $e)
            {
                $error[] = $e->getMessage();
            }
        }

        return array($error, $data);
    }

    public function fetchMerchantPricing($id)
    {
        $this->setApiCredentials();

        $response = $this->api->merchant->fetch($id)->fetchPricing()->toArray();

        return $response;
    }

    public function postMerchantPricing($id, $input)
    {
        $error = array();
        $data = array();

        $this->setApiCredentials();

        try
        {
            $data = $this->api->merchant->fetch($id)->setPricing($input)->toArray();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return array($error, $data);
    }

    public function activateMerchant($id)
    {
        $merchant = Merchant\Entity::findorfail($id);

        $details = $this->fetchMerchantDetails($id);

        if ((int)$details['submitted'] === 0)
        {
            return array('Activation form has not been submitted by merchant yet.');
        }

        $this->setApiCredentials();

        $bankAccount = array(
            'ifsc_code'             => $details['merchant_details']['bank_branch_ifsc'],
            'beneficiary_name'      => $details['merchant_details']['bank_account_name'],
            'account_number'        => $details['merchant_details']['bank_account_number'],
            'beneficiary_address1'  => $details['merchant_details']['bank_beneficiary_address1'],
            'beneficiary_address2'  => $details['merchant_details']['bank_beneficiary_address2'],
            'beneficiary_address3'  => $details['merchant_details']['bank_beneficiary_address3'],
            'beneficiary_address4'  => '',
            'beneficiary_pin'       => $details['merchant_details']['bank_beneficiary_pin'],
            'beneficiary_city'      => $details['merchant_details']['bank_beneficiary_city'],
            'beneficiary_state'     => $details['merchant_details']['bank_beneficiary_state'],
            'beneficiary_country'   => 'IN',
            'beneficiary_email'     => $details['merchant_details']['contact_email'],
            'beneficiary_mobile'    => $details['merchant_details']['contact_mobile']
        );

        try
        {
            $this->api->merchant->fetch($id)->setBankAccount($bankAccount);

            $this->api->merchant->fetch($id)->activate();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            return array($e->getMessage());
        }

        $merchant->activated = 1;
        $merchant->save();

        $this->lockMerchant($id);

        return array();
    }

    public function generateMerchantHdfcExcel($id)
    {
        if ($id === null)
        {
            return [['id' => 'Merchant id cannot be null'], []];
        }

        list(, $data) = $this->fetchMerchantAndActivationDetails($id);

        $file = HdfcTidExcel::generateExcel($data);

        return [[], $file];
    }

    public function liveEnableMerchant($id)
    {
        $error = array();

        $merchant = Merchant\Entity::findorfail($id);

        if ((int)$merchant->activated === 0 or $merchant->archived_at !== null)
        {
            return array(
                'Merchant must be active & unarchived before enabling/disabling live transactions.');
        }

        $this->setApiCredentials();

        try
        {
            $this->api->merchant->fetch($id)->enable();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            return array($e->getMessage());
        }

        return array();
    }

    public function archiveMerchant($id)
    {
        $error = array();

        $merchant = Merchant\Entity::findorfail($id);

        if($merchant->archived_at !== null)
        {
            return array("Merchant already archived.");
        }

        $this->setApiCredentials();

        try
        {
            $data = $this->api->merchant->fetch($id);
            if ($data->live === true)
            {
                return array("Live merchants can not be archived.");
            }
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            return array($e->getMessage());
        }

        $merchant->archived_at = time();
        $merchant->save();

        return array();
    }

    public function unarchiveMerchant($id)
    {
        $error = array();

        $merchant = Merchant\Entity::findorfail($id);

        if($merchant->archived_at === null)
        {
            return array("Merchant not archived.");
        }

        $merchant->archived_at = null;
        $merchant->save();

        return array();
    }

    public function liveDisableMerchant($id)
    {
        $error = array();

        $merchant = Merchant\Entity::findorfail($id);

        if ((int)$merchant->activated === 0)
        {
            return array('Merchant must be active before enabling/disabling live transactions.');
        }

        $this->setApiCredentials();

        try
        {
            $this->api->merchant->fetch($id)->disable();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            return array($e->getMessage());
        }

        return array();
    }

    public function enableMerchantMethod($id, $method)
    {
        $error = array();

        $merchant = Merchant\Entity::findorfail($id);

        $this->setApiCredentials();

        try
        {
            $this->api->merchant->fetch($id)->editMethods([$method => 1]);
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            return array($e->getMessage());
        }

        return array();
    }

    public function disableMerchantMethod($id, $method)
    {
        $error = array();

        $merchant = Merchant\Entity::findorfail($id);

        $this->setApiCredentials();

        try
        {
            $this->api->merchant->fetch($id)->editMethods([$method => 0]);
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            return array($e->getMessage());
        }

        return array();
    }

    public function fetchPricingPlans()
    {
        $errors = array();

        $response = array();

        $this->setApiCredentials();

        try
        {
            $response = $this->api->pricing->merchants()->toArray();

            $response = $response['items'];
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $errors[] = $e->getMessage();
        }

        return array($errors, $response);
    }

    public function fetchPricingPlan($id)
    {
        $errors = array();

        $response = array();

        $this->setApiCredentials();

        try
        {
            $response = $this->api->pricing->fetch($id)->toArray();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $errors[] = $e->getMessage();
        }

        return array($errors, $response);
    }

    public function addPricingPlanRule($id, $input)
    {
        $error = array();

        $response = array();

        $this->setApiCredentials();

        try
        {
            $response = $this->api->pricing->fetch($id)->createRule($input)->toArray();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return array($error, $response);
    }

    public function createPricingPlan($input)
    {
        $error = array();

        $response = array();

        $this->setApiCredentials();

        try
        {
            $response = $this->api->pricing->create($input)->toArray();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return array($error, $response);
    }

    public function fetchMultipleEntities($mode, $entity, $input)
    {
        $error = array();

        $response = array();

        $this->setApiCredentials(null, $mode);

        try
        {
            $response = $this->api->admin->fetchMultipleEntities($entity, $input)->toArray();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        if(!empty($response['items']))
        {
            $response['headings'] = array_keys($response['items'][0]);
        }
        else
        {
            $response['headings'] = array();
        }

        return array($error, $response);
    }

    public function fetchEntityById($mode, $entity, $id)
    {
        $error = array();

        $response = array();

        $this->setApiCredentials(null, $mode);

        try
        {
            $response = $this->api->admin->fetchEntityById($entity, $id)->toArray();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return array($error, $response);
    }

    /**
     * Sends a redirect the the file
     */
    public function getBeneficiaryFile($input)
    {
        $error = (new Validator)->validateInput('get_beneficiary', $input)->messages();

        if (empty($error))
        {
            $date = \Input::get('date', date('Y-m-d'));
            return [null, $this->getBeneficiaryFileUrl($date)];
        }

        return [$error, null];
    }

    /**
     * Returns a pre-authed S3 URL to download beneficiary file
     * @param  Date $date date in Y-m-d format (with leading zeroes)
     * @return String URL
     */
    protected function getBeneficiaryFileUrl($date)
    {
        $s3 = AWS::get('s3');

        $beneficiaryBucket = Config::get('aws::config.buckets')['beneficiary'];
        $filename = $date.'.xls';

        return $s3->getObjectUrl($beneficiaryBucket, $filename, '+2 minutes', [
            'https'     => true
        ]);
    }

    public function generateBeneficiaryFile()
    {
        $this->setApiCredentials();
        try
        {
            $response = $this->api->merchant->generateBeneficiaryFile();
        }

        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return array();
    }

    /**
     * Fires off a queue worker to start capturing screenshots
     * @param  string $id merchant id
     */
    public function captureScreenshot($id)
    {
        $merchant =  MerchantDetails\Entity::findorfail($id);
        $urls = $merchant->getUrls();
        $name = $merchant->business_name;

        if (count($urls) >= 7)
        {
            \Queue::push('Models\Admin\Creevey', [$id, $urls, $name]);
            return [];
        }
        else
        {
            return ["The merchant needs to give all atleast 7 links"];
        }
    }

    /**
     * Saves provided screenshots to S3
     * @param  string $id    Merchant Id
     * @return array error
     */
    public function saveScreenshot($id, $input)
    {
        $keys = MerchantDetails\Entity::getUrlKeys();
        $found = false;
        $creevey = new Creevey($id);

        foreach ($keys as $key)
        {
            if (\Input::hasFile($key) and $input[$key]->isValid())
            {
                $found = true;
                $localFilePath = $input[$key]->getRealPath();

                try
                {
                    $creevey->compressAndSave($key, $localFilePath,
                        $input[$key]->getClientOriginalName());
                }
                catch(\Exception $e)
                {
                    return [$e->getMessage()];
                }
            }
        }

        if ($found === false)
        {
            return ["No matching files found while uploading"];
        }

        return [];
    }

    /**
     * Uploads a screenshot to S3
     * @param  string $objectPath Object path on S2
     * @param  String $filePath   Local file path
     */
    protected function uploadToS3($objectPath, $filePath)
    {
        $s3 =  AWS::get('s3');
        $s3Obj = [
            'Bucket'        => $_ENV['AWS_ACTIVATION_BUCKET'],
            'Key'           => $objectPath,
            'ContentType'   => "image/jpeg",
            'SourceFile'    => $filePath,
        ];

        $s3->putObject($s3Obj);
    }

    /**
     * Returns an associative array of links to S3
     * @param  string $id merchant id
     * @return array screenshot S3 links
     */
    public function getScreenshot($id)
    {
        $s3 =  \AWS::get('s3');
        $bucket = $_ENV['AWS_ACTIVATION_BUCKET'];
        $keys = MerchantDetails\Entity::getUrlKeys();

        $links = [];

        foreach ($keys as $key)
        {
            $filename = "$id/screenshots/$key.jpg";
            $links[$key] = $s3->getObjectUrl($bucket, $filename,
                '+10 minutes', [
                    'https'     => true
            ]);
        }

        return $links;
    }

    public function sendTestNewsletter($input)
    {
        $this->setApiCredentials();

        try
        {
            $input['email'] = \Auth::admin()->get()->email;

            return [null, $this->api->admin->sendTestNewsletter($input)
                ->toArray()];
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            return [$e->getMessage(), null];
        }
    }

    public function sendNewsletter($input)
    {
        $this->setApiCredentials();

        try
        {
            return [null, $this->api->admin->sendNewsletter($input)
                ->toArray()];
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            return [$e->getMessage(), null];
        }
    }

    public function triggerError()
    {
        $this->setApiCredentials();
        try
        {
            $errorMsg = $this->api->admin->triggerError();
            if($errorMsg)
            {
                return [null, $errorMsg];
            }
            else
            {
                return ['Error not triggered', null];
            }
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            return [$e->getMessage(), null];
        }
    }

    public function deleteTerminal($mode, $terminalId)
    {
        $this->setApiCredentials(null, $mode);
        try
        {
            $response = $this->api->terminal->delete($terminalId);
            return [null, $response->toArray()];
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            return [$e->getMessage(), null];
        }
    }

    public function editTerminal($mode, $terminalId, $input)
    {
        $this->setApiCredentials(null, $mode);
        try
        {
            $response = $this->api->terminal->edit($terminalId, $input);
            return [null, $response->toArray()];
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            return [$e->getMessage(), null];
        }
    }

    public function verifyAllPayments()
    {
        $this->setApiCredentials(null, 'live');
        try
        {
            $response = $this->api->payment->verifyAll();
            return [null, $response->toArray()];
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            return [[$e->getMessage()], null];
        }
    }

    public function generateNetBankingRefunds($input)
    {
        $this->setApiCredentials(null, $input['mode']);
        unset($input['mode']);

        try
        {
            $response = $this->api->refund->generateNetBankingExcel($input);
            return [null, $response->toArray()];
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            return [[$e->getMessage()], null];
        }

    }
}
