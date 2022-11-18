<?php

namespace RZP\Models\Checkout\Order;

use App;
use DB;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Partitions;
use RZP\Exception;
use RZP\Base\Repository as BaseRepository;
use RZP\Constants\Mode;
use RZP\Constants\Table;
use RZP\Error\ErrorCode;
use Database\Connection;
use RZP\Trace\TraceCode;
use Illuminate\Database\QueryException;
use RZP\Models\Base\Traits\PartitionRepo;

class Repository extends BaseRepository
{
    use PartitionRepo;

    protected $entity = 'checkout_order';

    protected $mode;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->mode = $app['rzp.mode'];

        parent::__construct();
    }

    protected function getPartitionStrategy() : string
    {
        return Partitions::DAILY;
    }

    protected function getDesiredOldPartitionsCount() : int
    {
        return 7;
    }
}
