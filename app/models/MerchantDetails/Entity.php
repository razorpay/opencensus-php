<?php

namespace Models\MerchantDetails;

use Models\Base;
use Mailgun;

class Entity extends Base\Entity
{
    protected $table = 'merchant_details';

    protected $primaryKey = 'merchant_id';

    protected $fillable = array(
        'merchant_id',
        'contact_name',
        'contact_email',
        'contact_mobile',
        'contact_landline',
        'business_type',
        'business_name',
        'business_dba',
        'business_website',
        'business_international',
        'business_paymentdetails',
        'business_registered_address',
        'business_registered_state',
        'business_registered_city',
        'business_registered_pin',
        'business_operation_address',
        'business_operation_state',
        'business_operation_city',
        'business_operation_pin',
        'business_doe',
        'company_cin',
        'company_pan',
        'company_pan_name',
        'business_model',
        'transaction_volume',
        'transaction_value',
        'promoter_pan',
        'promoter_pan_name',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'bank_account_type',
        'bank_branch',
        'bank_branch_ifsc',
        'bank_beneficiary_address1',
        'bank_beneficiary_address2',
        'bank_beneficiary_address3',
        'bank_beneficiary_city',
        'bank_beneficiary_state',
        'bank_beneficiary_pin',
        'business_proof_url',
        'business_pan_url',
        'promoter_pan_url',
        'address_proof_url',
        'steps_finished',
        'submitted',
        'locked'
    );

    protected static $ajaxFields = array(
        'contact_name',
        'contact_email',
        'contact_mobile',
        'contact_landline',
        'business_type',
        'business_name',
        'business_dba',
        'business_website',
        'business_international',
        'business_paymentdetails',
        'business_registered_address',
        'business_registered_state',
        'business_registered_city',
        'business_registered_pin',
        'business_operation_address',
        'business_operation_state',
        'business_operation_city',
        'business_operation_pin',
        'business_doe',
        'company_cin',
        'company_pan',
        'company_pan_name',
        'business_model',
        'transaction_volume',
        'transaction_value',
        'promoter_pan',
        'promoter_pan_name',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'bank_account_type',
        'bank_branch',
        'bank_branch_ifsc',
        'bank_beneficiary_address1',
        'bank_beneficiary_address2',
        'bank_beneficiary_address3',
        'bank_beneficiary_city',
        'bank_beneficiary_state',
        'bank_beneficiary_pin',
        'business_proof_url',
        'business_pan_url',
        'promoter_pan_url',
        'address_proof_url',
        'steps_finished',
        'submitted',
        'locked'
    );

    protected static $uploadKeys = array(
        'business_proof'           => 'business_proof_url',
        'business_pan_proof'       => 'business_pan_url',
        'promoter_pan_proof'        => 'promoter_pan_url',
        'address_proof'             => 'address_proof_url'
    );

    protected static $uploadDocuments = array(
        'business_proof_url' => "Please upload business proof document.",
        'business_pan_url'   => "Please upload business pan card scan.",
        'promoter_pan_url'    => "Please upload promoter pan card",
        'address_proof_url'   => "Please upload address proof."
    );

    public function merchant()
    {
        return $this->belongsTo('Models\Merchant\Entity');
    }

    public function filterForAjax()
    {
        $details = $this->filterDetails();

        // remove the urls for ajax
        foreach ($details['files'] as &$file)
        {
           $file = '';
        }

        return $details;
    }

    public function filterDetails()
    {
        $data = array_intersect_key(
                    $this->toArray(),
                    array_flip(static::$ajaxFields));

        $map = array_flip(static::$uploadKeys);

        $files = array();

        foreach ($data as $origKey => $value)
        {
            // New key that we will insert into $newArray with
            if (isset($map[$origKey]))
            {
                if ($data[$origKey] != null)
                {
                    $newKey = $map[$origKey];

                    $files[$newKey] = $data[$origKey];
                }

                unset($data[$origKey]);
            }
        }

        $steps_finished = $data['steps_finished'];
        $submitted = $data['submitted'];
        $locked = $data['locked'];

        unset($data['steps_finished']);
        unset($data['submitted']);
        unset($data['locked']);

        return array(
            'data'           => $data,
            'files'          => $files,
            'locked'         => $locked,
            'submitted'      => $submitted,
            'steps_finished' => $steps_finished,
        );
    }

    public static function getFileUploadData($input)
    {
        $field = static::$uploadKeys[key($input)];

        return array(
            'key'   => key($input),
            'file'  => current($input),
            'field' => $field);
    }

    public function checkUploadedFiles()
    {
        $error = array();

        foreach (static::$uploadDocuments as $key => $document)
        {
            if ($this->getAttribute($key) == null)
            {
                $error[] = $document;
            }
        }

        return $error;
    }

    protected function getStepsFinishedAttribute($stepsFinished)
    {
        return json_decode($stepsFinished, true);
    }

    protected function setStepsFinishedAttribute($value)
    {
        $this->attributes['steps_finished'] = json_encode($value);
    }

    public function addStepToStepsFinished($step)
    {
        if ($step > 5)
        {
            throw new \LogicException('Step should be less than 5' . $step);
        }

        $stepsFinished = $this->getAttribute('steps_finished');

        if (in_array($step, $stepsFinished) === false)
        {
            $stepsFinished[] = (int)$step;

            $this->setAttribute('steps_finished', $stepsFinished);
        }
    }

    public function markSubmittedTrue()
    {
        $this->setAttribute('submitted', 1);
    }

    public function isLocked()
    {
        return $this->getAttribute('locked');
    }

    public function isStepFinished($step)
    {
        // Check if already finished
        $stepsFinished = $this->getAttribute('steps_finished');

        return in_array(5, $stepsFinished);
    }

    public function getStepsNotFinished()
    {
        $steps = range(1, 5);

        $stepsFinished = $this->getAttribute('steps_finished');

        return array_diff($steps, $stepsFinished);
    }

    public function finishStep($step, $input)
    {
        $error = $this->edit($input, 'step'.$step);

        if (empty($error))
        {
            $this->addStepToStepsFinished($step);
        }

        return $error;
    }

    public function sendMails()
    {
        $customer = array(
            'id' => $this->getAttribute('merchant_id'),
            'name' => $this->getAttribute('contact_name'),
            'email' => $this->getAttribute('contact_email')
        );

        $sales_email = 'sales@razorpay.com';

        Mailgun::send('emails.submission', compact('customer'), function($m) use ($customer)
        {
            $m->to($customer['email'], $customer['name'])->subject('Your Razorpay acount is pending approval');
        });

        Mailgun::send('emails.admin_notify', compact('customer'), function($m) use ($customer, $sales_email)
        {
            $m->to($sales_email, 'Razorpay Sales Team')->subject('New activation form submitted - '.$customer['id']);
        });
    }
}
