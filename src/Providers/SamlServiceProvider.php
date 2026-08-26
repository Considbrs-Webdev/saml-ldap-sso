<?php

declare(strict_types=1);

namespace SamlLdapSso\Providers;

if (!defined('ABSPATH')) {
    exit;
}

use SamlLdapSso\Auth\PasswordBypass;
use SamlLdapSso\Container;
use SamlLdapSso\Ldap\UserLookup;
use SamlLdapSso\Saml\Handler;
use SamlLdapSso\Saml\Metadata;
use SamlLdapSso\Saml\SignatureVerifier;
use SamlLdapSso\Settings\Options;
use SamlLdapSso\Support\Clock;
use SamlLdapSso\Support\Logger;
use SamlLdapSso\Users\UserManager;

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
