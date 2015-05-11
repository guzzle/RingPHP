<?php
namespace GuzzleHttp\Ring\Client;

interface HandlerInterface
{
	/**
     * @param array $request
     *
     * @return CompletedFutureArray
     */
	public function __invoke(array $request);
}