<?php
namespace GuzzleHttp\Ring;

use React\Promise\Deferred;

/**
 * Creates a deferred value that is validated against a PHP internal type.
 */
class ValidatedDeferredType extends Deferred
{
    private $type;

    /**
     * @param string $type PHP internal type to check against the resolved value
     */
    public function __construct($type)
    {
        $this->type = $type;
    }

    public function resolve($value = null)
    {
        $actual = gettype($value);
        if ($actual != $this->type) {
            throw new \InvalidArgumentException(
                'Expected the resolved value to be of type "' . $this->type
                . '", but got ' . Core::describeType($value));
        }

        parent::resolve($value);
    }
}
