<?php

declare(strict_types=1);

namespace Tests\Unit;

use Pollora\Pollingo\Pollingo;
use Pollora\Pollingo\Services\OpenAITranslator;
use Pollora\Pollingo\Tests\TestCase;
use ReflectionClass;

class PollingoModelTest extends TestCase
{
    public function testModelMethodChangesTranslatorModel(): void
    {
        // Créer une instance de Pollingo avec un modèle par défaut
        $defaultModel = 'gpt-4';
        $pollingo = Pollingo::make('fake-api-key', $defaultModel);
        
        // Récupérer le translator sous-jacent
        $translatorReflection = new ReflectionClass($pollingo);
        $translatorProperty = $translatorReflection->getProperty('translator');
        $translatorProperty->setAccessible(true);
        $translator = $translatorProperty->getValue($pollingo);
        
        $this->assertInstanceOf(OpenAITranslator::class, $translator);
        
        // Vérifier que le modèle initial est correct
        $this->assertEquals($defaultModel, $translator->getModel());
        
        // Changer le modèle avec la méthode model()
        $newModel = 'gpt-4.1-nano';
        $pollingo->model($newModel);
        
        // Vérifier que le modèle a bien été mis à jour
        $this->assertEquals($newModel, $translator->getModel());
    }
    
    public function testTimeoutMethodChangesTranslatorTimeout(): void
    {
        // Créer une instance de Pollingo
        $pollingo = Pollingo::make('fake-api-key');
        
        // Récupérer le translator sous-jacent
        $translatorReflection = new ReflectionClass($pollingo);
        $translatorProperty = $translatorReflection->getProperty('translator');
        $translatorProperty->setAccessible(true);
        $translator = $translatorProperty->getValue($pollingo);
        
        // Vérifier le timeout initial
        $initialTimeout = $translator->getTimeout();
        
        // Changer le timeout
        $newTimeout = 180;
        $pollingo->timeout($newTimeout);
        
        // Vérifier que le timeout a bien été mis à jour
        $this->assertEquals($newTimeout, $translator->getTimeout());
    }
    
    public function testMaxRetriesMethodChangesTranslatorMaxRetries(): void
    {
        // Créer une instance de Pollingo
        $pollingo = Pollingo::make('fake-api-key');
        
        // Récupérer le translator sous-jacent
        $translatorReflection = new ReflectionClass($pollingo);
        $translatorProperty = $translatorReflection->getProperty('translator');
        $translatorProperty->setAccessible(true);
        $translator = $translatorProperty->getValue($pollingo);
        
        // Vérifier le maxRetries initial
        $initialMaxRetries = $translator->getMaxRetries();
        
        // Changer le maxRetries
        $newMaxRetries = 5;
        $pollingo->maxRetries($newMaxRetries);
        
        // Vérifier que le maxRetries a bien été mis à jour
        $this->assertEquals($newMaxRetries, $translator->getMaxRetries());
    }
    
    public function testRetryDelayMethodChangesTranslatorRetryDelay(): void
    {
        // Créer une instance de Pollingo
        $pollingo = Pollingo::make('fake-api-key');
        
        // Récupérer le translator sous-jacent
        $translatorReflection = new ReflectionClass($pollingo);
        $translatorProperty = $translatorReflection->getProperty('translator');
        $translatorProperty->setAccessible(true);
        $translator = $translatorProperty->getValue($pollingo);
        
        // Vérifier le retryDelay initial
        $initialRetryDelay = $translator->getRetryDelay();
        
        // Changer le retryDelay
        $newRetryDelay = 2000;
        $pollingo->retryDelay($newRetryDelay);
        
        // Vérifier que le retryDelay a bien été mis à jour
        $this->assertEquals($newRetryDelay, $translator->getRetryDelay());
    }
} 