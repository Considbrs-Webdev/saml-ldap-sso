<?php

declare(strict_types=1);

namespace SamlLdapSso\Providers;

if (!defined('ABSPATH')) {
    exit;
}

use SamlLdapSso\Auth\PasswordBypass;
use SamlLdapSso\Container;
use SamlLdapSso\Ldap\Authenticator;
use SamlLdapSso\Ldap\ClientInterface;
use SamlLdapSso\Ldap\Connection;
use SamlLdapSso\Ldap\NativeClient;
use SamlLdapSso\Ldap\UserLookup;
use SamlLdapSso\Settings\Options;
use SamlLdapSso\Support\Logger;
use SamlLdapSso\Users\UserManager;

/**
 * LDAP client, lookup, and password authentication services.
 */
final class LdapServiceProvider implements ServiceProvider
{
    /**
     * @inheritDoc
     */
    public function register(Container $container): void
    {
        $container->singleton(ClientInterface::class, static fn () => new NativeClient());
        $container->singleton(
            Connection::class,
            static fn (Container $c) => new Connection($c->make(ClientInterface::class))
        );
        $container->singleton(
            UserLookup::class,
            static fn (Container $c) => new UserLookup(
                $c->make(ClientInterface::class),
                $c->make(Logger::class),
                $c->make(Connection::class),
                $c->make(Options::class)
            )
        );
        $container->singleton(
            Authenticator::class,
            static fn (Container $c) => new Authenticator(
                $c->make(ClientInterface::class),
                $c->make(Logger::class),
                $c->make(UserManager::class),
                $c->make(UserLookup::class),
                $c->make(Connection::class),
                $c->make(Options::class),
                $c->make(PasswordBypass::class)
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function boot(Container $container): void
    {
        $auth = $container->make(Authenticator::class);
        assert($auth instanceof Authenticator);

        add_filter(
            'authenticate',
            static function (mixed $user, mixed $username, #[\SensitiveParameter] mixed $password) use ($auth): mixed {
                return $auth->authenticate($user, (string) $username, (string) $password);
            },
            19,
            3
        );
    }
}
