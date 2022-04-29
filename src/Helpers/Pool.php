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

class Pool
{
    /**
     * @var EachPromise
     */
    protected mixed $requests;

    /**
     * @var MockClient|null
     */
    protected ?MockClient $mockClient = null;

    /**
     * @var null
     */
    protected mixed $onSuccess = null;

    /**
     * @var null
     */
    protected mixed $onFailure = null;

    /**
     * @var int
     */
    protected int $concurrency = 5;

    /**
     * @param Iterator $requests
     * @param MockClient|null $mockClient
     */
    public function __construct(Iterator $iterator, MockClient $mockClient = null, callable $onSuccess, callable $onFailure)
    {
        $values = Create::iterFor($iterator);

        $requests = static function () use ($values, $mockClient) {
            foreach ($values as $key => $value) {
                // $this->validateRequest($value);

                yield $key => $value->sendAsync($mockClient);
            }
        };

        $this->each = new EachPromise($requests(), [
            'concurrency' => 5,
            'fulfilled' => $onSuccess,
            'rejected' => $onFailure,
        ]);
    }

    /**
     * Instantiate a new class with the arguments.
     *
     * @param Iterator|array $requests
     * @param MockClient|null $mockClient
     * @return Pool
     */
    public static function make(Iterator|array $requests, MockClient $mockClient = null): self
    {
        return new static($requests, $mockClient);
    }

    public function onSuccess(callable $callback): self
    {
        $this->onSuccess = $callback;

        return $this;
    }

    public function onFailure(callable $callback): self
    {
        $this->onFailure = $callback;

        return $this;
    }

    public function setConcurrency(int $concurrency): self
    {
        $this->concurrency = $concurrency;

        return $this;
    }

    /**
     * Create the generator that returns the requests.
     *
     * @return callable
     */
    protected function createRequestIterator(): callable
    {

    }

    /**
     * Throw an exception if the provided value is not a SaloonRequest.
     *
     * @param mixed $request
     * @return void
     */
    private function validateRequest(mixed $request): void
    {
        if (! $request instanceof SaloonRequest) {
            throw new InvalidArgumentException('The pool must only contain SaloonRequests or iterator functions that return SaloonRequests.');
        }
    }

    /**
     * Create the pool promise.
     *
     * @return PromiseInterface
     */
    public function promise(): PromiseInterface
    {
        $requests = $this->requests;

        $eachPromise = new EachPromise($requests(), [
            'concurrency' => $this->concurrency,
            'fulfilled' => $this->onSuccess,
            'rejected' => $this->onFailure,
        ]);

        return $eachPromise->promise();
    }
}
