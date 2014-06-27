<?php

namespace Gateway\HdfcGateway;

use EE\Exception\InvalidArgumentException;
use Models\DAL\DAL;

class HdfcGatewayResponseXmlDal extends DAL
{
    protected $table = 'hdfc_response_xml';

    public $incrementing = false;

    protected $guarded = array();

    public function transaction()
    {
        return $this->belongsTo('Transaction', 'trackid', 'id');
    }

    public static function saveXml($id, $xml, $responseType)
    {
        $attributes = array(
            'trackid' => $id,
            $responseType => $xml);

        switch($responseType)
        {
            case 'enroll':
                return static::createOrFail($attributes);
                break;

            case 'auth_enrolled':
            case 'auth_not_enrolled':
                $responseFieldXml = $responseType;
                $model = static::where('trackid','=',$id)->firstOrFail();
                $model->$responseFieldXml = $xml;
                $model->save();
                return $model;
                break;

            case 'refund':
            case 'capture':
                return static::createOrFail($attributes);
                break;

            default:
                throw new InvalidArgumentException('Wrong responseType => '.$responseType);
        }
    }
}