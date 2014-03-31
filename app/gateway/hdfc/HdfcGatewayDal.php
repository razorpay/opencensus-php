<?php

namespace Gateway\HdfcGateway;

class HdfcGatewayDal extends \Models\DAL\DAL
{
	protected $table = 'hdfc';
	
	protected $primaryKey = 'paymentid';

	public $incrementing = false;

	protected $guarded = array();

	public function transaction()
	{
		return $this->belongsTo('Transaction', 'trackid', 'id');
	}

	public static function persistAfterEnroll($request, $response)
	{
		$attributes = array(
			'id' => $requestData['trackid'],
			'paymentid' => $responseData['paymentid'],
			'action' => $requestData['action'],
			'enroll_result' => $responseData['result'],
			'status' => 'VERES Received',
			'eci' => $responseData['eci']);

		return static::createOrFail($attributes);
	}

	public static function persistAfterEnrollError($id, array $error)
	{
		$attributes = array(
			'id' => $id,
			'error_code' => $error['code'],
			'error_service' => $error['service'],
			'error_text' => $error['text'],
			'enroll_result' => HdfcGatewayResult::FAIL_ENROLLED);

		return static::createOrFail($attributes);
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