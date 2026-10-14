<?php

declare(strict_types=1);

namespace Signclave\Providers;

if (!defined('ABSPATH')) {
    exit;
}

use Signclave\Auth\PasswordBypass;
use Signclave\Container;
use Signclave\Ldap\Authenticator;
use Signclave\Ldap\ClientInterface;
use Signclave\Ldap\Connection;
use Signclave\Ldap\NativeClient;
use Signclave\Ldap\UserLookup;
use Signclave\Settings\Options;
use Signclave\Support\Logger;
use Signclave\Users\UserManager;

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
