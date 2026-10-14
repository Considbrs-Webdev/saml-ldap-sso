<?php

declare(strict_types=1);

namespace Signclave\Users;

if (!defined('ABSPATH')) {
    exit;
}

use Signclave\Settings\Options;

/**
 * Map IdP/LDAP group names or DNs to WordPress roles.
 */
final class RoleMapper
{
    /**
     * @param Options $options Plugin options service.
     */
    public function __construct(
        private readonly Options $options
    ) {
    }

    /**
     * Resolve WordPress roles from configured group mappings.
     *
     * @param list<string> $groups Directory or assertion group identifiers.
     * @param string       $source Mapping source filter ("ldap", "saml", etc.).
     *
     * @return list<string>
     */
    public function resolveRoles(array $groups, string $source): array
    {
        $mappings = $this->options->get('roles', 'mappings', []);
        if (!is_array($mappings) || empty($mappings)) {
            return [];
        }

        $normalized = $this->normalizedKeys($groups);
        $matched = [];

        foreach ($mappings as $row) {
            if (!is_array($row)) {
                continue;
            }
            $rowSource = (string) ($row['source'] ?? 'any');
            $rowGroup = strtolower(trim((string) ($row['group'] ?? '')));
            $rowRole = sanitize_key((string) ($row['role'] ?? ''));
            if ('' === $rowGroup || '' === $rowRole) {
                continue;
            }
            if ('any' !== $rowSource && $source !== $rowSource) {
                continue;
            }
            if (in_array($rowGroup, $normalized, true)) {
                $matched[] = $rowRole;
                if ('first' === $this->options->get('general', 'role_strategy', 'highest')) {
                    break;
                }
            }
        }

        return $this->knownSiteRoles(array_values(array_unique($matched)));
    }

    /**
     * Drop mapped roles that do not exist on this WordPress site.
     *
     * @param list<string> $roles Candidate role slugs.
     *
     * @return list<string>
     */
    private function knownSiteRoles(array $roles): array
    {
        if (! function_exists('wp_roles')) {
            return $roles;
        }

        $known = array_map('strval', array_keys(wp_roles()->role_names));
        if ([] === $known) {
            return $roles;
        }

        return array_values(array_filter(
            $roles,
            static fn (string $role): bool => in_array($role, $known, true)
        ));
    }

    /**
     * Normalize group identifiers for case-insensitive matching.
     *
     * @param list<string> $groups Raw group names or DNs.
     *
     * @return list<string>
     */
    private function normalizedKeys(array $groups): array
    {
        $keys = [];
        foreach ($groups as $group) {
            $g = strtolower(trim((string) $group));
            $keys[] = $g;
            if (preg_match('/cn=([^,]+)/i', $g, $m)) {
                $keys[] = trim($m[1]);
            }
        }

        return array_values(array_unique($keys));
    }
}
