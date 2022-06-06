<?php
namespace RZP\Models\CapitalVirtualCards;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use ApiResponse;

class Validator  extends Base\Validator{

    protected $core;

    public function __construct($entity = null)
    {
        parent::__construct($entity);
        $this->core = new Core();
    }

    public function validateSession($request){
        if(empty($request->session()->get('sessionId'))){
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_VALIDATION_FAILURE);
        }
    }
}
?>
