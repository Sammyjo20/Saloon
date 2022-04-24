<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Sammyjo20\Saloon\Clients\MockClient;
use Sammyjo20\Saloon\Helpers\Pool;
use Sammyjo20\Saloon\Http\MockResponse;
use Sammyjo20\Saloon\Http\SaloonResponse;
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

    $requests = function ($total) use ($mockClient) {
        for ($i = 0; $i < $total; $i++) {
            yield UserRequest::make()->sendAsync($mockClient);
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
            yield UserRequest::make()->sendAsync();
        }
    };

    $pool = Pool::make([
        UserRequest::make(),
        UserRequest::make(),
        UserRequest::make(),
        UserRequest::make(),
        UserRequest::make(),
    ])
        ->onSuccess(function ($res, $index) {
            ray($index, $res->json());
        })
        ->onFailure(function ($res, $index) {
            ray($index, $res)->red();
        })
        ->setConcurrency(5);

    $p = $pool->promise()->wait();
});
