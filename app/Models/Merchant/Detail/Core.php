<?php

namespace RZP\Models\Merchant\Detail;

use Mail;
use Queue;
use Config;
use RZP\Jobs\RequestJob;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Notify as NotifyTrait;
use RZP\Models\Merchant\SlackActions as SlackActions;
use RZP\Mail\Admin\NotifyActivationSubmission as NotifyAdmin;
use RZP\Mail\Merchant\NotifyActivationSubmission as NotifyMerchant;
use Illuminate\Foundation\Bus\DispatchesJobs;

class Core extends Base\Core
{
    use NotifyTrait;
    use DispatchesJobs;

    /**
     * On submission of activation form by user, send email
     * to the customer and sales team notifying them about the activity
     */
    public function fireActivationTrigger($merchantDetails)
    {
        $merchantId = $this->merchant->id;

        $customer = [
            'id'            => $merchantId,
            'name'          => $merchantDetails['contact_name'],
            'email'         => $merchantDetails['contact_email'],
            'business_name' => $merchantDetails['business_name'],
            'dba'           => $merchantDetails['business_dba'],
            'website'       => $merchantDetails['business_website']
        ];

        // For marketplace linked accounts - skip sending this email
        if ($this->merchant->isLinkedAccount() === false)
        {
            $this->merchantNotifyActivationSubmission($merchantDetails);
        }

        $this->adminNotifyActivationSubmission($merchantDetails);

        // We also send over details to slack
        $link = "<https://dashboard.razorpay.com/admin#/app/merchants/{$merchantId}/activation|See activation form>";

        $this->logActionToSlack($this->merchant, SlackActions::SUBMIT_ACTIVATION, $customer, $link);

        $zapierData = $this->activationZapierData($customer);

        $this->postFormSubmissionToZapier($zapierData);
    }

    protected function activationZapierData(array $customer)
    {
        $customer['date'] = Carbon::createFromTimeStamp(time(), Timezone::IST)->format('j/m/Y');

        if ($this->merchant->users->isNotEmpty() === true)
        {
            $customer['contact_name'] = $this->merchant->users->first()->getAttribute('name');
        }

        return $customer;
    }

    public function postFormSubmissionToZapier($data)
    {
        if (Config::get('zapier.mock'))
        {
            return;
        }

        $url = Config::get('zapier.submissions');

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

    public function merchantNotifyActivationSubmission($merchantDetails)
    {
        $org = $this->merchant->org->toArray();

        $org['hostname'] = $this->merchant->org->getPrimaryHostName();

        $data = $merchantDetails->toArray();

        $notifyMerchantMail = new NotifyMerchant($data, $org);

        Mail::queue($notifyMerchantMail);
    }

    public function adminNotifyActivationSubmission($merchantDetails)
    {
        $data = $merchantDetails->toArray();

        $notifyAdminMail = new NotifyAdmin($data);

        Mail::queue($notifyAdminMail);
    }
}
