<?php
/**
 * Copyright 2017 OpenCensus Authors
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

// reference: https://www.jaegertracing.io/docs/1.18/client-libraries/#propagation-format


namespace OpenCensus\Trace\Propagator;

use OpenCensus\Trace\SpanContext;

/**
 * This propagator uses HTTP headers to propagate SpanContext over HTTP.
 * The default headers is `uber-trace-id`.
 */
class JaegerPropagator implements PropagatorInterface
{
    const DEFAULT_HEADER = 'uber-trace-id';

    // refer: https://www.jaegertracing.io/docs/1.18/client-libraries/#propagation-format

    const CONTEXT_HEADER_FORMAT = '%032s:%016s:%016s:%x';    //traceId, spanId are stored as hex strings in opencensus

    /**
     * @var FormatterInterface
     */
    private $formatter;

    /**
     * @var string
     */
    private $header;


    /**
     * Create a new JaegerHeaderPropagator
     *
     * @param FormatterInterface $formatter The formatter used to serialize and
     *        deserialize SpanContext. **Defaults to** a new TraceContextFormatter.
     * @param string $header
     * header format is {trace-id}:{span-id}:{parent-span-id}:{flags}';
     */
    public function __construct(FormatterInterface $formatter = null, string $header = self::DEFAULT_HEADER)
    {
        $this->header = $header;
    }

    public function extract(HeaderGetter $headers): SpanContext
    {
        // normalize header name that comes in, like php does it
        $extract_header = 'HTTP_' . strtoupper(str_replace('-', '_', $this->header));

        $data = $headers->get($extract_header);

        if (!$data) {
            return new SpanContext();
        }
        
        // Jaeger trace id can be of length either 16 or 32. (https://www.jaegertracing.io/docs/1.21/client-libraries/#value)
        // We have decided to continue with trace id of length 32 for injection. While extraction can accept both length 16 and 32.
        $data = explode($data, ':');
        if (count($data) < 4) {
            return new SpanContext();
        }
        
        $traceId = $data[0];
        $spanId = $data[1];
        $parentSpanId = $data[2];
        $flags = $data[3];

        $enabled = $flags & 0x01;

        $fromHeader = true;

        return new SpanContext($traceId, $spanId, $enabled, $fromHeader);
    }

    public function inject(SpanContext $context, HeaderSetter $setter)
    {
        $traceId = $context->traceId();
        $spanId = $context->spanId();
        $parentID = ''; // this is deprecated anyway
        $enabled = $context->enabled();

        $value = sprintf(self::CONTEXT_HEADER_FORMAT, $traceId, $spanId, $parentID, $enabled);

        if (!headers_sent()) {
            header("$this->header: $value");
        }
        $setter->set($this->header, $value);
    }
}
