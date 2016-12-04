<?php

namespace RZP\Listeners;

use App;
use RZP\Events\AuditLogEntry;
use Illuminate\Foundation\Bus\DispatchesJobs;
use RZP\Models\Base\EsDao;
use RZP\Constants\Mode;
USE RZP\Trace\TraceCode;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class AuditLogListener
{
    use DispatchesJobs;

    protected $app;

    /**
     * Event being fired
     * @var string
     */
    protected $event;

    /**
     * Laravel Events instance
     * @var
     */
    protected $events;

    protected $queue;

    protected $trace;

    protected $esDao;

    protected $baseIndex;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->event = $this->app['events'];

        $this->trace = $this->app['trace'];

        $this->queue = $this->app['queue'];

        $this->esDao = new EsDao();

        $config = $this->app['config'];

        $mode = empty($this->app['rzp.mode']) ? Mode::TEST : $this->app['rzp.mode'];

        $this->baseIndex = $config->get('database.es_heimdall')[$mode];
    }


    /**
     * Handle the event.
     *
     * @param  AuditLogEntry  $event
     * @return void
     */
    public function handle(AuditLogEntry $event)
    {
        // strtolower since index names must be lowercase
        $index = strtolower($this->baseIndex);

        $type = 'audit_log';

        $fields = [];

        $admin = $event->admin;

        // Whitelisting admin props we need to log
        $fields['admin'] = [
            'id'        => $admin['id'],
            'username'  => $admin['username'] ?? 'NA',
            'email'     => $admin['email'],
            'name'      => $admin['name'],

            'org_id'    => $admin['org_id'],

            'employee_code'     => $admin['employee_code'],
            'branch_code'       => $admin['branch_code'],
            'department_code'   => $admin['department_code'],
            'supervisor_code'   => $admin['supervisor_code'],
            'location_code'     => $admin['location_code'],

            'roles'     => [],
            'groups'    => [],
        ];

        // Roles
        if (isset($admin['roles']))
        {
            $fields['admin']['roles'] = array_map(function ($role)
            {
                return $role['name'];
            }, $admin['roles']);
        }

        if (isset($admin['groups']))
        {
            $fields['admin']['groups'] = array_map(function ($role)
            {
                return $role['name'];
            }, $admin['groups']);
        }

        // Event specific
        $fields['category']     = $event->action['category'] ?? null;
        $fields['label']        = $event->action['label'] ?? null;
        $fields['action']       = $event->action['action'] ?? null;
        $fields['description']  = $event->description ?? null;

        // Entity specific
        $fields['entity']       = $event->entity ?? null;

        // Meta
        $fields['user_agent']   = \Request::header('User-Agent') ?? null;
        $fields['ip_address']   = \Request::ip() ?? null;
        $fields['created_at']   = time();

        // add action specific properties. e.g. failed_payment_attempt in case of
        // login failure
        $fields['action_properties'] = $event->customProperties ?? null;

        // org_id, mode, etc.
        $fields['extra'] = [
            'org_id' => $event->admin['org_id']
        ];

        $fields['internal'] = [
            // firing() - Gets the event that is currently firing
            'event' => $this->event->firing(),

            // We can add more info like caller class/function/line,
            // environment, etc.
        ];

        if ($this->app['is_es_enabled'])
        {
            // $fields['extra'] = ...;
            $this->esDao->storeAdminEvent(
                $index, $type, $fields
            );
        }

        $this->trace->info(TraceCode::HEIMDALL_EVENT_RECORD, ['event' => $event, 'fields' => $fields, 'type' => $type]);
    }
}
