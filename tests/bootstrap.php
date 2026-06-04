<?php

require_once dirname(__DIR__) . '/vendor/antecedent/patchwork/Patchwork.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Minimal WP_Roles stub so RoleManager's typed property can be satisfied
// without a real WordPress environment.
if (! class_exists('WP_Roles')) {
    class WP_Roles
    {
        /** @var array<string, mixed> */
        public array $roles = [];
        /** @var array<string, string> */
        public array $role_names = [];
        /** @var array<string, mixed> */
        public array $role_objects = [];
        public string $role_key = 'user_roles';
    }
}
