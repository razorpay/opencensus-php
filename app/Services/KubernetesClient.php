<?php

namespace RZP\Services;

use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use Maclof\Kubernetes\Client;
use Maclof\Kubernetes\Models\Job;
use RZP\Models\Batch as BatchModel;
use RZP\Services\Batch as BatchService;

class KubernetesClient
{
    /**
     * Credentials to talk to Kubernetes
     */
    protected $masterUrl;
    protected $caCert;
    protected $token;
    protected $imagePath;
    protected $namespace;
    protected $iamRole;
    protected $nodeSelector;
    protected $logPath;
    protected $mock;
    protected $gitCommitHash;
    protected $appMode;
    protected $appEnv;

    protected $config;
    protected $client;

    protected $commitFilePath = 'commit.txt';

    /**
     * Trace instance used for tracing
     * @var Trace
     */
    protected $trace;

    protected $razorx;

    protected $merchant;

    const NODE_SELECTOR_HITACHI = 'node-role.kubernetes.io/worker-hitachi-queue';

    protected $batchNodePreference = [
        BatchModel\Type::RECURRING_CHARGE => self::NODE_SELECTOR_HITACHI,
    ];

    /**
     * Maintains the cpu request based on batch type
     * @var array
     */
    protected $batchNodeCpuRequest = [
        BatchModel\Type::RECONCILIATION => '200m',
    ];

    /**
     * Maintains the memory request based on batch type
     * @var array
     */
    protected $batchNodeMemoryRequest = [
        BatchModel\Type::RECONCILIATION => '1024Mi',
    ];

    public function __construct($app)
    {
        $this->trace        = $app['trace'];
        $this->config       = $app['config']->get('applications.kubernetes_client');

        $this->masterUrl        = $this->config['cluster_url'];
        $this->caCert           = $this->config['ca_cert'];
        $this->token            = $this->config['token'];
        $this->imagePath        = $this->config['image_path'];
        $this->namespace        = $this->config['namespace'];
        $this->iamRole          = $this->config['iam_role'];
        $this->nodeSelector     = $this->config['node_selector'];
        $this->logPath          = $this->config['log_path'];
        $this->mock             = $this->config['mock'];
        $this->gitCommitHash    = $this->config['git_commit_hash'];
        $this->appMode          = $this->config['app_mode'];
        $this->appEnv           = $this->config['app_env'];

        $this->commitFilePath = public_path($this->commitFilePath);

    }

    public function getDockerImage()
    {
        $dockerImage = $this->imagePath;

        // Read the latest commit id from the environment variable
        if ($this->gitCommitHash !== false)
        {
            $dockerImage .= ":".$this->gitCommitHash;
        }
        // in case environment variable not there make another attempt to read from commit.txt file
        else
        {
            if ($this->commitFilePath !== null && file_exists($this->commitFilePath)) {
                $dockerImage .= ":".file_get_contents($this->commitFilePath);
            }
        }

        return trim($dockerImage);

    }

