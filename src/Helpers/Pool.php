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
     * @var array
     */
    protected array $objects = [];

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
            $this->objects[] = $requests;
        } else {
            $this->objects = array_merge($this->objects, $requests);
        }

        return $this;
    }

    public function addRequest(SaloonRequest $request): self
    {
        $this->addRequests([$request]);

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

    /**
     * Create the generator that returns the requests.
     *
     * @return callable
     */
    protected function createRequestIterator(): callable
    {
        $objects = Create::iterFor($this->objects);
        $mockClient = $this->mockClient;

        $objects = $objects[0];

        return function () use ($objects, $mockClient) {
            foreach ($objects as $key => $object) {
                // Let's firstly check if the object provided is an iterator
                // which is most likely a generator. If it isn't, it will
                // just be a request - so we will run this.

                if (! $object instanceof Iterator) {
                    $this->validateRequest($object);

                    yield $object->sendAsync($mockClient);

                    continue;
                }

                // However, if the object is iterable - we can loop through
                // it and then yield each of the requests inside. If one
                // of the object is not a request, we will throw an
                // exception.

                foreach ($object as $nestedObject) {
                    $this->validateRequest($nestedObject);

                    yield $nestedObject->sendAsync($mockClient);
                }
            }
        };
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
        $requests = $this->createRequestIterator();

        $eachPromise = new EachPromise($requests(), [
            'concurrency' => $this->concurrency,
            'fulfilled' => $this->onSuccess,
            'rejected' => $this->onFailure,
        ]);

        return $eachPromise->promise();
    }
}
