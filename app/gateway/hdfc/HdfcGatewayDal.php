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
            'trackid' => $request['trackid'],
            'paymentid' => $response['paymentid'],
            'action' => $request['action'],
            'result' => $response['result'],
            'status' => 'VERES Received',
            'eci' => $response['eci']);

        return static::createOrFail($attributes);
    }

    public static function persistAfterEnrollError($id, array $error)
    {
        $attributes = array(
            'trackid' => $id,
            'error_code' => $error['code'],
            'error_service' => $error['service'],
            'error_text' => $error['text'],
            'result' => HdfcGatewayResult::FAIL_ENROLLED);

        return static::createOrFail($attributes);
    }

    public function persistAfterCCAuth($data)
    {
        $attributes['status'] = 'PaRes Received';
        if(isset($data['error_text'])) $attributes['error_text'] = $data['error_text'];
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
    
    public static function persistAfterRefund($requestData, $responseData)
    {
        $attributes = array(
            'trackid' => $responseData['trackid'],
            'paymentid' => $responseData['paymentid'],
            'action' => $requestData['action'],
            'status' => $responseData['result'],
            'error_text' => $responseData['error_text']);

        return static::create($attributes);
    }

    public static function persistAfterRefundError($id, $paymentid, array $error)
    {
        $attributes = array(
            'trackid' => $id,
            'paymentid' => $paymentid,
            'error_code' => $error['code'],
            'error_service' => $error['service'],
            'error_text' => $error['text'],
            'action' => HdfcGatewayAction::REFUND,
            'result' => HdfcGatewayResult::FAIL_REFUND);

        return static::createOrFail($attributes);
    }

    public function persisAfterCapture($data)
    {
        ;
    }

    public static function retrieve($id)
    {
        return static::where('trackid','=',$id)->firstOrFail();
    }
}