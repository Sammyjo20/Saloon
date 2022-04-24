<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\Utils;
use Sammyjo20\Saloon\Clients\MockClient;
use Sammyjo20\Saloon\Helpers\Pool;
use Sammyjo20\Saloon\Http\MockResponse;
use Sammyjo20\Saloon\Http\SaloonResponse;
use Sammyjo20\Saloon\Tests\Fixtures\Requests\ErrorRequest;
use Sammyjo20\Saloon\Tests\Fixtures\Requests\UserRequest;

test('a request pool can send multiple requests concurrently', function () {
    $mockClient = new MockClient([
        MockResponse::make(['name' => 'Sam']),
        MockResponse::make(['name' => 'Michael']),
        MockResponse::make(['name' => 'Gareth']),
        MockResponse::make(['name' => 'Mantas']),
    ]);

    $requests = [
        UserRequest::make(),
        UserRequest::make(),
        UserRequest::make(),
        UserRequest::make(),
    ];

    $pool = Pool::make($requests, $mockClient)
        ->onSuccess(function (SaloonResponse $response) {
            ray($response);

            return $response;
        })
        ->onFailure(function ($error) {
            //
        });

    $pool->promise()->wait();
});

test('you can provide a generator when sending requests to the pool', function () {
    $mockClient = new MockClient([
        MockResponse::make(['name' => 'Sam']),
        MockResponse::make(['name' => 'Michael']),
        MockResponse::make(['name' => 'Gareth'], 500),
        MockResponse::make(['name' => 'Mantas']),
        MockResponse::make(['name' => 'Teo']),
    ]);

    $requests = function ($total) {
        for ($i = 0; $i < $total; $i++) {
            yield UserRequest::make();
        }
    };

    $pool = Pool::make($requests(5), $mockClient)
        ->onSuccess(function ($value) {
            ray($value->json());

            return $value;
        })
        ->onFailure(function ($error) {
            ray($error)->red();
        });

    $pool->promise()->wait();
});

test('you can add a request or an iterator after you have created the pool', function () {

});

test('the guzzle way', function () {
    $requests = function ($total) {
        for ($i = 0; $i < $total; $i++) {
            yield new UserRequest;
        }
    };

    $pool = Pool::make($requests(10))
        ->onSuccess(function ($res, $index) {
            ray($index, $res->json());
        })
        ->onFailure(function ($res, $index) {
            ray($index, $res)->red();
        })
        ->setConcurrency(5);

    $promise = $pool->promise();

// Force the pool of requests to complete.
    $promise->wait();
});

test('the guzzle way 2', function () {
    $client = new Client();

    $requests = function ($total) {
        $uri = 'https://tests.saloon.dev/api/user';
        for ($i = 0; $i < $total; $i++) {
            yield new \GuzzleHttp\Psr7\Request('GET', $uri);
        }
    };

    $pool = new \GuzzleHttp\Pool($client, $requests(10), [
        'concurrency' => 5,
        'fulfilled' => function (\GuzzleHttp\Psr7\Response $response, $index) {
            ray($index, $response);
            // this is delivered each successful response
        },
        'rejected' => function (RequestException $reason, $index) {
            ray($index, $reason)->red();
        },
    ]);

// Initiate the transfers and create a promise
    $promise = $pool->promise();

// Force the pool of requests to complete.
    $promise->wait();
});
