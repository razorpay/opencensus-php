<?php declare(strict_types=1);

namespace OpenCensus\Trace\Propagator;

class ArrayHeaders implements HeaderSetter, HeaderGetter, \IteratorAggregate, \ArrayAccess
{
    /**
     * @var string[]
     */
    private $headers;

    /**
     * @param string[] $headers An associative array with header name as key
     */
    public function __construct(array $headers = [])
    {
        $this->headers = $headers;
    }

    public function get(string $header)
    {
        return $this->headers[$header] ?? null;
    }

    public function set(string $header, string $value)
    {
        $this->headers[$header] = $value;
    }

    public function toArray(): array
    {
        return $this->headers;
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->headers);
    }

    public function offsetExists($offset): bool
    {
        return isset($this->headers[$offset]);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->headers[$offset]);
    }
}
