<?php

namespace Sammyjo20\Saloon\Tests\Fixtures\Requests;

use Sammyjo20\Saloon\Constants\Saloon;
use Sammyjo20\Saloon\Http\SaloonRequest;
use Sammyjo20\Saloon\Traits\Auth\RequiresAuth;
use Sammyjo20\Saloon\Interfaces\AuthenticatorInterface;
use Sammyjo20\Saloon\Tests\Fixtures\Connectors\TestConnector;
use Sammyjo20\Saloon\Tests\Fixtures\Authenticators\PizzaAuthenticator;

class DefaultPizzaAuthenticatorRequest extends SaloonRequest
{
    use RequiresAuth;

    /**
     * Define the method that the request will use.
     *
     * @var string|null
     */
    protected ?string $method = Saloon::GET;

    /**
     * The connector.
     *
     * @var string|null
     */
    protected ?string $connector = TestConnector::class;

    /**
     * Define the endpoint for the request.
     *
     * @return string
     */
    public function defineEndpoint(): string
    {
        return '/user';
    }

    /**
     * @return AuthenticatorInterface|null
     */
    public function defaultAuth(): ?AuthenticatorInterface
    {
        return new PizzaAuthenticator('BBQ Chicken', 'Lemonade');
    }

    public function __construct(public ?int $userId = null, public ?int $groupId = null)
    {
        //
    }
}
