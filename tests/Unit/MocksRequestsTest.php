<?php

use Sammyjo20\Saloon\Http\MockResponse;
use Sammyjo20\Saloon\Clients\MockClient;
use Sammyjo20\Saloon\Tests\Fixtures\Requests\UserRequest;
use Sammyjo20\Saloon\Tests\Fixtures\Connectors\TestConnector;

test('you can provide a mock client on a connector and all requests will be mocked', function () {
    $mockClient = new MockClient([
        MockResponse::make(['name' => 'Sam']),
        MockResponse::make(['name' => 'Mantas']),
    ]);

    $connector = new TestConnector;
    $connector->withMockClient($mockClient);

    $responseA = $connector->request(new UserRequest)->send();
    $responseB = $connector->request(new UserRequest)->send();

    expect($responseA->isMocked())->toBeTrue();
    expect($responseB->isMocked())->toBeTrue();
});

test('you can provide a mock client on a request and all requests will be mocked', function () {
    $mockClient = new MockClient([
        MockResponse::make(['name' => 'Sam']),
    ]);

    $request = new UserRequest;
    $request->withMockClient($mockClient);

    $response = $request->send();

    expect($response->isMocked())->toBeTrue();
});

test('request mock clients are always prioritied', function () {
    $mockClientA = new MockClient([
        MockResponse::make(['name' => 'Sam']),
    ]);

    $mockClientB = new MockClient([
        MockResponse::make(['name' => 'Mantas']),
    ]);

    $connector = new TestConnector;
    $connector->withMockClient($mockClientA);

    $request = new UserRequest;
    $request->withMockClient($mockClientB);

    $response = $request->send();

    expect($response->isMocked())->toBeTrue();
    expect($response->json())->toEqual(['name' => 'Mantas']);
});
