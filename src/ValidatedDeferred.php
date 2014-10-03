<?php
namespace GuzzleHttp\Ring;

use React\Promise\Deferred;

/**
 * Creates a deferred value that is validated when it is resolved.
 */
class ValidatedDeferred extends Deferred
{
    private $onResolve;

    /**
     * Creates a deferred value that must be resolved with an array.
     *
     * @return static
     */
    public static function forArray()
    {
        static $validator;

        if (!$validator) {
            $validator = function ($value) {
                if (!is_array($value)) {
                    throw new \InvalidArgumentException(
                        'Expected the resolved value to be an array, but got '
                        . Core::describeType($value));
                }
            };
        }

        return new static($validator);
    }

    /**
     * Creates a deferred value that must be resolved with a specific class.
     *
     * Each validation function is cached for faster reuse.
     *
     * @param string $instance Class or interface
     *
     * @return static
     */
    public static function forInstance($instance)
    {
        static $validators = [];

        if (!isset($validators[$instance])) {
            $validators[$instance] = function ($value) use ($instance) {
                if (!($value instanceof $instance)) {
                    throw new \InvalidArgumentException(
                        'Expected the resolved value to be an instance of '
                        . $instance . ', but got ' . Core::describeType($value)
                    );
                }
            };
        }

        return new static($validators[$instance]);
    }

    /**
     * @param callable $onResolve Function used to validate resolved values.
     */
    public function __construct(callable $onResolve)
    {
        $this->onResolve = $onResolve;
    }

    public function resolve($value = null)
    {
        $fn = $this->onResolve;
        $fn($value);
        parent::resolve($value);
    }
}
