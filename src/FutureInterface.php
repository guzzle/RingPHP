<?php
namespace GuzzleHttp\Ring;

/**
 * Represents the result of a computation that may not have completed yet.
 *
 * When the future is dereferenced using deref(), the result of the computation
 * is cached and returned for subsequent calls to deref(). If the result of the
 * computation has not yet completed when deref() is called, the call to
 * deref() will block until the future has completed.
 */
interface FutureInterface
{
    /**
     * Returns the result of the future either from cache or by blocking until
     * it is complete.
     *
     * This method must block until the future has a result or is cancelled.
     *
     * @return array
     */
    public function deref();

    /**
     * Returns true if the future has been realized, meaning a result or error
     * is available.
     *
     * @return bool
     */
    public function realized();

    /**
     * Cancels the future.
     *
     * There are three different cases handled by this method:
     *
     * 1. If the future is already realized or cancelled, this function returns
     *    false because there's no way to cancel it.
     * 2. If the future has not been realized, then the dereference function
     *    will be disassociated from the future, will not be called, and
     *    the future state will be changed to cancelled. Because the future
     *    may not have been truly cancelled from the executor, this case will
     *    return false.
     * 3. If the future has a cancel function, then the future state will be
     *    set to cancelled, the cancel function will be called, and the result
     *    of the invocation will be returned.
     *
     * @return bool
     */
    public function cancel();

    /**
     * Returns true if the future has been cancelled.
     *
     * @return bool
     */
    public function cancelled();
}
