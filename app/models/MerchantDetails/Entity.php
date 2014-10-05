<?php

namespace Models\MerchantDetails;

use Models\Base;

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
        'bussiness_type',
        'bussiness_category',
        'bussiness_subcategory',
        'bussiness_registered_address',
        'bussiness_registered_state',
        'bussiness_registered_city',
        'bussiness_registered_pin',
        'bussiness_operation_address',
        'bussiness_operation_state',
        'bussiness_operation_city',
        'bussiness_operation_pin',
        'bussiness_doe',
        'company_cin',
        'company_pan',
        'company_pan_name',
        'bussiness_model',
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
        'bussiness_proof_url',
        'bussiness_pan_url',
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
        'bussiness_type',
        'bussiness_category',
        'bussiness_subcategory',
        'bussiness_registered_address',
        'bussiness_registered_state',
        'bussiness_registered_city',
        'bussiness_registered_pin',
        'bussiness_operation_address',
        'bussiness_operation_state',
        'bussiness_operation_city',
        'bussiness_operation_pin',
        'bussiness_doe',
        'company_cin',
        'company_pan',
        'company_pan_name',
        'bussiness_model',
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
        'bussiness_proof_url',
        'bussiness_pan_url',
        'promoter_pan_url',
        'address_proof_url',
        'steps_finished',
        'submitted',
        'locked'
    );

    protected static $uploadKeys = array(
        'bussiness_proof'           => 'bussiness_proof_url',
        'bussiness_pan_proof'       => 'bussiness_pan_url',
        'promoter_pan_proof'        => 'promoter_pan_url',
        'address_proof'             => 'address_proof_url'
    );

    protected static $uploadDocuments = array(
        'bussiness_proof_url' => "Please upload bussiness proof document.",
        'bussiness_pan_url'   => "Please upload bussiness pan card scan.",
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
            'locked'         => $locked
            'submitted'      => $submitted,
            'steps_finished' => $steps_finished,
        );
    }

    protected function getFileUploadData($input)
    {
        $field = static::$uploadKeys[$key];

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

        $stepsFinished = $this->getAttriute('steps_finished');

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
        $stepsFinished = $this->getAttriute('steps_finished');

        return (in_array(5, $stepsFinished);
    }

    public function getStepsNotFinished()
    {
        $steps = range(1, 5);

        $stepsFinished = $this->getAttriute('steps_finished');

        return array_diff($steps, $stepsFinished);
    }

    public function finishStep($step, $input)
    {
        $error = $this->edit($input, 'step'.$step);

        if (empty($error))
        {
            $this->addStepToStepsFinished($step);
        }
    }
}
