<?php

namespace Gateway\HdfcGateway;

use Models\DAL\DAL;

class HdfcGatewayResponseXmlDal extends DAL
{
    protected $table = 'hdfc_response_xml';
    
    public $incrementing = false;

    protected $guarded = array();

    public function transaction()
    {
        return $this->belongsTo('Transaction', 'id', 'id');
    }

    public static function saveXml($id, $xml, $responseType)
    {
        $attributes = array(
            'id' => $id,
            $responseType => $xml);

        switch($responseType)
        {
            case 'enroll':
                return static::createOrFail($attributes);
                break;

            case 'auth_enrolled':
            case 'auth_not_enrolled':
            	$responseFieldXml = $responseType;
                $model = static::findOrFail($id);
                $model->$responseFieldXml = $xml;
                $model->save();
                return $model;
                break;

            default:
                throw new \InvalidArgumentException('Wrong responseType => '.$responseType);
        }
    }
}