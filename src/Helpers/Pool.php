<?php

namespace Sammyjo20\Saloon\Helpers;

use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\EachPromise;
use GuzzleHttp\Promise\Promise;
use Sammyjo20\Saloon\Clients\MockClient;
use Sammyjo20\Saloon\Http\SaloonConnector;
use \Iterator;
use Sammyjo20\Saloon\Http\SaloonRequest;
use InvalidArgumentException;
use Sammyjo20\Saloon\Http\SaloonResponse;

class Pool
{
    protected int $concurrency = 5;

    protected array $iterables = [];

    /**
     * @var MockClient
     */
    protected ?MockClient $mockClient = null;

    protected $onSuccess = null;
    protected $onFailure = null;

    protected SaloonConnector $connector;

    /**
     * @param Iterator|array $requests
     * @param MockClient|null $mockClient
     */
    public function __construct(Iterator|array $requests, MockClient $mockClient = null)
    {
        $this->mockClient = $mockClient;

        $this->addRequests($requests);
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

    /**
     * @param Iterator|array $requests
     * @return $this
     */
    public function addRequests(Iterator|array $requests): self
    {
        if ($requests instanceof Iterator) {
            $this->iterables[] = $requests;
        } else {
            $this->iterables = array_merge($this->iterables, $requests);
        }

        return $this;
    }

    public function addRequest(SaloonRequest $request): self
    {
        $this->requests[] = $request;

        return $this;
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

    public function withConnector(SaloonConnector $connector): self
    {
        $this->connector = $connector;

        return $this;
    }

    /**
     *
     *
     * @return callable
     */
    private function createRequestIterator(): callable
    {
        $iterables = array_values($this->iterables);
        $mockClient = $this->mockClient;

        return static function () use ($iterables, $mockClient) {
            foreach ($iterables as $request) {
                $isRequestIterable = $request instanceof Iterator;

                if (! $isRequestIterable) {
                    if (! $request instanceof SaloonRequest) {
                        throw new InvalidArgumentException('The pool must only contain SaloonRequests or iterator functions that return SaloonRequests.');
                    }

                    yield $request->sendAsync($mockClient);

                    continue;
                }

                foreach ($request as $item) {
                    if ($item instanceof SaloonRequest) {
                        yield $item->sendAsync();

                        continue;
                    }

                    if (! $item instanceof Promise) {
                        throw new InvalidArgumentException('The pool must only contain SaloonRequests or iterator functions that return promises.');
                    }

                    yield $item;
                }
            }
        };
    }

    public function promise()
    {
        $requests = $this->createRequestIterator();

        $eachPromise = new EachPromise($requests(), [
            'concurrency' => $this->concurrency,
            'fulfilled' => $this->onSuccess,
            'rejected' => $this->onFailure,
        ]);

        return $eachPromise->promise();
    }
}
