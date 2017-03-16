<?php

namespace RZP\Models\Workflow\Action\Differ;

use RZP\Error;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Base\EsDao;
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

        return $action->getId();
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

        try
        {
            if ($this->config->get('database.es_workflow_action_mock') === false)
            {
                $this->esDao->storeAdminEvent(
                    strtolower($this->baseIndex), self::ES_TYPE, $action->toArray()
                );
            }
        }
        catch(\Exception $e)
        {
            $this->trace->warning(TraceCode::HEIMDALL_ACTION_LOG_FAIL, ['msg' => $e]);
        }

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

    public function execute(string $id)
    {
        $esResponse = $this->esDao->search(strtolower($this->baseIndex), self::ES_TYPE, $id);

        $esObject = $esResponse[0]['_source'];

        $esObject[Entity::PAYLOAD]['action_id'] = $id;

        $response = $this->makeRequest($esObject[Entity::METHOD],
                                       $esObject[Entity::URL],
                                       $esObject[Entity::HEADERS],
                                       $esObject[Entity::PAYLOAD]);

        return $response;
    }

    public function makeRequest($method, $url, $headers, $content)
    {
        $factory = $this->app->make('httplug.message_factory.default');

        $req = $factory->createRequest($method, $url, $headers, json_encode($content));

        $response = false;

        try
        {
            $response = $this->createHttpClient()->sendRequest($req);
        }
        catch (ClientErrorException $e)
        {
            $errorMessage = 'Client error: '. $e->getResponse()->getReasonPhrase();

            $this->trace->info(
                TraceCode::HEIMDALL_REQUEST_FOWARD_FAIL,
                [
                    'content'   => $content,
                    'url'       => $url,
                    'exception' => $errorMessage,
                ]);
        }
        catch (ServerErrorException $e)
        {
            $errorMessage = 'Server error: '. $e->getResponse()->getReasonPhrase();

            $this->trace->info(
                TraceCode::HEIMDALL_REQUEST_FOWARD_FAIL,
                [
                    'content'   => $content,
                    'url'       => $url,
                    'exception' => $errorMessage,
                ]);
        }
        catch (HttpException $e)
        {
            $errorMessage = 'Some error occurred: '. $e->getResponse()->getReasonPhrase();

            $this->trace->info(
                TraceCode::HEIMDALL_REQUEST_FOWARD_FAIL,
                [
                    'content'   => $content,
                    'url'       => $url,
                    'exception' => $errorMessage,
                ]);
        }

        $this->trace->info(
            TraceCode::HEIMDALL_REQUEST_FOWARD_FAIL,
            [
                'response'   => $response
            ]);

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
}
