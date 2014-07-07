<?php

namespace Gateway\HdfcGateway;

class HdfcGatewayDal extends \Models\Base\DAL
{
    protected $table = 'hdfc';

    protected $primaryKey = 'transactionid';

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
            'transactionid' => $response['paymentid'],
            'action' => $request['action'],
            'enroll_result' => $response['enroll_result'],
            'status' => HdfcGatewayStatus::ENROLLED,
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
            'enroll_result' => HdfcGatewayResult::FAIL_ENROLLED,
            'status' => HdfcGatewayStatus::ENROLL_FAILED);

        return static::createOrFail($attributes);
    }

    public function persistAfterAuthNotEnrolled($data)
    {
        $this->attributes = array(
            'status' => HdfcGatewayStatus::AUTH,
            'auth_result' => $data['result'],
            'ref' => $data['ref'],
            'auth' => $data['auth'],
            'avr' => $data['avr'],
            'transactionid' => $data['tranid'],
            'postdate' => $data['postdate']);

        $this->save();
    }

    public function persistAfterAuthEnrolled($data)
    {
        $this->attributes = array(
            'status' => HdfcGatewayStatus::AUTH,
            'auth_result' => $data['result'],
            'ref' => $data['ref'],
            'auth' => $data['auth'],
            'avr' => $data['avr'],
            'postdate' => $data['postdate']);

        $this->save();
    }

    public function persistAfterAuthNotEnrolledError($error)
    {
        $this->attributes = array(
            'status' => HdfcGatewayStatus::AUTH_NOT_ENROLL_FAILED,
            'error_code' => $error['code'],
            'error_service' => $error['service'],
            'error_text' => $error['text']);

        $this->save();
    }

    public function persistAfterAuthEnrolledError($error)
    {
        $this->attributes = array(
            'status' => HdfcGatewayStatus::AUTH_ENROLL_FAILED,
            'error_code' => $error['code'],
            'error_service' => $error['service'],
            'error_text' => $error['text']);

        $this->save();
    }

    public static function persistAfterSupportTxn($requestData, $responseData)
    {
        $attributes = array(
            'trackid' => $responseData['trackid'],
            'transactionid' => $responseData['tranid'],
            'action' => $requestData['action'],
            'status' => $responseData['result'],
            'ref' => $responseData['ref'],
            'auth' => $responseData['auth'],
            'avr' => $responseData['avr'],
            'postdate' => $responseData['postdate']);

        return static::create($attributes);
    }

    public static function persistAfterSupportTxnError($id, $transactionid, array $error, $type)
    {
        $action = '';
        $status = '';
        switch($type)
        {
            case 'refund':
                $action = HdfcGatewayAction::REFUND;
                $status = HdfcGatewayStatus::REFUND_FAILED;
                break;
            case 'capture':
                $action = HdfcGatewayAction::CAPTURE;
                $status = HdfcGatewayStatus::CAPTURE_FAILED;
                break;
        }

        $attributes = array(
            'trackid' => $id,
            'transactionid' => $transactionid,
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