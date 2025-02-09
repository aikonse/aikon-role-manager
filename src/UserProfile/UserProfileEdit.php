<?php

namespace Aikon\RoleManager\UserProfile;

use Aikon\RoleManager\Manager\RoleManager;

use function Aikon\RoleManager\template;

class UserProfileEdit
{
    /** @var RoleManager */
    private $manager;

    protected string $form = 'arm_other_roles';

    public function __construct()
    {
        $this->manager = RoleManager::getInstance();

        add_action('show_user_profile', [$this, 'edit_user_profile'], 10, 1);
        add_action('edit_user_profile', [$this, 'edit_user_profile'], 10, 1);
        add_action('profile_update', [$this, 'handle_other_roles'], 10, 1);
        ;
    }

    /**
     * Check if the current user can edit roles
     *
     * @return boolean
     */
    private function can_edit_roles()
    {
        return current_user_can('promote_users');
    }

    /**
     * Render the user profile edit form
     *
     * @param \WP_User $user
     * @return void
     */
    public function edit_user_profile($user)
    {

        $user_roles = $user->roles;
        $roles = $this->manager->current_roles();
        $primary_role = array_shift($user_roles);
        $other_roles = $user_roles;

        if (isset($other_roles[$primary_role])) {
            unset($other_roles[$primary_role]);
        }

        template('userprofile-edit', [
            'primary_role' => $primary_role,
            'roles' => $roles,
            'user_other_roles' => $other_roles,
            'form' => $this->form,
            'can_edit_roles' => $this->can_edit_roles()
        ]);

    }

    /**
     * Handle saving user profile other roles
     *
     * @param integer $user_id
     * @return void
     */
    public function handle_other_roles(int $user_id)
    {
        if (!$this->can_edit_roles()) {
            return;
        }

        if (!isset($_POST[$this->form]) || !is_array($_POST[$this->form])) {
            return;
        }

        if (get_userdata($user_id) === false) {
            throw new \Exception('User not found');
        }

        /** @var string[] */
        $other_roles = $_POST[$this->form];

        $available_roles = $this->manager->current_roles();

        // Only allow roles that are available
        $other_roles = array_filter($other_roles, function ($role) use ($available_roles) {
            return in_array($role, array_keys($available_roles));
        });

        $this->manager->add_roles_to_user($user_id, $other_roles);
    }
}
