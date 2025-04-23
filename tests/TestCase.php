<?php

declare(strict_types=1);

namespace Pollora\Pollingo\Tests;

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadEnvironmentVariables();
    }

    private function loadEnvironmentVariables(): void
    {
        // Load test environment variables
        $envFiles = ['.env.testing', '.env'];
        $basePath = __DIR__.'/../';

        foreach ($envFiles as $file) {
            if (file_exists($basePath . $file)) {
                $dotenv = Dotenv::createImmutable($basePath, $file);
                $dotenv->load();
                break;
            }
        }

        // Set default test values if not set
        if (!isset($_ENV['OPENAI_API_KEY'])) {
            $_ENV['OPENAI_API_KEY'] = 'test-key';
        }

        if (!isset($_ENV['OPENAI_MODEL'])) {
            $_ENV['OPENAI_MODEL'] = 'gpt-4_1-2025-04-14';
        }
    }
}
