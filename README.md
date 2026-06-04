# Aikon Role Manager

Aikon Role Manager empowers you to take full control of user roles and capabilities in WordPress. With this plugin, you can manage roles responsibly, define custom capabilities, and assign multiple roles to users effortlessly.

## Features

### Manage Roles

- Create, edit, and delete user roles with ease.
- Safeguard your site by managing roles responsibly – the plugin warns against removing roles that might lock you out of your website.
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

## Development

This project leverages the [WordPress Environment](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) (`wordpress/env`) for streamlined development and testing.

### Tools for a Clean and Bug-Free Codebase

Ensure a robust and maintainable plugin with these tools:

- **XDebug**: Debug your PHP code with ease.
- **Composer**: Manage dependencies and libraries efficiently.
- **PHPStan**: Perform static analysis for improved code quality.
- **PHP CS Fixer**: Automatically fix coding standards to ensure consistency.
- **Rector**: Refactor and modernize your codebase effortlessly.

### Prerequisites

- WordPress development environment ([WordPress Environment](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/))
- PHP 8.0 or higher
- Composer installed globally
- Docker
- Node

### Development Workflow

1. Start the WordPress development environment:

   ```bash
   npm run wp:start
   ```

2. Make changes to the codebase.
3. Run quality assurance tools:

   ```bash
   composer rector
   composer phpstan
   composer phpcs:fix
   ```

4. Test your plugin in the local environment.

### Testing

The plugin has two test suites managed by PHPUnit.

**Unit tests** run without WordPress using [Brain Monkey](https://brain-wp.github.io/BrainMonkey/) to mock WP functions. No Docker required.

```bash
./vendor/bin/phpunit
```

**Integration tests** run inside the wp-env test container against a real WordPress installation. Start the test environment first, then run the suite:

```bash
npm run wp:test:start
npm run test:integration
```

Run both suites in one command:

```bash
npm run test:all
```

Stop the test environment when you are done:

```bash
npm run wp:test:stop
```

---

## Contributing

We welcome contributions to improve the Aikon Role Manager! Feel free to submit issues or create pull requests on our [GitHub repository](https://github.com/aikonse/aikon-role-manager).

### Guidelines

- Use the CI tools
- Write clear and concise commit messages.

## License

This project is licensed under the [MIT License](https://opensource.org/license/mit).

## Support

If you encounter issues or have questions, please reach out via our [support page](https://github.com/aikonse/role-manager/issues) or contact us directly through the WordPress plugin directory.
