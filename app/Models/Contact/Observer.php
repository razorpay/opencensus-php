<?php

namespace RZP\Models\Contact;

use RZP\Constants\Mode;
use RZP\Services\KafkaProducer;
use Throwable;
use RZP\Exception;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\Observer as BaseObserver;

class Observer extends BaseObserver
{
    const CHANGE_SET            = "change_set";
    const CONTACT_UPDATED_TOPIC = "contact-entity-update";

    public function updated($contact)
    {
        $this->validateEntity($contact);

        $data = array(
            Entity::ID => $contact->getPublicId(),
            self::CHANGE_SET => $contact->getChanges(),
        );

        $this->trace->info(TraceCode::CONTACT_UPDATED_MESSAGE, [
            'data' => $data,
        ]);

        $message = [
            'data' => $data,
            'attributes' => [
                Entity::TYPE => $contact->getType() ?? ""
            ]
        ];

        try
        {
            $kafkaExperimentEnabled = $this->isVendorPaymentsMetroToKafkaExperimentEnabled($contact->getMerchantId(), Constants::EXPERIMENT_KEY);
            $mode = $this->app['rzp.mode'] ? $this->app['rzp.mode'] : Mode::LIVE;
            if ($kafkaExperimentEnabled === true)
            {
                (new KafkaProducer(self::CONTACT_UPDATED_TOPIC.'-'.$mode, stringify($message)))->Produce();
                
                $this->trace->info(TraceCode::CONTACT_UPDATED_KAFKA_MESSAGE_PUBLISHED);
            } else {

                $metroMessage = [
                    'data' => json_encode($data),
                    'attributes' => [
                        Entity::TYPE => $contact->getType() ?? ""
                    ]
                ];
                $this->app['metro']->publish(self::CONTACT_UPDATED_TOPIC.'-'.$mode, $metroMessage);

                $this->trace->info(TraceCode::CONTACT_UPDATED_METRO_MESSAGE_PUBLISHED);
            }
            
        } catch (Throwable $exception)
        {
            $this->trace->count(Metric::CONTACT_UPDATED_TRIGGER_FAILURE);

            $this->trace->traceException(
                $exception,
                Trace::CRITICAL,
                TraceCode::CONTACT_UPDATED_MESSAGE_FAILED);
        }
    }

    protected function validateEntity($entity)
    {
        if (($entity instanceof Entity) === false)
        {
            throw new Exception\RuntimeException('Entity should be instance of Contact Entity',
                [
                    'entity' => $entity
                ]);
        }
    }
    
    protected function isVendorPaymentsMetroToKafkaExperimentEnabled($merchantId, $experimentId)
    {
        $properties = [
            'id'            => $merchantId,
            'experiment_id' => $this->app['config']->get($experimentId),
        ];
        $response = $this->app['splitzService']->evaluateRequest($properties);
        $variant = $response['response']['variant']['name'] ?? '';
        return $variant === Constants::VARIANT;
    }
}
