<?php

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\Utils;
use Sammyjo20\Saloon\Http\MockResponse;
use Sammyjo20\Saloon\Clients\MockClient;
use Sammyjo20\Saloon\Http\SaloonResponse;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Sammyjo20\Saloon\Exceptions\SaloonRequestException;
use Sammyjo20\Saloon\Tests\Fixtures\Requests\UserRequest;

test('an asynchronous request will return a saloon response on a successful request', function () {
    $mockClient = new MockClient([
        MockResponse::make(['name' => 'Sam']),
    ]);

    $request = new UserRequest;
    $promise = $request->sendAsync($mockClient);

    expect($promise)->toBeInstanceOf(Promise::class);

    $response = $promise->wait();

    expect($response)->toBeInstanceOf(SaloonResponse::class);
    expect($response->json())->toEqual(['name' => 'Sam']);
    expect($response->status())->toEqual(200);
});

test('an asynchronous request will throw a saloon exception on an unsuccessful request', function () {
    $mockClient = new MockClient([
        MockResponse::make(['error' => 'Server Error'], 500),
    ]);

    $request = new UserRequest;
    $promise = $request->sendAsync($mockClient);

    expect($promise)->toBeInstanceOf(Promise::class);

    try {
        $promise->wait();
    } catch (Exception $exception) {
        expect($exception)->toBeInstanceOf(SaloonRequestException::class);

        $response = $exception->getSaloonResponse();

        expect($response)->toBeInstanceOf(SaloonResponse::class);
        expect($response->json())->toEqual(['error' => 'Server Error']);
        expect($response->status())->toEqual(500);
        expect($response->getGuzzleException())->toBeInstanceOf(RequestException::class);
    }
});

test('an asynchronous request will return a connect exception if a connection error happens', function () {
    $mockClient = new MockClient([
        MockResponse::make(['name' => 'Patrick'])->throw(fn ($guzzleRequest) => new ConnectException('Unable to connect!', $guzzleRequest)),
    ]);

    $request = new UserRequest;
    $promise = $request->sendAsync($mockClient);

    try {
        $promise->wait();
    } catch (Exception $exception) {
        expect($exception)->toBeInstanceOf(ConnectException::class);
        expect($exception->getMessage())->toEqual('Unable to connect!');
    }
});

test('if you chain an asynchronous request you can have a SaloonResponse', function () {
    $mockClient = new MockClient([
        MockResponse::make(['name' => 'Sam']),
    ]);

    $request = new UserRequest;
    $promise = $request->sendAsync($mockClient);

    $promise->then(
        function (SaloonResponse $response) {
            expect($response)->toBeInstanceOf(SaloonResponse::class);
        }
    );

    $promise->wait();
});

test('if you chain an erroneous asynchronous request the error can be caught in the rejection handler', function () {
    $mockClient = new MockClient([
        MockResponse::make(['error' => 'Server Error'], 500),
    ]);

    $request = new UserRequest;
    $promise = $request->sendAsync($mockClient);

    $promise->then(
        null,
        function (SaloonRequestException $exception) {
            $response = $exception->getSaloonResponse();

            expect($response)->toBeInstanceOf(SaloonResponse::class);
            expect($response->status())->toEqual(500);
            expect($response->getGuzzleException())->toBeInstanceOf(RequestException::class);
        }
    );

    try {
        $promise->wait();
    } catch (\Exception $ex) {
        //
    }
});

test('if a connection exception happens it will be provided in the rejection handler', function () {
    $mockClient = new MockClient([
        MockResponse::make(['name' => 'Patrick'])->throw(fn ($guzzleRequest) => new ConnectException('Unable to connect!', $guzzleRequest)),
    ]);

    $request = new UserRequest;
    $promise = $request->sendAsync($mockClient);

    $promise->then(
        null,
        function (ConnectException $exception) {
            expect($exception->getMessage())->toEqual('Unable to connect!');
        }
    );

    try {
        $promise->wait();
    } catch (\Exception $ex) {
        //
    }
});

test('multiple requests can be made at once', function () {
    $mockClient = new MockClient([
        MockResponse::make(['name' => 'A']),
        MockResponse::make(['name' => 'B']),
        MockResponse::make(['name' => 'C']),
        MockResponse::make(['name' => 'D']),
    ]);

    $requests = [
        'a' => UserRequest::make()->sendAsync($mockClient),
        'b' => UserRequest::make()->sendAsync($mockClient),
        'c' => UserRequest::make()->sendAsync($mockClient),
        'd' => UserRequest::make()->sendAsync($mockClient),
    ];

    $responses = Utils::unwrap($requests);

    expect($responses['a']->json())->toEqual(['name' => 'A']);
    expect($responses['b']->json())->toEqual(['name' => 'B']);
    expect($responses['c']->json())->toEqual(['name' => 'C']);
    expect($responses['d']->json())->toEqual(['name' => 'D']);
});
