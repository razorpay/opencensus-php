<?php

namespace RZP\Listeners;

use App;
use RZP\Events\AuditLogEntry;
use RZP\Models\Workflow\Helper;
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

    protected $config;

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

        $this->esDao = new EsDao();

        $this->config = $this->app['config'];

        $mode = empty($this->app['rzp.mode']) ? Mode::TEST : $this->app['rzp.mode'];

        $this->baseIndex = $this->config->get('database.es_audit')[$mode];
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
        $admin = $event->admin;

        $index = strtolower($this->baseIndex);

        $type = 'audit_log';

        $fields = [];

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
        $fields['user_agent']   = \Request::header('X-User-Agent') ?? \Request::header('User-Agent') ?? null;
        $fields['ip_address']   = \Request::header('X-IP-Address') ?? \Request::ip() ?? null;

        $fields['created_at']   = time();

        // org_id, mode, etc.
        $fields['extra'] = [
            'org_id' => $event->admin['org_id']
        ];

        // add action specific properties. e.g. failed_payment_attempt in case of
        // login failure

        $customProperties = $event->customProperties ?? null;

        // move the entity object one level up
        // refer audit log spec for why this is needed
        if (($customProperties !== null ) and (is_array($customProperties) === true))
        {
            if (isset($customProperties['entity']))
            {
                $fields['entity'] = $customProperties['entity'];
            }
            else
            {
                $fields['extra']['custom'] = $customProperties;
            }
        }

        $fields['internal'] = [
            'event' => get_class($event),

            // We can add more info like caller class/function/line,
            // environment, etc.
        ];

        try
        {
            if ($this->config->get('database.es_audit_mock') === false)
            {
                // $fields['extra'] = ...;
                $this->esDao->storeAdminEvent(
                    $index, $type, $fields
                );
            }
        }
        catch (\Exception $e)
        {
            $this->trace->warning(TraceCode::HEIMDALL_AUDIT_LOG_FAIL, ['msg' => $e]);
        }

        $this->trace->info(TraceCode::HEIMDALL_EVENT_RECORD, ['event' => $event, 'fields' => $fields, 'type' => $type]);
    }
}
