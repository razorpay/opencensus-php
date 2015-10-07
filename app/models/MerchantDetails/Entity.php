<?php

namespace Models\MerchantDetails;

use Models\Base;

class Entity extends Base\Entity
{
    protected static $URL_KEYS = [
        'business_website',
        'website_about',
        'website_contact',
        'website_privacy',
        'website_terms',
        'website_refund',
        'website_pricing',
        'website_login'
    ];

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
        'promoter_pan',
        'promoter_pan_name',
        'business_doe',
        'company_cin',
        'company_pan',
        'company_pan_name',
        'business_model',
        'transaction_volume',
        'transaction_value',
        'business_website',
        'website_about',
        'website_contact',
        'website_privacy',
        'website_terms',
        'website_refund',
        'website_pricing',
        'website_login',
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
        'business_operation_proof_url',
        'business_pan_url',
        'address_proof_url',
        'promoter_proof_url',
        'promoter_pan_url',
        'promoter_address_url',
        'steps_finished',
        'submitted',
        'locked',
        'comment',
        'submitted_at',
        'transaction_report_email'
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
        'promoter_pan',
        'promoter_pan_name',
        'business_doe',
        'company_cin',
        'company_pan',
        'company_pan_name',
        'business_model',
        'transaction_volume',
        'transaction_value',
        'business_website',
        'website_about',
        'website_contact',
        'website_privacy',
        'website_terms',
        'website_refund',
        'website_pricing',
        'website_login',
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
        'business_operation_proof_url',
        'business_pan_url',
        'address_proof_url',
        'promoter_proof_url',
        'promoter_pan_url',
        'promoter_address_url',
        'steps_finished',
        'submitted',
        'locked',
        'transaction_report_email'
    );

    protected static $uploadKeys = array(
        'business_proof'           => 'business_proof_url',
        'business_operation_proof' => 'business_operation_proof_url',
        'business_pan_proof'       => 'business_pan_url',
        'address_proof'             => 'address_proof_url',
        'promoter_proof'            => 'promoter_proof_url',
        'promoter_pan_proof'        => 'promoter_pan_url',
        'promoter_address_proof'    => 'promoter_address_url'

    );

    protected static $uploadDocuments = array(
        'business_operation_proof_url' => "Please upload business operation proof document.",
        'business_pan_url'   => "Please upload business pan card scan.",
        'address_proof_url'   => "Please upload address proof.",
        'promoter_pan_url'    => "Please upload authorised signatory pan card",
        'promoter_address_url'  => "Please upload authorised signatory address  proof."
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

    public function getUrls()
    {
        // Filter = Remove null values
        // Intersect + Flip = filter to the required keys
        return array_filter(array_intersect_key(
            $this->attributes, array_flip(self::$URL_KEYS)
        ));
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

    public function markSubmitted()
    {
        $this->setAttribute('submitted', 1);
        $this->setAttribute('submitted_at', time());
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

    public static function getUrlKeys()
    {
        return self::$URL_KEYS;
    }

    public function changeTransactionEmail($email)
    {
        $input = [
            'transaction_report_email'  => $email
        ];

        $error = $this->edit($input, 'editEmail');
    }
}
