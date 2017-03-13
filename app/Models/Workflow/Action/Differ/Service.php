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

        $function = 'create' .$input['type'] .'Action';

        $E = $this->$function($action);

        return $E->toArrayPublic();
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

    protected function createMakerAction(Entity $action)
    {
        $entity = $action[Entity::ENTITY_NAME];

        $entityId = $action[Entity::ENTITY_ID];

        $oldE = $this->repo->$entity->findByPublicId($entityId);

        $newE = clone $oldE;

        $newE->edit($action[Entity::PAYLOAD]);

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

        return $newE;
    }

    protected function createDiff(array $oldE, array $newE)
    {
        $diff = [];

        $keys = array_keys($oldE);

        foreach ($keys as $key)
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

        $esResponse[Entity::PAYLOAD]['action_id'] = $id;

        $response = $this->makeRequest($esResponse['method'],
                                       $esResponse[Entity::URL],
                                       $esResponse[Entity::HEADERS],
                                       $esResponse[Entity::PAYLOAD]);

        return $response;
    }

    public function makeRequest($method, $url, $headers, $content)
    {
        $factory = $this->app->make('httplug.message_factory.default');

        $req = $factory->createRequest($method, $url, $headers, $content);

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
                    'action_id' => $content['action_id'],
                    'exception' => $errorMessage,
                ]);

            return false;
        }
        catch (ServerErrorException $e)
        {
            $errorMessage = 'Server error: '. $e->getResponse()->getReasonPhrase();

            $this->trace->info(
                TraceCode::HEIMDALL_REQUEST_FOWARD_FAIL,
                [
                    'action_id' => $content['action_id'],
                    'exception' => $errorMessage,
                ]);

            return false;
        }
        catch (HttpException $e)
        {
            $errorMessage = 'Some error occurred: '. $e->getResponse()->getReasonPhrase();

            $this->trace->info(
                TraceCode::HEIMDALL_REQUEST_FOWARD_FAIL,
                [
                    'action_id' => $content['action_id'],
                    'exception' => $errorMessage,
                ]);

            return false;
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
}
