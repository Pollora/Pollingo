<?php

declare(strict_types=1);

namespace Pollora\Pollingo\Tests;

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase as BaseTestCase;

final class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadEnvironmentVariables();
    }

    private function loadEnvironmentVariables(): void
    {
        // Try to load from .env file, fallback to .env.example if not found
        $envFile = file_exists(__DIR__.'/../.env') ? '.env' : '.env.example';

        $dotenv = Dotenv::createImmutable(__DIR__.'/../');
        $dotenv->load();

        // Ensure required variables are set
        $dotenv->required('OPENAI_API_KEY')->notEmpty();
    }
}
