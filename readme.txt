=== Aikon Role Manager ===
Contributors: andreas-andersson
Tags: User, Role, Security
Tested up to: 7.2
Requires at least: 6.5
Stable Tag: 1.1.3
Requires PHP: 8.0
License: MIT
License URI: https://opensource.org/license/mit

Aikon Role Manager empowers you to take full control of user roles and capabilities in WordPress.

== Description ==

With this plugin, you can manage roles responsibly, define custom capabilities, and assign multiple roles to users effortlessly.

## Features

### Manage Roles
- Create, edit, duplicate and delete user roles with ease.
- Maintain flexibility and control over your site's access permissions.

### Manage Capabilities
- Add custom capabilities or modify existing ones.
- Enable, disable, add, or remove capabilities seamlessly.
- Tailor the permissions to suit your site's specific requirements.

### Assign Multiple Roles to a Single User
- Enhance user flexibility by allowing multiple roles per user.
- Combine capabilities from different roles to create a customized experience for each user.

### User Switcher
- Log in as any other user directly from the Users list with a single click.
- A clear indicator in the admin bar shows when you are operating as a switched user.
- Switch back to your original account instantly at any time.
- Only users with the ability to edit other users can switch, and you can only switch to users you could otherwise edit — preventing privilege escalation.

== Screenshots ==

1. Create, edit, duplicate and delete Roles
2. Manage role capabilities
3. Protect critical roles
4. Override default post type capabilities
5. Override default post type capabilities - Form
6. Configure your security settings
7. Allow multiple roles on a single user
8. Simple login as your users
9. Switch back in the wp admin bar

== Changelog ==

= 1.1.3 =
This is a security release. Updating is strongly recommended.

* Security: All actions on the Role Manager page are now protected against cross-site request forgery (CSRF)
* Security: The user switcher's switch-back cookie is replaced with a random, single-use token stored on the server. It only works for the user you switched to and is cleared on logout and login
* Security: On multisite, only super admins can grant or revoke network-level and super-admin-only capabilities. These are shown as locked to site administrators
* Security: Additional roles on the user profile now follow the same rules as core: only editable roles can be changed, and users can no longer give themselves roles with capabilities they don't already have
* Fix: Saving a user from anywhere other than the profile form (e.g. the REST API or another plugin) no longer removes the user's additional roles
* Fix: The Delete button on the role edit screen now works
* Fix: Switching to another user is refused while already switched
* Change: If you are switched to another user when updating, you will need to log in again as yourself
* Change: "Check all" and "Uncheck all" on the capabilities screen skip capabilities that can't be changed

= 1.1.2 =
* Tested with Wordpress 7.2
* Feature: Small UI update, making capabilities marked for removal more visible
* Feature: More secure default protection settings

= 1.1.1 =
* Fix: Issue with restoring a post type capabilities solved
* Feature: Duplicate roles
* Tested with Wordpress 7.1 

= 1.1.0 =
* Feature: Override post type capabilities
* Feature: Settings panel to set up protected post typs and roles to prevent changes

= 1.0.7 =
* Tested with Wordpress 7.0

= 1.0.6 =
* Tested with Wordpress 6.9

= 1.0.4 =
* Added blueprint.json to enable wp.org previews
* Added warning when editing curent users role

= 1.0.3 =
* Added banner, icon and screenshots

= 1.0.1 =
* Fix issue in deployment

= 1.0.0 =
* First version
