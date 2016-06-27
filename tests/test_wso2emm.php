<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

$mock = new MockHandler([
    new Response(200, ['X-Foo' => 'bar']),
    new Response(202, ['Content-Length' => 0]),
    new RequestException("Error communicating", new Request('GET', 'test'))
]);

$handler = HandlerStack::create($mock);
$client = new Client(['handler' => $handler]);


