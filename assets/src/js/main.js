import '../css/main.css';

import { rolesInit } from './modules/roles';
import { capabilitiesInit } from './modules/capabilities';

window.addEventListener( 'load', () => {
	rolesInit();
	capabilitiesInit();
} );
