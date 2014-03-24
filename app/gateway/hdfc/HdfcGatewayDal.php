<?php

namespace Gateway\HdfcGateway;

class HdfcGatewayDal extends \Eloquent
{
	protected $table = 'hdfc';
	
	protected $primaryKey = 'paymentid';

	public $incrementing = false;

	protected $guarded = array();

	public function transaction()
	{
		return $this->belongsTo('Transaction', 'trackid', 'id');
	}

	public static function persistAfterEnroll($requestData, $responseData)
	{
		$attributes = array(
			'paymentid' => $responseData['paymentid'],
			'trackid' => $requestData['trackid'],
			'action' => $requestData['action'],
			'enroll_result' => $responseData['result'],
			'status' => 'VERES Recieved',
			'eci' => $responseData['eci'],
			'error_text' => $responseData['error_text']);

		return static::create($attributes);
	}

	public function persistAferCCAuth($data)
	{
		$attributes['status'] = 'PaRes Received';
		$attributes['error_text'] = $data['error_text'];
		$attributes['auth_result'] = $data['result'];
		$attributes['ref'] = $data['ref'];
		$attributes['auth'] = $data['auth'];
		$attributes['avr'] = $data['avr'];
		$attributes['postdate'] = $data['postdate'];

		$this->save();
	}

	public function persistAfterDCAuth($data)
	{
		$attributes['status'] = 'PaRes Received';
		$attributes['error_text'] = $data['error_text'];
		$attributes['auth_result'] = $data['result'];
		$attributes['ref'] = $data['ref'];
		$attributes['auth'] = $data['auth'];
		$attributes['avr'] = $data['avr'];
		$attributes['postdate'] = $data['postdate'];
		$this->save();
	}
}