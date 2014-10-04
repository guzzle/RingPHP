<?php
namespace GuzzleHttp\Ring;

use React\Promise\Deferred;

/**
 * Creates a deferred value that is validated against a PHP instance type.
 */
class ValidatedDeferredInstance extends Deferred
{
    private $instance;

    /**
     * @param string $instance Class or interface name
     */
    public function __construct($instance)
    {
        $this->instance = $instance;
    }

    public function resolve($value = null)
    {
        if (!($value instanceof $this->instance)) {
            throw new \InvalidArgumentException(
                'Expected the resolved value to be an instance of "'
                . $this->instance . '", but got ' . Core::describeType($value));
        }

        parent::resolve($value);
    }
}
