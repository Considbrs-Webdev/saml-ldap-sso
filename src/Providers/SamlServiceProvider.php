<?php

declare(strict_types=1);

namespace Signclave\Providers;

if (!defined('ABSPATH')) {
    exit;
}

use Signclave\Auth\PasswordBypass;
use Signclave\Container;
use Signclave\Ldap\UserLookup;
use Signclave\Saml\Handler;
use Signclave\Saml\Metadata;
use Signclave\Saml\SignatureVerifier;
use Signclave\Settings\Options;
use Signclave\Support\Clock;
use Signclave\Support\Logger;
use Signclave\Users\UserManager;

/**
 * SAML metadata, signature verification, and ACS handler services.
 */
final class SamlServiceProvider implements ServiceProvider
{
    /**
     * @inheritDoc
     */
    public function register(Container $container): void
    {
        $container->singleton(SignatureVerifier::class, static fn () => new SignatureVerifier());
        $container->singleton(
            Metadata::class,
            static fn (Container $c) => new Metadata($c->make(Options::class))
        );
        $container->singleton(
            Handler::class,
            static fn (Container $c) => new Handler(
                $c->make(SignatureVerifier::class),
                $c->make(UserManager::class),
                $c->make(Logger::class),
                $c->make(UserLookup::class),
                $c->make(Options::class),
                $c->make(PasswordBypass::class),
                $c->make(Metadata::class),
                $c->make(Clock::class)
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function boot(Container $container): void
    {
        $handler = $container->make(Handler::class);
        assert($handler instanceof Handler);
        $handler->registerHooks();
    }
}
