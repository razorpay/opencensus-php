<?php

namespace RZP\Models\Merchant\Detail;

use Mail;
use Queue;
use Config;

use Carbon\Carbon;
use Illuminate\Foundation\Bus\DispatchesJobs;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Jobs\RequestJob;
use RZP\Models\Merchant;
use RZP\Models\BankAccount;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Notify as NotifyTrait;
use RZP\Models\Merchant\SlackActions as SlackActions;
use RZP\Mail\Admin\NotifyActivationSubmission as NotifyAdmin;
use RZP\Mail\Merchant\NotifyActivationSubmission as NotifyMerchant;

class Core extends Base\Core
{
    use NotifyTrait;
    use DispatchesJobs;

    public function saveMerchantDetails(array $input, Merchant\Entity $merchant)
    {
        $this->trace->info(
            TraceCode::MERCHANT_SAVE_ACTIVATION_DETAILS,
            ['input' => $input]);

        $merchantDetails = $this->getMerchantDetails($merchant, $input);

        $merchantDetails->getValidator()->validateIsNotLocked();

        $merchantDetails->edit($input);

        return $this->repo->transactionOnLiveAndTest(function() use ($input, $merchantDetails, $merchant)
        {
            $this->repo->saveOrFail($merchantDetails);

            $response = $this->createResponse($merchantDetails);

            $eventAttributes = $merchant->toArrayEvent();

            if ($this->canSubmit($input, $response) === true)
            {
                $this->markSubmitted($merchantDetails);

                $this->app['eventManager']->trackEvents($merchant, Merchant\Action::SUBMITTED, $eventAttributes);
            }

            $autoActivated = $this->autoActivateMerchantIfApplicable($merchantDetails);

            $response = $this->createResponse($merchantDetails);

            $activationProgress = $response['verification']['activation_progress'];

            $merchantDetails->setActivationProgress($activationProgress);

            $this->repo->saveOrFail($merchantDetails);

            if ($this->canSubmit($input, $response) === true)
            {
                $this->fireActivationTrigger($merchantDetails, $merchant);
            }

            $response['auto_activated'] = $autoActivated;

            $eventAttributes['activation_progress'] = $activationProgress;

            $this->app['eventManager']
                ->trackEvents($merchant, Merchant\Action::ACTIVATION_PROGRESS, $eventAttributes);

            return $response;
        });
    }

    public function getMerchantDetails(Merchant\Entity $merchant, array $input = []): Entity
    {
        $merchantDetails = $merchant->merchantDetail;

        if ($merchantDetails === null)
        {
            $this->trace->info(
                TraceCode::MERCHANT_DETAIL_DOES_NOT_EXIST,
                [ 'merchant_id'    => $merchant->getId() ]);

            $merchantDetails = $this->createMerchantDetails($merchant, $input);
        }

        return $merchantDetails;
    }

    public function createMerchantDetails(Merchant\Entity $merchant, array $input = [])
    {
        $merchantDetail = (new Entity)->build($input);

        $merchantDetail->setContactEmail($merchant->getEmail());

        $merchantDetail->merchant()->associate($merchant);

        try
        {
            $this->repo->saveOrFail($merchantDetail);

            $this->trace->info(
                TraceCode::CREATE_MERCHANT_DETAIL,
                [ 'merchant_id'   => $merchant->getId()]);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::CREATE_MERCHANT_DETAIL_FAILED,
                [
                    Entity::MERCHANT_ID => $merchant->getId(),
                ]);
        }

