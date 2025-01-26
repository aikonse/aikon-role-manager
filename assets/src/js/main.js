import '../css/main.css';

import { rolesInit } from './modules/roles';

window.addEventListener( 'load', () => {
	rolesInit({
			deleteSelector: '.delete_role_button',
			addRoleValidator: {
				name: {
					selector: '#role_name',
					reg: /^[a-zA-Z0-9_]+$/,
				},
				slug: {
					selector: '#role_slug',
					reg: /^[a-zA-Z0-9_]+$/,
				},
			}
	});
} );
