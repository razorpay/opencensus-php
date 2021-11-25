<?php

namespace App\Merchant;

use Mail;
use Uuid;
use App\Base;
use App\User;
use App\Invitation;

class Entity extends Base\Entity
{
    public $incrementing = false;

    protected $table = 'merchants';

    protected $hidden = ['remember_token'];

    protected $fillable = array(
        'id',
        'name',
        'email',
        'activated',
        'archived_at',
        'suspended_at'
    );

    const ID_LENGTH = 14;
    const EMAIL     = 'email';

    protected static $generators = array('id');

    const AMEX  = 'AMEX';
    const AEPS  = 'AEPS';
    const DICL  = 'DICL';
    const DISC  = 'DISC';
    const EMI   = 'EMI';
    const JCB   = 'JCB';
    const MAES  = 'MAES';
    const MC    = 'MC';
    const RUPAY = 'RUPAY';
    const VISA  = 'VISA';
    const UNP   = 'UNP';
    const CARD  = 'CARD';
    const UPI   = 'UPI';
    const NETBANKING    = 'NETBANKING';
    const EMANDATE      = 'EMANDATE';
    const WALLET        = 'WALLET';
    const UNKNOWN       = 'UNKNOWN';

    protected static $api_mappings = array(
        'American Express'  =>  self::AMEX,
        'aeps'              =>  self::AEPS,
        'Diners Club'       =>  self::DICL,
        'Discover'          =>  self::DISC,
        'emandate'          =>  self::EMANDATE,
        'JCB'               =>  self::JCB,
        'Maestro'           =>  self::MAES,
        'MasterCard'        =>  self::MC,
        'RuPay'             =>  self::RUPAY,
        'Unknown'           =>  self::UNKNOWN,
        'Visa'              =>  self::VISA,
        'Union Pay'         =>  self::UNP,
        'card'              =>  self::CARD,
        'netbanking'        =>  self::NETBANKING,
        'wallet'            =>  self::WALLET,
        'emi'               =>  self::EMI,
        'upi'               =>  self::UPI,
        'Unknown'           =>  self::UNKNOWN
    );

    public static function getAggregations($data, $mode)
    {
        $data = \DB::table('aggregations')
                    ->where('merchant_id','=',$data['merchant_id'])
                    ->where('resource','=',$data['resource'])
                    ->where('mode','=',$mode)
                    ->first();
        return $data;
    }

    public static function getTransactionAggregations($mode, $sort, $filterTimestamp, $type, $merchantId)
    {
        $data = \DB::table('transactions')
                    ->select(
                        \DB::raw(
                            'transactions.merchant_id,
                            SUM(transactions.amount) as total_amount,
                            SUM(transactions.count) as total_count'
                        )
                    )
                    ->where('transactions.mode', '=', $mode)
                    ->where('transactions.type', '=', $type)
                    ->where('transactions.created_at', '>=', $filterTimestamp)
                    ->where('transactions.merchant_id', '=', $merchantId)
                    ->orderBy($sort, 'DESC')
                    ->get();

        return $data;
    }

    public static function getAllTransactionAggregations($mode, $sort, $count, $filterTimestamp, $type)
    {
        $data = \DB::table('transactions')
                    ->select(
                        \DB::raw(
                            'transactions.merchant_id,
                            SUM(transactions.amount) as total_amount,
                            SUM(transactions.count) as total_count'
                        )
                    )
                    ->where('transactions.mode', '=', $mode)
                    ->where('transactions.type', '=', $type)
                    ->where('transactions.created_at', '>=', $filterTimestamp)
                    ->groupBy('transactions.merchant_id')
                    ->orderBy($sort, 'DESC')
                    ->simplePaginate($count);

        return $data;
    }

    public static function getPaymentAggregations($data, $mode)
    {
        $data = \DB::table('payment_aggregations')
                    ->where('merchant_id','=',$data['merchant_id'])
                    ->where('mode','=',$mode)
                    ->first();
        return $data;
    }

    public static function createAggregations($data, $mode)
    {
        $obj = array(
            'merchant_id'           =>  $data['merchant_id'],
            'total_amount'          =>  0,
            'successful_txn_count'  =>  0,
            'txn_count'             =>  0,
            'created_at'            =>  $data['updated_at'],
            'updated_at'            =>  $data['updated_at'],
            'resource'              =>  $data['resource'],
            'mode'                  =>  $mode
        );

        \DB::table('aggregations')->insert($obj);

        return static::getAggregations($data, $mode);
    }

    public static function createPaymentAggregations($data, $mode)
    {
        $obj = array(
            'merchant_id'           =>  $data['merchant_id'],
            'created_at'            =>  $data['updated_at'],
            'updated_at'            =>  $data['updated_at'],
            'mode'                  =>  $mode
        );

        \DB::table('payment_aggregations')->insert($obj);

        return static::getPaymentAggregations($data, $mode);
    }

    public static function updateAggregations($data, $merchant_details, $mode)
    {
        $obj = array(
            'total_amount'          =>  (int)$data['amount'] + (int)$merchant_details->total_amount,
            'successful_txn_count'  =>  (int)$merchant_details->successful_txn_count + 1,
            'txn_count'             =>  (int)$merchant_details->txn_count + 1,
            'updated_at'            =>  $data['created_at']
        );
        \DB::table('aggregations')
            ->where('merchant_id','=',$data['merchant_id'])
            ->where('resource','=',$data['resource'])
            ->where('mode', '=', $mode)
            ->update($obj);
    }

    public static function updatePaymentAggregations($data, $merchant_details, $mode)
    {
        $obj = [];

        $method = static::$api_mappings[$data['method']];

        $currentCount = $merchant_details->$method;

        $obj[static::$api_mappings[$data['method']]] =  $currentCount + 1;

        if(isset($data['network']))
        {
            $network = static::$api_mappings[$data['network']];

            $obj[static::$api_mappings[$data['network']]] = $currentCount + 1;
        }

        \DB::table('payment_aggregations')
            ->where('merchant_id','=',$data['merchant_id'])
            ->where('mode', '=', $mode)
            ->update($obj);
    }
}
