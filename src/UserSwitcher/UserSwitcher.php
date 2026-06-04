<?php

declare(strict_types=1);

namespace Aikon\RoleManager\UserSwitcher;

class UserSwitcher
{
    private const COOKIE_NAME   = 'aikon_role_manager_user_switcher';
    private const ARG_ACTION    = 'aikon_role_manager_switch_action';
    private const ARG_USER      = 'aikon_role_manager_switch_user';

    public function __construct()
    {
        add_filter('user_row_actions',  [$this, 'add_switch_action'], 10, 2);
        add_action('admin_bar_menu',    [$this, 'add_admin_bar_item'], 100);
        add_action('admin_head',        [$this, 'add_admin_bar_styles']);
        add_action('init',              [$this, 'handle_switch_request']);
    }

    // -------------------------------------------------------------------------
    // Capability checks
    // -------------------------------------------------------------------------

    private function can_switch(): bool
    {
        return current_user_can('edit_users');
    }

    /**
     * A user may only be impersonated if the acting user could normally edit them.
     * This prevents, e.g., an editor-level account from being switched to an admin.
     */
    private function can_switch_to(int $target_user_id): bool
    {
        return current_user_can('edit_user', $target_user_id);
    }

    // -------------------------------------------------------------------------
    // User list row action: "Login as"
    // -------------------------------------------------------------------------

    /**
     * @param array<string,string> $actions
     * @param \WP_User $user
     * @return array<string,string>
     */
    public function add_switch_action(array $actions, \WP_User $user): array
    {
        if (!$this->can_switch() || $this->is_switched()) {
            return $actions;
        }

        if ($user->ID === get_current_user_id()) {
            return $actions;
        }

        if (!$this->can_switch_to($user->ID)) {
            return $actions;
        }

        $url = wp_nonce_url(
            add_query_arg(
                [
                    self::ARG_ACTION => 'switch',
                    self::ARG_USER   => $user->ID,
                ],
                admin_url('users.php')
            ),
            'aikon_role_manager_switch_' . $user->ID
        );

        $actions['aikon_role_manager_switch'] = sprintf(
            '<a href="%s">%s</a>',
            esc_url($url),
            esc_html__('Login as', 'aikon-role-manager')
        );

        return $actions;
    }

    // -------------------------------------------------------------------------
    // Admin bar: switched-user indicator + switch-back link
    // -------------------------------------------------------------------------

    public function add_admin_bar_item(\WP_Admin_Bar $wp_admin_bar): void
    {
        if (!$this->is_switched()) {
            return;
        }

        $original_user_id = $this->get_original_user_id();
        if ($original_user_id === null) {
            return;
        }

        $original_user = get_userdata($original_user_id);
        if (!$original_user) {
            return;
        }

        $current_user = wp_get_current_user();

        $wp_admin_bar->add_node([
            'id'    => 'aikon-role-manager-user-switcher',
            'title' => sprintf(
                /* translators: %s: display name of the currently impersonated user */
                esc_html__('User Switcher: %s', 'aikon-role-manager'),
                esc_html($current_user->display_name)
            ),
            'meta'  => ['class' => 'aikon-role-manager-user-switcher-active'],
        ]);

        $switch_back_url = wp_nonce_url(
            add_query_arg(
                [self::ARG_ACTION => 'switch_back'],
                admin_url()
            ),
            'aikon_role_manager_switch_back_' . $original_user_id
        );

        $wp_admin_bar->add_node([
            'id'     => 'aikon-role-manager-switch-back',
            'parent' => 'aikon-role-manager-user-switcher',
            'title'  => sprintf(
                /* translators: %s: display name of the original (admin) user */
                esc_html__('Switch back to %s', 'aikon-role-manager'),
                esc_html($original_user->display_name)
            ),
            'href'   => esc_url($switch_back_url),
        ]);
    }