    public function createJob(string $mode, string $batchId, array $params, string $batchType = null)
    {
        try
        {
            // Call the batch service process method directly when kubernetes mock is set to true
            // No need to create Kubernetes job
            if ($this->mock === true)
            {
                $batchService = new BatchService();
                $batchService->process($batchId, $mode, $params);

                return;
            }

            // Selecting node selector

            if (($batchType !== null) and (array_key_exists($batchType, $this->batchNodePreference) === true))
            {
                $this->nodeSelector = $this->batchNodePreference[$batchType];
            }
            // Create Job Spec
            $jobSpec = $this->generateJobSpec($mode, $batchId, $params, $batchType);

            $job = new Job($jobSpec);

            $this->client = new Client([
                'master'  => $this->masterUrl,
                'ca_cert' => $this->caCert,
                'token'   => $this->token,
            ]);

            // Set Namespace if provided
            if ($this->namespace !== null and file_exists($this->namespace))
            {
                $this->trace->info(
                    TraceCode::KUBERNETES_BATCH_NAMESPACE,
                    [
                        BatchModel\Entity::ID   => $batchId,
                        'namespace'   => file_get_contents($this->namespace),
                    ]);
                $this->client->setNamespace(file_get_contents($this->namespace));
            }

            if ($this->client->jobs()->exists($job->getMetadata('name')))
            {

                $this->trace->error(
                    TraceCode::KUBERNETES_BATCH_JOB_EXISTS,
                    [
                        BatchModel\Entity::ID   => $batchId,
                    ]);

            }
            else
            {
                $response = $this->client->jobs()->create($job);

                $this->trace->info(
                    TraceCode::KUBERNETES_BATCH_JOB_CREATED,
                    [
                        BatchModel\Entity::ID   => $batchId,
                        'kubernetes_response'   => $response,
                    ]);
            }

        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::KUBERNETES_BATCH_JOB_ERROR,
                [
                    BatchModel\Entity::ID   => $batchId,
                ]);
        }

    }

    private function generateJobSpec(string $mode, string $batchId, array $params, string $batchType = null)
    {
        $batchName = $params['job_name'] ?? $batchId;

        $metaName = strtolower('batch-' . $batchName);

        $dockerImage = $this->getDockerImage();

        $this->nodeSelector = $params['node_selector'] ?? $this->nodeSelector;

        $cpuRequest = $this->batchNodeCpuRequest[$batchType] ?? '100m';

        $memoryRequest = $this->batchNodeMemoryRequest[$batchType] ?? '150Mi';

        $jobSpec = [
            'metadata' => [
                'name' => $metaName,
                'labels' => [
                    'name' => 'batch-job',
                ]
            ],
            'spec' => [
                'template' => [
                    'metadata' => [
                        'labels' => [
                            'name' => 'batch-job',
                        ],
                        'annotations' => [
                            'iam.amazonaws.com/role' => $this->iamRole,
                            'k8s.rzp.io/logger' => 'efk',
                            'k8s.rzp.io/logs' => 'true',
                            'batch_job_type' => $batchType ?? '',
                        ]
                    ],
                    'spec' => [
                        'containers' => [
                            [
                                'envFrom' => [
                                    [
                                        'secretRef' => [
                                            'name' => 'aws-secret'
                                        ]
                                    ]
                                ],
                                'env' => [
                                    [
                                        'name' => 'APP_MODE',
                                        'value' => $this->appMode
                                    ]
                                ],
                                'name'  => 'batch',
                                'image' => $dockerImage,
                                'resources' => [
                                    'requests' => [
                                        'cpu' => $cpuRequest,
                                        'memory' => $memoryRequest
                                    ],
                                    'limits' => [
                                        'cpu' => '500m',
                                        'memory' => '2048Mi'
                                    ]
                                ],
                                'livenessProbe' => [
                                    'exec' => [
                                        'command' => ["cat", $this->commitFilePath]
                                    ],
                                    'initialDelaySeconds' => 180,
                                    'periodSeconds' => 2,
                                    'successThreshold' => 1
                                ],
                                'readinessProbe' => [
                                    'exec' => [
                                        'command' => ["cat", $this->commitFilePath]
                                    ],
                                    'initialDelaySeconds' => 180,
                                    'periodSeconds' => 2,
                                    'successThreshold' => 1
                                ],
                                'imagePullPolicy' => 'IfNotPresent',
                                'args' => ["batch-job", "batch:process", $batchId, $mode],
                                'backoffLimit' => 4,
                                'volumeMounts' => [
                                    [
                                        'name' => 'trace',
                                        'mountPath' => '/app/storage/logs/'
                                    ]
                                ],
                            ],
                        ],
                        'volumes' => [
                            [
                                'name' => 'trace',
                                'hostPath' => [
                                    'path' => $this->logPath,
                                    'type' => '',
                                ]
                            ]
                        ],
                        'restartPolicy' => 'Never',
                        'dnsPolicy' => 'Default',
                        'nodeSelector' => [
                            $this->nodeSelector => ''
                        ],
                        'imagePullSecrets' => [
                            [
                                'name' => 'registry',
                            ]
                        ],
                    ],
                ],
            ],
        ];

        return $jobSpec;
    }

}
