<?php

declare(strict_types=1);

use SamlLdapSso\Tests\Support\OptionStore;

it('deletes sls_options, the prune hook, and log files on uninstall', function () {
    $dir = trailingslashit(sys_get_temp_dir()) . 'saml-ldap-sso';
    if (! is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $log = $dir . '/sls-test-uninstall.log';
    file_put_contents($log, "line\n");

    OptionStore::seed([
        'sls_options' => [
            'general' => [
                'log_file' => 'sls-test-uninstall.log',
                'audit_log_file' => '',
            ],
        ],
    ]);

    if (! defined('WP_UNINSTALL_PLUGIN')) {
        define('WP_UNINSTALL_PLUGIN', true);
    }

    require dirname(__DIR__, 2) . '/uninstall.php';

    expect(get_option('sls_options', false))->toBeFalse()
        ->and($GLOBALS['sls_test_cleared_hooks'] ?? [])->toContain('sls_prune_logs')
        ->and(is_file($log))->toBeFalse();
});
