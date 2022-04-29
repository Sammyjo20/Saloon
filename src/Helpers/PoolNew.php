<?php

namespace Sammyjo20\Saloon\Helpers;

use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\EachPromise;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use Sammyjo20\Saloon\Clients\MockClient;
use Sammyjo20\Saloon\Http\SaloonConnector;
use \Iterator;
use Sammyjo20\Saloon\Http\SaloonRequest;
use InvalidArgumentException;
use Sammyjo20\Saloon\Http\SaloonResponse;

class PoolNew
{
    /**
     * @var EachPromise
     */
    private $each;

    /**
     * @param ClientInterface $client   Client used to send the requests.
     * @param \Iterator|array $requests Requests or functions that return
     *                                  requests to send concurrently.
     * @param array           $config   Associative array of options
     *                                  - concurrency: (int) Maximum number of requests to send concurrently
     *                                  - options: Array of request options to apply to each request.
     *                                  - fulfilled: (callable) Function to invoke when a request completes.
     *                                  - rejected: (callable) Function to invoke when a request is rejected.
     */
    public function __construct(Iterator|array $requests, MockClient $mockClient = null, array $options = [])
    {
        $options['concurrency'] = 5;

        $iterable = Create::iterFor($requests);

        $requestsObject = static function () use ($iterable, $mockClient) {
            foreach ($iterable as $key => $rfn) {
                if ($rfn instanceof SaloonRequest) {
                    yield $key => $rfn->sendAsync();
                }
            }
        };

        $this->each = new EachPromise($requestsObject(), $options);
    }

    /**
     * Get promise
     */
    public function promise(): PromiseInterface
    {
        return $this->each->promise();
    }
}
