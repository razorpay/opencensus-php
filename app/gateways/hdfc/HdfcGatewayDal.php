<?php

namespace Gateway\Hdfc;

class HdfcGatewayDal extends \Eloquent
{
	protected $fillable = array(
		);

	protected $guarded = array();

	public function transaction()
	{
		return $this->belongsTo('Transaction', 'trackid', 'id');
	}
}