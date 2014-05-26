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
            'enroll_result' => $response['enroll_result'],
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
            'enroll_result' => HdfcGatewayResult::FAIL_ENROLLED);

        return static::createOrFail($attributes);
    }

    public function persistAfterCCAuth($data)
    {
        $this->attributes = array(
            'status' => 'CC Authed',
            'auth_result' => $data['result'],
            'ref' => $data['ref'],
            'auth' => $data['auth'],
            'avr' => $data['avr'],
            'paymentid' => $data['tranid'],
            'postdate' => $data['postdate']);

        $this->save();
    }

    public function persistAfterDCAuth($data)
    {
        $this->attributes = array(
            'status' => 'PaRes Received',
            'auth_result' => $data['result'],
            'ref' => $data['ref'],
            'auth' => $data['auth'],
            'avr' => $data['avr'],
            'postdate' => $data['postdate']);
        
        $this->save();
    }

    public function persistAfterDCAuthError($error)
    {
        $this->attributes = array(
            'status' => 'PaRes Error',
            'error_code' => $error['code'],
            'error_service' => $error['service'],
            'error_text' => $error['text']);
        
        $this->save();
    }
    
    public static function persistAfterSupportTxn($requestData, $responseData)
    {
        $attributes = array(
            'trackid' => $responseData['trackid'],
            'paymentid' => $responseData['tranid'],
            'action' => $requestData['action'],
            'status' => $responseData['result'],
            'ref' => $responseData['ref'],
            'auth' => $responseData['auth'],
            'avr' => $responseData['avr'],
            'postdate' => $responseData['postdate']);

        return static::create($attributes);
    }

    public static function persistAfterSupportTxnError($id, $paymentid, array $error, $type)
    {
        $action = '';
        $status = '';
        switch($type)
        {
            case 'refund':
                $action = HdfcGatewayAction::REFUND;
                $status = HdfcGatewayResult::NOT_REFUNDED;
                break;
            case 'capture':
                $action = HdfcGatewayAction::CAPTURE;
                $status = HdfcGatewayResult::NOT_CAPTURED;
                break;
        }

        $attributes = array(
            'trackid' => $id,
            'paymentid' => $paymentid,
            'error_code' => $error['code'],
            'error_text' => $error['result'],
            'action' => $action,
            'status' => $status);

        return static::createOrFail($attributes);
    }

    public static function retrieve($id)
    {
        return static::where('trackid','=',$id)->firstOrFail();
    }

    public function getTrackId()
    {
        return $this->getAttribute('trackid');
    }
}