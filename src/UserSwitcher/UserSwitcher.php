<?php

declare(strict_types=1);

namespace Aikon\RoleManager\UserSwitcher;

class UserSwitcher
{
    private const COOKIE_NAME   = 'aikon_role_manager_user_switcher';
    private const ARG_ACTION    = 'aikon_role_manager_switch_action';
    private const ARG_USER      = 'aikon_role_manager_switch_user';
    private const META_KEY      = '_aikon_role_manager_switch_session';
    private const SESSION_TTL   = 3600;

    public function __construct()
    {
        add_filter('user_row_actions', [$this, 'add_switch_action'], 10, 2);
        add_action('admin_bar_menu', [$this, 'add_admin_bar_item'], 100);
        add_action('admin_head', [$this, 'add_admin_bar_styles']);
        add_action('init', [$this, 'handle_switch_request']);
        add_action('wp_logout', [$this, 'discard_switch_session']);
        add_action('wp_login', [$this, 'discard_switch_session']);
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

        if ($this->is_switched()) {
            wp_die(esc_html__('Switch back to your own account before switching to another user.', 'aikon-role-manager'));
        }

        $target_id = (
            isset($_GET[self::ARG_USER]) &&
            is_string($_GET[self::ARG_USER])
        )
            ? absint($_GET[self::ARG_USER])
            : 0;

        if (!$target_id || !wp_verify_nonce($this->nonce_from_request(), 'aikon_role_manager_switch_' . $target_id)) {
            wp_die(esc_html__('Security check failed.', 'aikon-role-manager'));
        }

        if (!$this->can_switch_to($target_id)) {
            wp_die(esc_html__('You do not have permission to switch to this user.', 'aikon-role-manager'));
        }

        if (!get_userdata($target_id)) {
            wp_die(esc_html__('User not found.', 'aikon-role-manager'));
        }

        $cookie_value = $this->create_switch_session(get_current_user_id(), $target_id);

        // The original session's auth cookie is about to be replaced, so drop
        // its server-side token instead of leaving it orphaned.
        wp_destroy_current_session();

        $this->set_switcher_cookie($cookie_value);

        wp_set_current_user($target_id);
        wp_set_auth_cookie($target_id, false);

        wp_safe_redirect(admin_url());
        exit;
    }

    /**
     * Read and sanitize the _wpnonce GET parameter, returning an empty string
     * if the key is absent or not a string (PHPStan: $_GET values are mixed).
     */
    private function nonce_from_request(): string
    {
        $raw = $_GET['_wpnonce'] ?? null;
        return is_string($raw) ? sanitize_text_field(wp_unslash($raw)) : '';
    }

    private function handle_switch_back(): void
    {
        $original_user_id = $this->get_original_user_id();

        if ($original_user_id === null) {
            wp_die(esc_html__('No active user switch session found.', 'aikon-role-manager'));
        }

        if (!wp_verify_nonce($this->nonce_from_request(), 'aikon_role_manager_switch_back_' . $original_user_id)) {
            wp_die(esc_html__('Security check failed.', 'aikon-role-manager'));
        }

        $original_user = get_userdata($original_user_id);

        // The token is single use: invalidate it before doing anything else
        $this->end_switch_session($original_user_id);

        if (!$original_user) {
            wp_die(esc_html__('Original user not found.', 'aikon-role-manager'));
        }

        // Destroy the impersonation session so it can't be reused
        wp_destroy_current_session();

        wp_set_current_user($original_user_id);
        wp_set_auth_cookie($original_user_id, false);

        wp_safe_redirect(admin_url('users.php'));
        exit;
    }

    // -------------------------------------------------------------------------
    // Switch session
    //
    // The cookie holds "{original_user_id}|{random token}". Only an HMAC of the
    // token is stored, in the original user's meta, together with the user that
    // was switched to and an expiry. A session is valid only while the current
    // user is that target, and it is deleted on switch back, logout and login.
    // -------------------------------------------------------------------------

    private function is_switched(): bool
    {
        return $this->get_original_user_id() !== null;
    }

    /**
     * The original user's ID, if the current user is the target of a valid switch session.
     */
    private function get_original_user_id(): ?int
    {
        $session = $this->get_switch_session();

        if ($session === null || $session['target'] !== get_current_user_id()) {
            return null;
        }

        return $session['original'];
    }

    /**
     * Validate the cookie against the stored session.
     *
     * @return array{original: int, target: int}|null
     */
    private function get_switch_session(): ?array
    {
        $raw_cookie = $_COOKIE[self::COOKIE_NAME] ?? null;
        if (!is_string($raw_cookie)) {
            return null;
        }

        $parts = explode('|', sanitize_text_field(wp_unslash($raw_cookie)), 2);
        if (count($parts) !== 2 || $parts[1] === '') {
            return null;
        }

        $original_user_id = absint($parts[0]);
        if (!$original_user_id) {
            return null;
        }

        $stored = get_user_meta($original_user_id, self::META_KEY, true);
        if (
            !is_array($stored) ||
            !isset($stored['hash'], $stored['target'], $stored['expires']) ||
            !is_string($stored['hash']) ||
            !is_int($stored['target']) ||
            !is_int($stored['expires'])
        ) {
            return null;
        }

        // Constant-time comparison to prevent timing attacks
        if (!hash_equals($stored['hash'], $this->hash_token($parts[1]))) {
            return null;
        }

        if ($stored['expires'] < time()) {
            delete_user_meta($original_user_id, self::META_KEY);
            return null;
        }

        return [
            'original' => $original_user_id,
            'target'   => $stored['target'],
        ];
    }

    /**
     * Store a new switch session and return the cookie value for it.
     * Replaces any earlier session started by the same user.
     */
    private function create_switch_session(int $original_user_id, int $target_user_id): string
    {
        $token = wp_generate_password(64, false, false);

        update_user_meta($original_user_id, self::META_KEY, [
            'hash'    => $this->hash_token($token),
            'target'  => $target_user_id,
            'expires' => time() + self::SESSION_TTL,
        ]);

        return $original_user_id . '|' . $token;
    }

    private function end_switch_session(int $original_user_id): void
    {
        delete_user_meta($original_user_id, self::META_KEY);
        $this->clear_switcher_cookie();
    }

    /**
     * Drop the switch session held by this browser, if any. Runs on logout
     * and login so a switch cookie can't outlive the session it belongs to.
     */
    public function discard_switch_session(): void
    {
        if (!isset($_COOKIE[self::COOKIE_NAME])) {
            return;
        }

        $session = $this->get_switch_session();
        if ($session !== null) {
            delete_user_meta($session['original'], self::META_KEY);
        }

        $this->clear_switcher_cookie();
    }

    private function hash_token(string $token): string
    {
        return hash_hmac('sha256', $token, wp_salt('auth'));
    }

    private function set_switcher_cookie(string $value): void
    {
        if (!headers_sent()) {
            setcookie(self::COOKIE_NAME, $value, [
                'expires'  => time() + self::SESSION_TTL,
                'path'     => COOKIEPATH,
                'domain'   => COOKIE_DOMAIN,
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
        }

        $_COOKIE[self::COOKIE_NAME] = $value;
    }

    private function clear_switcher_cookie(): void
    {
        if (!headers_sent()) {
            setcookie(self::COOKIE_NAME, '', [
                'expires'  => time() - self::SESSION_TTL,
                'path'     => COOKIEPATH,
                'domain'   => COOKIE_DOMAIN,
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
        }

        unset($_COOKIE[self::COOKIE_NAME]);
    }
}
