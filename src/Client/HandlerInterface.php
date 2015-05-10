<?php
namespace GuzzleHttp\Ring\Client;

use GuzzleHttp\Ring\Future\CompletedFutureArray;
use GuzzleHttp\Ring\Core;

interface HandlerInterface
{
	/**
     * @param array $request
     *
     * @see http://ringphp.readthedocs.org/en/latest/client_handlers.html#implementing-handlers
     * @return CompletedFutureArray
     */
	public function __invoke(array $request);
}