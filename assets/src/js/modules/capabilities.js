import { toggleAccordion } from "./accordion";
import { quickFilter } from "./filter";
import { addCapabilityFromList, addNewCapability } from "./add-capability";
import { capabilityToggle } from "./toggle-capability";
import { initToggleAllCapabilities } from "./toolbar_actions-capability";


/**
 * @typedef {Object} DefaultConfig
 * @property {Object} quickFilter - Configuration for the quick filter.
 * @property {string} quickFilter.inputSelector - Selector for the quick filter input element.
 * @property {string} quickFilter.listItemsSelector - Selector for the list items to be filtered.
 * @property {string} quickFilter.wrapperSelector - Selector for the wrapper element containing the list.
 * @property {Object} addNewCapability - Configuration for adding a new capability.
 * @property {string} addNewCapability.inputSelector - Selector for the input element for adding a new capability.
 * @property {string} addNewCapability.addBtnSelector - Selector for the button to add a new capability.
 * @property {string} capabilityListSelector - Selector for the capability list.
 * @property {string} accordionToggleSelector - Selector for the accordion toggle elements.
 * 
 * @param {DefaultConfig} config
 */
export function capabilitiesInit( config ) {
	
	const defaultConfig = {
		quickFilter: {
			inputSelector: '#capability-filter',
			listItemsSelector: '#capability-list li, #add-capabilities-list li',
			wrapperSelector: '#nav-menus-frame'
		},
		capabilityListSelector: '#capability-list',
		capabilityListToggleSelector: 'button',
		addNewCapability: {
			inputSelector: '#new-capability',
			addBtnSelector: '#add-capability',
		},
		addCapabilityFromList: {
			checkboxSelector: '#add-capabilities-list input[type="checkbox"]',
			submitButtonSelector: '#add-capabilities',
		},
		accordionToggleSelector: '.arm-postbox-accordion-header',

	};

	config = {
        ...defaultConfig,
        ...config,
    };

	addNewCapability(
		config.addNewCapability.inputSelector, 
		config.addNewCapability.addBtnSelector, 
		config.capabilityListSelector
	);
	addCapabilityFromList(
		config.addCapabilityFromList.checkboxSelector, 
		config.addCapabilityFromList.submitButtonSelector, 
		config.capabilityListSelector
	);
	capabilityToggle(
		config.capabilityListSelector, 
		config.capabilityListToggleSelector
	)
	quickFilter(
		config.quickFilter.inputSelector, 
		config.quickFilter.listItemsSelector,
		config.quickFilter.wrapperSelector
	);

	toggleAccordion(config.accordionToggleSelector);
	initToggleAllCapabilities();
}