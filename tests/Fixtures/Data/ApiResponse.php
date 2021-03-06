<?php

namespace Sammyjo20\Saloon\Tests\Fixtures\Data;

use Sammyjo20\Saloon\Http\SaloonResponse;

class ApiResponse
{
    /**
     * @param array $data
     */
    public function __construct(
        public array $data,
    ) {
        //
    }

    /**
     * @param SaloonResponse $response
     * @return static
     */
    public static function fromSaloon(SaloonResponse $response): self
    {
        return new static($response->json());
    }
}
