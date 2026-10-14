<?php

declare(strict_types=1);

namespace Signclave\Providers;

if (!defined('ABSPATH')) {
    exit;
}

use Signclave\Admin\Diagnostics;
use Signclave\Admin\SettingsPage;
use Signclave\Admin\SiteHealth;
use Signclave\Assets\Admin as AdminAssets;
use Signclave\Assets\ViteManifest;
use Signclave\Auth\PasswordBypass;
use Signclave\Container;
use Signclave\Ldap\ClientInterface;
use Signclave\Ldap\Connection;
use Signclave\Saml\Metadata;
use Signclave\Settings\Options;
use Signclave\Support\Clock;
use Signclave\Support\Logger;
use Signclave\Support\View;

/**
 * Admin UI, diagnostics, and asset services.
 */
final class AdminServiceProvider implements ServiceProvider
{
    /**
     * @inheritDoc
     */
    public function register(Container $container): void
    {
        $container->singleton(ViteManifest::class, static fn () => new ViteManifest());
        $container->singleton(
            AdminAssets::class,
            static fn (Container $c) => new AdminAssets($c->make(ViteManifest::class))
        );
        $container->singleton(
            SettingsPage::class,
            static fn (Container $c) => new SettingsPage(
                $c->make(Logger::class),
                $c->make(Options::class),
                $c->make(Metadata::class),
                $c->make(PasswordBypass::class),
                $c->make(View::class)
            )
        );
        $container->singleton(
            Diagnostics::class,
            static fn (Container $c) => new Diagnostics(
                $c->make(Logger::class),
                $c->make(ClientInterface::class),
                $c->make(Connection::class),
                $c->make(Options::class),
                $c->make(Metadata::class),
                $c->make(Clock::class)
            )
        );
        $container->singleton(
            SiteHealth::class,
            static fn (Container $c) => new SiteHealth(
                $c->make(Options::class),
                $c->make(Connection::class)
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function boot(Container $container): void
    {
        $assets = $container->make(AdminAssets::class);
        $page = $container->make(SettingsPage::class);
        $diag = $container->make(Diagnostics::class);
        $health = $container->make(SiteHealth::class);
        assert($assets instanceof AdminAssets);
        assert($page instanceof SettingsPage);
        assert($diag instanceof Diagnostics);
        assert($health instanceof SiteHealth);
        $assets->registerHooks();
        $page->registerHooks();
        $diag->registerHooks();
        $health->registerHooks();
    }
}
