<?php

namespace Models\Base;

use DB;
use Illuminate\Support\Facades\App;

class Repository extends \Razorpay\Spine\Repository
{
    protected $db;

    protected $auth;

    public function __construct()
    {
        parent::__construct();

        $app = App::getFacadeRoot();

        $this->auth = $app['basicauth'];
    }

    public function findOrFailPublic($id, $columns = array('*'))
    {
        $repo = $this->repo;

        return $repo::findOrFailPublic($id, $columns);
    }

    protected function processDbQueryFailure($operation, $attributes = null)
    {
        $e = $this->getExceptionDataArray($operation, $attributes);

        $this->throwException($e);
    }

    protected function getExceptionDataArray($operation, $attributes = null)
    {
        $e = array(
                'model' => get_class($this),
                'operation' => $operation,
                'attributes' => $attributes);

        return $e;
    }

    protected function throwException(array $e)
    {
        throw new Exception\DbQueryException($e);
    }
}
