<?php

namespace Gateway\HdfcGateway;

class HdfcGatewayResponseXmlDal extends \Eloquent
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
				return static::create($attributes);
				break;

			case 'auth_enrolled':
			case 'auth_not_enrolled':
				$model = static::find($id);
				$model->$$responseType = $xml;
				$model->save();
				return $model;
				break;

			default:
				throw new \InvalidArgumentException('Wrong responseType => '.$responseType);
		}
	}

}