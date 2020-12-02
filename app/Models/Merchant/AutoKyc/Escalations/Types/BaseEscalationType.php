<?php


namespace RZP\Models\Merchant\AutoKyc\Escalations\Types;

use App;
use Illuminate\Foundation\Application;
use RZP\Base\RepositoryManager;

abstract class BaseEscalationType
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * Repository manager instance
     * @var RepositoryManager
     */
    protected $repo;


    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];
    }

    public abstract function triggerEscalation($merchants, string $type, int $level);
}
