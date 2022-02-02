<?php

namespace RZP\Console\Commands;

use App;
use Illuminate\Console\Command;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Services\Kafka as KafkaService;
use RZP\Services\Kafka\Utils\Constants;

class KafkaConsumerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kafka-consumer:consume {--topics=*} {--consumer=} {--groupId=} {--mode=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kafka general consumer in php';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info("starting kafka general consumer");

        $app = App::getFacadeRoot();

        $trace = $app['trace'];

        $options = $this->options();

        if (isset($options[Constants::MODE]) === false)
        {
            $options[Constants::MODE] = Mode::LIVE;
        }

        (new KafkaService\Utils\CommandValidator())->validateOptions($options);

        $options       = new KafkaService\Utils\CommandOptions($options);
        $topics        = $options->getTopics();
        $groupId       = $options->getGroupId();
        $consumerClass = $options->getConsumer();
        $mode          = $options->getMode();

        $trace->info(TraceCode::KAFKA_CONSUMER_COMMAND_INIT,
                     [
                         Constants::TOPICS   => $topics,
                         Constants::GROUP_ID => $groupId,
                         Constants::CONSUMER => $consumerClass,
                         Constants::MODE     => $mode
                     ]);

        $handler = new $consumerClass();

        (new KafkaService\Consumer($handler, $topics, $groupId, $mode))->consume();
    }
}
