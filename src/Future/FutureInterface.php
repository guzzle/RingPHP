<?php
namespace GuzzleHttp\Ring\Future;

use React\Promise\PromiseInterface;

/**
 * Represents the result of a computation that may not have completed yet.
 *
 * You can use the future in a blocking manner using the wait() function, or
 * you can use a promise from the future to receive the result when the future
 * has been resolved.
 *
 * When the future is dereferenced using wait(), the result of the computation
 * is cached and returned for subsequent calls to wait(). If the result of the
 * computation has not yet completed when wait() is called, the call to wait()
 * will block until the future has completed.
 */
interface FutureInterface extends PromiseInterface
{
    /**
     * Returns the result of the future either from cache or by blocking until
     * it is complete.
     *
     * This method must block until the future has a result or is cancelled.
     * Throwing an exception in the wait() method will mark the future as
     * realized and will throw the exception each time wait() is called.
     * Throwing an instance of GuzzleHttp\Ring\CancelledException will mark
     * the future as realized, will not throw immediately, but will throw the
     * exception if the future's wait() method is called again.
     *
     * @return array
     */
    public function wait();

    /**
     * Cancels the future.
     *
     * There are three different cases handled by this method:
     *
     * 1. Returns false if the future is already realized.
     * 2. Returns false if the future has no cancel function.
     * 3. Returns the result of invoking a cancellation function if the future
     *    has a cancel function.
     *
     * @return bool
     */
    public function cancel();
}
