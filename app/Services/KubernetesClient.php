<?php

namespace RZP\Services;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use Maclof\Kubernetes\Client;
use GuzzleHttp\Client as GuzzleHttpClient;

use Maclof\Kubernetes\Models\Job;
use RZP\Models\Batch as BatchModel;
use RZP\Models\Merchant\Invoice\Core;
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

    /**
     * Constants to use
     */
    const INVOICE = 'invoice';

    const NODE_SELECTOR_HITACHI = 'node-role.kubernetes.io/worker-hitachi-queue';

    protected $batchNodePreference = [
        BatchModel\Type::RECURRING_CHARGE => self::NODE_SELECTOR_HITACHI,
    ];

    /**
     * Maintains the cpu request based on batch type
     * @var array
     */
    protected $nodeCpuRequest = [
        BatchModel\Type::RECONCILIATION => '200m',
        self::INVOICE                   => '100m'
    ];

    /**
     * Maintains the memory request based on batch type
     * @var array
     */
    protected $nodeMemoryRequest = [
        BatchModel\Type::RECONCILIATION => '1024Mi',
        self::INVOICE                   => '150Mi'
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
            $dockerImage .= ":worker-".$this->gitCommitHash;
        }
        // in case environment variable not there make another attempt to read from commit.txt file
        else
        {
            if ($this->commitFilePath !== null && file_exists($this->commitFilePath)) {
                $dockerImage .= ":worker-".file_get_contents($this->commitFilePath);
            }
        }

        return trim($dockerImage);

    }

    /**
     * @param string $mode
     * @param string $batchId
     * @param array $params
     * @param string|null $batchType
     * @return bool
     */
    public function createJob(string $mode, string $batchId, array $params, string $batchType = null) : bool
    {
        try
        {
            // Call the batch service process method directly when kubernetes mock is set to true
            // No need to create Kubernetes job
            if ($this->mock === true)
            {
                $batchService = new BatchService();
                $batchService->process($batchId, $mode, $params);

                return true;
            }

            // Selecting node selector

            if (($batchType !== null) and (array_key_exists($batchType, $this->batchNodePreference) === true))
            {
                $this->nodeSelector = $this->batchNodePreference[$batchType];
            }
            // Create Job Spec
            $jobSpec = $this->generateJobSpec($mode, $batchId, $params, $batchType);

            $job = new Job($jobSpec);

            $httpClient = new GuzzleHttpClient([
                'verify' => $this->caCert,
            ]);

            $this->client = new Client([
                'master'  => $this->masterUrl,
                'token'   => $this->token,
            ], null, $httpClient);

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

                return true;
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

        return false;
    }

    public function createInvoiceJob(string $mode, $year, $month)
    {
        try
        {
            if ($this->mock === true)
            {
                (new Core)->processMerchantInvoice($mode, $year, $month);

                return;
            }

            // Create Job Spec
            $jobSpec = $this->generateInvoiceJobSpec($mode, $year, $month);

            $job = new Job($jobSpec);

            $httpClient = new GuzzleHttpClient([
                'verify' => $this->caCert,
            ]);

            $this->client = new Client([
                'master'  => $this->masterUrl,
                'token'   => $this->token,
            ], null, $httpClient);

            // Set Namespace if provided
            if (($this->namespace !== null) and (file_exists($this->namespace) === true))
            {
                $this->trace->info(
                    TraceCode::KUBERNETES_INVOICE_JOB_NAMESPACE,
                    [
                        'mode'           => $mode,
                        'year'           => $year,
                        'month'          => $month,
                        'namespace'      => file_get_contents($this->namespace),
                    ]);

                $this->client->setNamespace(file_get_contents($this->namespace));
            }

            if ($this->client->jobs()->exists($job->getMetadata('name')))
            {
                $this->trace->error(TraceCode::KUBERNETES_INVOICE_JOB_EXISTS);
            }
            else
            {
                $response = $this->client->jobs()->create($job);

                $this->trace->info(TraceCode::KUBERNETES_INVOICE_JOB_CREATED,
                    [
                        'kubernetes_response'   => $response,
                    ]);
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::KUBERNETES_INVOICE_JOB_ERROR);
        }
    }

    private function generateJobSpec(string $mode, string $batchId, array $params, string $batchType = null)
    {
        $batchName = $params['job_name'] ?? $batchId;

        $metaName = strtolower('batch-' . $batchName);

        $dockerImage = $this->getDockerImage();

        $this->nodeSelector = $params['node_selector'] ?? $this->nodeSelector;

        $cpuRequest = $this->nodeCpuRequest[$batchType] ?? '100m';

        $memoryRequest = $this->nodeMemoryRequest[$batchType] ?? '150Mi';

        $jobSpec = [
            'metadata' => [
                'name' => $metaName,
                'labels' => [
                    'name' => 'batch-job',
                    'bu'   => 'all'
                ]
            ],
            'spec' => [
                'template' => [
                    'metadata' => [
                        'labels' => [
                            'name' => 'batch-job',
                            'bu'   => 'all'
                        ],
                        'annotations' => [
                            'iam.amazonaws.com/role' => $this->iamRole,
                            'k8s.rzp.io/logger' => 'efk',
                            'k8s.rzp.io/logs' => 'true',
                            'batch_job_type' => $batchType ?? '',
                            'cluster-autoscaler.kubernetes.io/safe-to-evict' => 'false',
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
                        'dnsPolicy' => 'ClusterFirst',
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

    private function generateInvoiceJobSpec(string $mode, $year, $month)
    {
        $currentTime = Carbon::now(Timezone::IST)->getTimeStamp();

        $metaName = strtolower('batch-' . 'merchantInvoice' . $currentTime);

        $dockerImage = $this->getDockerImage();

        $this->nodeSelector = $params['node_selector'] ?? $this->nodeSelector;

        $cpuRequest = $this->nodeCpuRequest[self::INVOICE] ?? '100m';

        $memoryRequest = $this->nodeMemoryRequest[self::INVOICE] ?? '150Mi';

        $jobSpec = [
            'metadata' => [
                'name' => $metaName,
                'labels' => [
                    'name' => 'batch-job',
                    'bu'   => 'all'
                ]
            ],
            'spec' => [
                'template' => [
                    'metadata' => [
                        'labels' => [
                            'name' => 'batch-job',
                            'bu'   => 'all'
                        ],
                        'annotations' => [
                            'iam.amazonaws.com/role' => $this->iamRole,
                            'k8s.rzp.io/logger' => 'efk',
                            'k8s.rzp.io/logs' => 'true',
                            'batch_job_type' => $batchType ?? '',
                            'cluster-autoscaler.kubernetes.io/safe-to-evict' => 'false',
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
                                'args' => ["merchantInvoice-job", "merchantinvoice:process", $mode, $year, $month],
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
                        'dnsPolicy' => 'ClusterFirst',
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
