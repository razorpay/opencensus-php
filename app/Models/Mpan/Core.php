<?php


namespace RZP\Models\Mpan;

use Illuminate\Support\Facades\App;
use RZP\Error\ErrorCode;
use RZP\Models\Base;

class Core extends Base\Core
{

    protected $mutex;

    /**
     * Core constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->mutex = App::getFacadeRoot()['api.mutex'];
    }

    public function create(array $input)
    {
        $mpan = (new Entity)->build($input);

        $this->repo->saveOrFail($mpan);

        return $mpan;
    }

    public function issueMpans(array $input)
    {
        /*
         * Need the mutex to prevent race condition where multiple requests to issue mpan can arrive at same time
         * We dont want to end up mis-allocating to different requesters.
         */
        return $this->mutex->acquireAndRelease(Constants::MPAN_ISSUE_MUTEX_RESOURCE,
            function() use ($input)
            {
                /*
                 * Transaction block is needed here to ensure atomicity of the issue_mpan operation
                 */
                return  $this->repo->mpan->transaction(function () use ($input)
                {
                    $unassignedMpansCollection = $this->repo->mpan->fetchUnassignedMpansForNetwork($input['network'],
                                                                                                   $input['count']);

                    $assignedMpansCollection = $this->repo->mpan->assignMpansToMerchant($unassignedMpansCollection);

                    return $assignedMpansCollection;
                });
            },
            Constants::MPAN_ISSUE_MUTEX_TTL,
            ErrorCode::BAD_REQUEST_ANOTHER_MPAN_ISSUE_IN_PROGRESS
            );
    }
}
