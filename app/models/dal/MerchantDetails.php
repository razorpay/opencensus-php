<?php

namespace Models\DAL;

class MerchantDetails extends DAL
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
        'steps_finished'
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
        'steps_finished'
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
        return $this->belongsTo(
            __NAMESPACE__.'\Merchant'
        );
    }

    public static function filterForAjax($merchant_details)
    {
        $data = array_intersect_key($merchant_details->toArray(), array_flip(static::$ajaxFields));

        $map = array_flip(static::$uploadKeys);

        $files = array();

        foreach($data as $origKey => $value)
        {
            // New key that we will insert into $newArray with
            if(isset($map[$origKey]))
            {   
                if($data[$origKey] != null)
                {
                    $newKey = $map[$origKey];

                    $files[$newKey] = '';
                }
                unset($data[$origKey]);
            }
        }

        $steps_finished = $data['steps_finished'];

        unset($data['steps_finished']);

        return array('data' =>  $data, 'files' => $files, 'steps_finished' => $steps_finished);
    }

    public static function getDataForUpload($input)
    {
        $key = key($input);

        $file = current($input);

        $field = static::$uploadKeys[$key];

        return array('key' => $key, 'file'  => $file, 'field'   => $field);
    }

    public static function checkUploadedFiles($merchant_details)
    {
        $error = array();

        $documents_needed = static::$uploadDocuments;

        while ($document = current($documents_needed)) 
        {
            if($merchant_details[key($documents_needed)] == null)
            {
                $error[] = $document;
            } 
            next($documents_needed);
        }
        return $error;
    }
}
