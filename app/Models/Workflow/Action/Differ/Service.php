<?php

namespace RZP\Models\Workflow\Action\Differ;

use RZP\Error;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Base\EsDao;
use RZP\Events\DifferEvent;
use Http\Client\Common\PluginClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Client\Exception\HttpException;
use Http\Client\Common\Plugin\ErrorPlugin;
use Http\Client\Common\Exception\ClientErrorException;
use Http\Client\Common\Exception\ServerErrorException;


class Service extends Base\Service
{
    protected $esDao;

    protected $baseIndex;

    protected $config;

    const ES_TYPE = 'action';

    const SKIP_DIFF_FIELDS = [
        'created_at',
        'updated_at',
    ];

    public function __construct()
    {
        parent::__construct();

        $this->esDao = new EsDao();

        $this->config = $this->app['config'];

        $mode = empty($this->app['rzp.mode']) ? Mode::TEST : $this->app['rzp.mode'];

        $this->baseIndex = $this->config->get('database.es_workflow_action')[$mode];
    }

    public function create(array $input)
    {
        $action = (new Entity)->generateId();

        $action->build($input);

        $action[Entity::CREATED_AT] = Carbon::now('Asia/Kolkata')->timestamp;

        $function = $input['type'] .'Action';

        $action = $this->$function($action);

        return [ 'action_id' => $action->getId()];
    }

    public function fetchDiffById(string $id)
    {
        $esResponse = $this->esDao->search(strtolower($this->baseIndex), self::ES_TYPE, $id);

        if ($esResponse === null)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACTION_NOT_FOUND);
        }

        $diff = $esResponse[0]['_source'][Entity::DIFF];

        return $diff;
    }

    public function fetchRequest(string $id)
    {
        $esResponse = $this->esDao->search(strtolower($this->baseIndex), self::ES_TYPE, $id);

        if ($esResponse === null)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACTION_NOT_FOUND);
        }

        $esObject = $esResponse[0]['_source'];

        $controller = $esObject[Entity::CONTROLLER];

        $controllerSplit = explode('@', $controller);

        return [
            Entity::ENTITY_ID     => $esObject[Entity::ENTITY_ID],
            Entity::PAYLOAD       => $esObject[Entity::PAYLOAD],
            Entity::CONTROLLER    => $controllerSplit[0],
            Entity::FUNCTION_NAME => $controllerSplit[1],
        ];
    }

    public function saveToES(array $action)
    {
        try
        {
            if ($this->config->get('database.es_workflow_action_mock') === false)
            {
                $this->esDao->storeAdminEvent(strtolower($this->baseIndex), self::ES_TYPE, $action);
            }
        }
        catch(\Exception $e)
        {
            $this->trace->warning(TraceCode::HEIMDALL_ACTION_LOG_FAIL, ['msg' => $e]);
        }
    }

    public function makeRequest($method, $url, $headers, $content)
    {
        $factory = $this->app->make('httplug.message_factory.default');

        $req = $factory->createRequest($method, $url, $headers, json_encode($content));

        $response = null;

        try
        {
            $response = $this->createHttpClient()->sendRequest($req);
        }
        catch (ClientErrorException $e)
        {
            $response = $e->getResponse();
        }
        catch (ServerErrorException $e)
        {
            $response = $e->getResponse();
        }
        catch (HttpException $e)
        {
            $response = $e->getResponse();
        }

        return $response;
    }

    protected function createHttpClient()
    {
        // Plugin to get error-exceptions from responses of httpClient
        $errorPlugin = new ErrorPlugin();

        // PluginClient is the decorator around the httpClient that manages plugins
        // HttpClientDiscovery finds a suitable installed client that -
        // extends HttpClient (in this case Guzzle6 client)
        $pluginClient = new PluginClient(
            HttpClientDiscovery::find(),
            [$errorPlugin]
        );

        return $pluginClient;
    }

    protected function makerAction(Entity $action)
    {
        $entity = $action->getEntityName();

        $entityId = $action->getEntityId();

        $oldE = $this->repo->$entity->findByPublicId($entityId);

        $newE = clone $oldE;

        $validator = EntityValidator::getValidator($action->getRoute());

        $newE = $newE->edit($action->getPayload(), $validator);

        $diff = $this->createDiff($oldE->toArray(), $newE->toArray());

        $action->setDiff($diff);

        event(new DifferEvent($action->toArray()));

        return $action;
    }

    protected function createDiff(array $oldE, array $newE)
    {
        $diff = [];

        $keys = array_keys($oldE);

        $diffKeys = array_diff($keys, self::SKIP_DIFF_FIELDS);

        foreach ($diffKeys as $key)
        {
            if ($oldE[$key] !== $newE[$key])
            {
                $diff['old'][$key] = $oldE[$key];

                $diff['new'][$key] = $newE[$key];
            }
        }

        return $diff;
    }
}
