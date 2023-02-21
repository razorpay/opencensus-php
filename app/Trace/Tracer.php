<?php

namespace RZP\Trace;

use OpenCensus\Trace\Span;
use OpenCensus\Trace\Tracer as OpenCensusTracer;
use OpenCensus\Trace\Exporter\ExporterInterface;
use OpenCensus\Trace\Propagator\ArrayHeaders;


class Tracer
{
    public static function inSpan(array $spanOptions, callable $callable, array $arguments = [])
    {
        return OpenCensusTracer::inSpan($spanOptions, $callable, $arguments);
    }

    public static function startSpan(array $spanOptions = [])
    {
        try
        {
            return OpenCensusTracer::startSpan($spanOptions);
        }
        catch (\Error $e)
        {
            app('trace')->warning(TraceCode::OPENCENSUS_ERROR,
                ['startSpan', $e->getMessage()]);
        }
    }

    public static function withSpan(Span $span)
    {
        try
        {
            return OpenCensusTracer::withSpan($span);
        }
        catch (\Error $e)
        {
            app('trace')->warning(TraceCode::OPENCENSUS_ERROR,
                ['withSpan', $e->getMessage()]);
        }
    }

    public static function injectContext(ArrayHeaders $headers)
    {
        try
        {
            return OpenCensusTracer::injectContext($headers);
        }
        catch (\Error $e)
        {
            app('trace')->warning(TraceCode::OPENCENSUS_ERROR,
                ['injectContext', $e->getMessage()]);
        }
    }

    public static function spanContext()
    {
        try
        {
            return OpenCensusTracer::spanContext();
        }
        catch (\Error $e)
        {
            app('trace')->warning(TraceCode::OPENCENSUS_ERROR,
                ['spanContext', $e->getMessage()]);
        }
    }

    public static function addAttribute($attribute, $value, array $options = [])
    {
        try
        {
            if((!is_null($attribute)) and (!is_null($value))){
                return OpenCensusTracer::addAttribute($attribute, $value, $options);
            }
        }
        catch (\Error $e)
        {
            app('trace')->warning(TraceCode::OPENCENSUS_ERROR,
                ['addAttribute', $e->getMessage()]);
        }
    }

    public static function addAttributes(array $context, array $options = [])
    {
        foreach ($context as $key => $value)
        {
            try
            {
                if(is_array($value))
                {
                    $value = implode(', ', $value);
                }
                self::addAttribute($key, $value, $options);
            }
            catch (\Error $e)
            {
                app('trace')->warning(TraceCode::OPENCENSUS_ERROR,
                    ['addAttributes', $e->getMessage()]);
            }
        }
    }

    public static function startSpanWithAttributes(string $spanName, array $attributes = [])
    {
        $span = self::startSpan(['name' => $spanName]);

        $scope = self::withSpan($span);

        try
        {
            $span->addAttribute('kind', 'server');

            foreach ($attributes as $key => $value)
            {
                $span->addAttribute($key, $value);
            }
        }
        finally
        {
            // Closes the scope (ends the span)
            $scope->close();
        }
    }
}