    public function add_admin_bar_styles(): void
    {
        if (!$this->is_switched()) {
            return;
        }

        echo '<style>
            #wp-admin-bar-aikon-role-manager-user-switcher > .ab-item {
                background: #b32d2e !important;
                color: #fff !important;
            }
        </style>';
    }

    // -------------------------------------------------------------------------
    // Switch request handler (runs on init)
    // -------------------------------------------------------------------------

    public function handle_switch_request(): void
    {
        if (!isset($_GET[self::ARG_ACTION]) || !is_string($_GET[self::ARG_ACTION])) {
            return;
        }

        $action = sanitize_key($_GET[self::ARG_ACTION]);

        if ($action === 'switch') {
            $this->handle_switch();
        } elseif ($action === 'switch_back') {
            $this->handle_switch_back();
        }
    }

    private function handle_switch(): void
    {
        if (!$this->can_switch()) {
            wp_die(esc_html__('You do not have permission to switch users.', 'aikon-role-manager'));
        }

        $target_id = isset($_GET[self::ARG_USER]) ? absint($_GET[self::ARG_USER]) : 0;

        if (!$target_id || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'] ?? '')), 'aikon_role_manager_switch_' . $target_id)) {
            wp_die(esc_html__('Security check failed.', 'aikon-role-manager'));
        }

        if (!$this->can_switch_to($target_id)) {
            wp_die(esc_html__('You do not have permission to switch to this user.', 'aikon-role-manager'));
        }

        if (!get_userdata($target_id)) {
            wp_die(esc_html__('User not found.', 'aikon-role-manager'));
        }

        $this->set_switcher_cookie(get_current_user_id());

        wp_set_current_user($target_id);
        wp_set_auth_cookie($target_id, false);

        wp_safe_redirect(admin_url());
        exit;
    }

    private function handle_switch_back(): void
    {
        $original_user_id = $this->get_original_user_id();

        if ($original_user_id === null) {
            wp_die(esc_html__('No active user switch session found.', 'aikon-role-manager'));
        }

        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'] ?? '')), 'aikon_role_manager_switch_back_' . $original_user_id)) {
            wp_die(esc_html__('Security check failed.', 'aikon-role-manager'));
        }

        $original_user = get_userdata($original_user_id);

        if (!$original_user) {
            $this->clear_switcher_cookie();
            wp_die(esc_html__('Original user not found.', 'aikon-role-manager'));
        }

        $this->clear_switcher_cookie();

        wp_set_current_user($original_user_id);
        wp_set_auth_cookie($original_user_id, false);

        wp_safe_redirect(admin_url('users.php'));
        exit;
    }

    // -------------------------------------------------------------------------
    // Signed cookie
    // -------------------------------------------------------------------------

    private function is_switched(): bool
    {
        return $this->get_original_user_id() !== null;
    }

    private function get_original_user_id(): ?int
    {
        if (!isset($_COOKIE[self::COOKIE_NAME])) {
            return null;
        }

        $raw   = sanitize_text_field(wp_unslash($_COOKIE[self::COOKIE_NAME]));
        $parts = explode('|', $raw, 2);

        if (count($parts) !== 2) {
            return null;
        }

        $user_id = absint($parts[0]);

        if (!$user_id) {
            return null;
        }

        // Constant-time comparison to prevent timing attacks
        if (!hash_equals(wp_hash($user_id . \AUTH_SALT), $parts[1])) {
            return null;
        }

        return $user_id;
    }

    private function set_switcher_cookie(int $original_user_id): void
    {
        $value = $original_user_id . '|' . wp_hash($original_user_id . \AUTH_SALT);

        setcookie(self::COOKIE_NAME, $value, [
            'expires'  => time() + HOUR_IN_SECONDS,
            'path'     => COOKIEPATH,
            'domain'   => COOKIE_DOMAIN,
            'secure'   => is_ssl(),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        $_COOKIE[self::COOKIE_NAME] = $value;
    }

    private function clear_switcher_cookie(): void
    {
        setcookie(self::COOKIE_NAME, '', [
            'expires'  => time() - HOUR_IN_SECONDS,
            'path'     => COOKIEPATH,
            'domain'   => COOKIE_DOMAIN,
            'secure'   => is_ssl(),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        unset($_COOKIE[self::COOKIE_NAME]);
    }
}
