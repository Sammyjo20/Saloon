<?php

namespace Sammyjo20\Saloon\Interfaces;

use Sammyjo20\Saloon\Http\SaloonRequest;
use Sammyjo20\Saloon\Http\SaloonConnector;

interface SaloonRequestInterface
{
    public function boot(SaloonRequest $request): void;

    public function getMethod(): ?string;

    public function getResponseClass(): ?string;

    public function getConnector(): ?SaloonConnector;

    public function setConnector(SaloonConnector $connector): self;

    public function getFullRequestUrl(): string;

    public function traitExistsOnConnector(string $trait): bool;

    public function defineEndpoint(): string;
}