        return $merchantDetail;
    }

    /**
     * On submission of activation form by user, send email
     * to the customer and sales team notifying them about the activity
     *
     * @param Entity          $merchantDetails
     * @param Merchant\Entity $merchant
     */
    protected function fireActivationTrigger(Entity $merchantDetails, Merchant\Entity $merchant)
    {
        $merchantId = $merchant->id;

        $customer = [
            'id'            => $merchantId,
            'name'          => $merchantDetails['contact_name'],
            'email'         => $merchantDetails['contact_email'],
            'business_name' => $merchantDetails['business_name'],
            'dba'           => $merchantDetails['business_dba'],
            'website'       => $merchantDetails['business_website']
        ];

        // For marketplace linked accounts - skip sending this email
        if ($merchant->isLinkedAccount() === false)
        {
            $this->merchantNotifyActivationSubmission($merchantDetails, $merchant);
        }

        $this->adminNotifyActivationSubmission($merchantDetails);

        // We also send over details to slack
        $link = "<https://dashboard.razorpay.com/admin#/app/merchants/{$merchantId}/activation|See activation form>";

        $this->logActionToSlack($merchant, SlackActions::SUBMIT_ACTIVATION, $customer, $link);

        $zapierData = $this->activationZapierData($customer, $merchant);

        $this->postFormSubmissionToZapier($zapierData, 'submissions');
    }

    protected function activationZapierData(array $customer, Merchant\Entity $merchant)
    {
        $customer['date'] = Carbon::createFromTimeStamp(time(), Timezone::IST)->format('j/m/Y');

        if ($merchant->users->isNotEmpty() === true)
        {
            $customer['contact_name'] = $merchant->users->first()->getAttribute('name');
        }

        return $customer;
    }

    public function postFormSubmissionToZapier($data, $zapierAction)
    {
        if (Config::get('zapier.mock'))
        {
            return;
        }

        $url = Config::get('zapier.' . $zapierAction);

        $request = [
            'url'     => $url,
            'method'  => 'post',
            'headers' => [],
            'options' => [],
            'content' => $data
        ];

        // Dispatching the job into the queue
        $job = new RequestJob($request);

        $this->dispatch($job);
    }

    protected function merchantNotifyActivationSubmission(Entity $merchantDetails, Merchant\Entity $merchant)
    {
        $org = $merchant->org->toArray();

        $org['hostname'] = $merchant->org->getPrimaryHostName();

        $data = $merchantDetails->toArray();

        $notifyMerchantMail = new NotifyMerchant($data, $org);

        Mail::queue($notifyMerchantMail);
    }

    protected function adminNotifyActivationSubmission(Entity $merchantDetails)
    {
        $data = $merchantDetails->toArray();

        $notifyAdminMail = new NotifyAdmin($data);

        Mail::queue($notifyAdminMail);
    }

    protected function canSubmit($input, $response)
    {
        return (($response['can_submit'] === true) and
                (isset($input[Entity::SUBMIT]) === true) and
                ($input[Entity::SUBMIT] === '1'));
    }

    protected function markSubmitted(Entity $merchantDetails)
    {
        $submittedAt = Carbon::now()->getTimestamp();

        $input = [
            Entity::SUBMITTED     => 1,
            Entity::SUBMITTED_AT  => $submittedAt
        ];

        $merchantDetails->fill($input);

        $this->repo->saveOrFail($merchantDetails);
    }

    /**
     * Checks and auto activates the merchant if possible, after form submission
     *
     * @param Entity $merchantDetails
     *
     * @return bool
     */
    protected function autoActivateMerchantIfApplicable(Entity $merchantDetails): bool
    {
        $merchant = $merchantDetails->merchant;

        //
        // Auto-activation is attempted if the following conditions are met
        //
        if (($merchantDetails->isSubmitted() === true) and
            ($merchant->isLinkedAccount() === true))
        {
            $bankCore = (new BankAccount\Core);

            // Build the input array for the merchant's bank account creation
            $bankData = $bankCore->buildBankAccountArrayFromMerchantDetail($merchantDetails, true);

            $bankCore->createOrChangeBankAccount($bankData, $merchant);

            (new Merchant\Activate)->autoActivate($merchant);

            $merchantDetails->setLocked(true);

            $this->repo->saveOrFail($merchantDetails);

            return true;
        }

        return false;
    }

    public function createResponse(Entity $merchantDetails)
    {
        $merchantDetailsArr = $merchantDetails->toArray();

        $response = $merchantDetails->toArrayPublic();

        $requiredFields = [];

        $validationFields = ValidationFields::DASHBOARD_FIELDS;

        $merchant = $merchantDetails->merchant;

        if ($merchant->isLinkedAccount() === true)
        {
            $validationFields = ValidationFields::MARKETPLACE_ACCOUNT_FIELDS;

            $parentMerchant = $merchant->parent;

            //
            // If the linked account's parent was flagged by admins,
            // linked accounts need to add additional KYC details and
            // documents before allowing the merchant to submit the form
            //
            if ($parentMerchant->linkedAccountsRequireKyc() === true)
            {
                $kycValidationFields = ValidationFields::MARKETPLACE_ACCOUNT_KYC_FIELDS;

                $validationFields = array_merge($validationFields, $kycValidationFields);
            }

            //
            // set key `need_kyc` for the client to determine where full KYC is needed
            // for a linked accounts activation
            //
            $response['need_kyc'] = (int) $parentMerchant->linkedAccountsRequireKyc();
        }

        $totalFields = count($validationFields);

        foreach ($validationFields as $key)
        {
            if ((array_key_exists($key, $merchantDetailsArr) === false) or
                (is_null($merchantDetailsArr[$key]) === true) or
                ((is_bool($merchantDetailsArr[$key]) !== true) and
                 (empty($merchantDetailsArr[$key]) === true)))
            {
                $requiredFields[] = $key;
            }
        }

        if (count($requiredFields) > 0)
        {
            $remainingFields = count($requiredFields);

            $response['verification'] = [
                'status'              => 'disabled',
                'disabled_reason'     => 'required_fields',
                'required_fields'     => $requiredFields,
                'activation_progress' => 100 - intval($remainingFields * 100 / $totalFields),
            ];

            $response['can_submit'] = false;
        }
        else
        {
            $response['verification'] = [
                'status'              => 'pending',
                'activation_progress' => 100,
            ];

            $response['can_submit'] = true;
        }

        $response['activated'] = (int) $merchant->isActivated();

        return $response;
    }
}
