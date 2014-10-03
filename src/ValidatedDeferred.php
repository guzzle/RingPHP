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
    public static function deferredArray()
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
     * @param callable $onResolve Function used to validate resolved values.
     */
    public function __construct(callable $onResolve)
    {
        $this->onResolve = $onResolve;
    }

    public function resolve($value = null)
    {
        call_user_func($this->onResolve, $value);
        parent::resolve($value);
    }
}
