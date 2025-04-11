/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./assets/src/js/modules/accordion.js":
/*!********************************************!*\
  !*** ./assets/src/js/modules/accordion.js ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   toggleAccordion: () => (/* binding */ toggleAccordion)
/* harmony export */ });
/**
 * Toggle accordion
 * @param {string} toggleSelector Accordion toggle selector
 * @returns {void}
 */
function toggleAccordion(toggleSelector) {
  const accordions = document.querySelectorAll(toggleSelector);
  accordions.forEach(accordion => {
    accordion.addEventListener('click', () => {
      const parent = accordion.parentElement;
      const is_open = parent.getAttribute('aria-expanded') === 'true';
      parent.setAttribute('aria-expanded', !is_open ? 'true' : 'false');
    });
  });
}

/***/ }),

/***/ "./assets/src/js/modules/add-capability.js":
/*!*************************************************!*\
  !*** ./assets/src/js/modules/add-capability.js ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   addCapabilityFromList: () => (/* binding */ addCapabilityFromList),
/* harmony export */   addNewCapability: () => (/* binding */ addNewCapability)
/* harmony export */ });
/**
 * Capability check box name.
 */
const checkBoxName = 'role_caps';

/**
 * Add capability to the list.
 * @param {string} inputSelector Input selector.
 * @param {string} addBtnSelector Add button selector.
 * @param {string} listSelector List selector.
 * @returns void
 */
function addNewCapability(inputSelector, addBtnSelector, listSelector) {
  const input = document.querySelector(inputSelector);
  const addBtn = document.querySelector(addBtnSelector);
  const capabilitiesList = document.querySelector(listSelector);
  addBtn.addEventListener('click', function () {
    input.reportValidity();
    if (!input.validity.valid) {
      return;
    }
    capabilitiesList.appendChild(createCapabilityItem(input.value.trim(), checkBoxName));
    input.value = '';
  });
}

/**
 * Create a new capability item.
 * @param {string} capability 
 * @returns HtmlElement
 */
function createCapabilityItem(capability, inputName) {
  const name = inputName + '[ ' + capability + ' ]';
  const capabilityItem = document.createElement('li');
  capabilityItem.innerHTML = `<input type="hidden" name="${name}" value="0"> 
        <label>
            <input type="checkbox" name="${name}" value="1">
            ${capability}
        </label>`;
  return capabilityItem;
}
function addCapabilityFromList(checkboxSelector, submitButtonSelector, listSelector) {
  /** @type {NodeListOf<HTMLInputElement>} */
  const checkBoxes = document.querySelectorAll(checkboxSelector);

  /** @type {HTMLButtonElement} */
  const submitButton = document.querySelector(submitButtonSelector);

  /** @type {HTMLUListElement} */
  const list = document.querySelector(listSelector);
  if (!checkBoxes || !submitButton || !list) {
    console.warn('Aikon Role Manager: One or more elements not found in addCapabilityFromList()');
    return;
  }
  submitButton.disabled = true;
  const checkHasChecked = () => {
    // Loop through all checkboxes and check if any is checked
    const checkedCount = [...checkBoxes].filter(input => input.checked).length;
    console.log(checkedCount);
    return checkedCount > 0;
  };
  checkBoxes.forEach(box => {
    box.addEventListener('change', () => {
      submitButton.disabled = !checkHasChecked();
    });
  });
  submitButton.addEventListener('click', e => {
    const checked = [...checkBoxes].filter(input => input.checked);
    checked.forEach(checkbox => {
      const capname = checkbox.parentElement.textContent.trim();
      list.appendChild(createCapabilityItem(capname, checkbox.name));
      checkbox.checked = false;
      checkbox.disabled = true;
    });
    submitButton.disabled = true;
  });
}

/***/ }),

/***/ "./assets/src/js/modules/capabilities.js":
/*!***********************************************!*\
  !*** ./assets/src/js/modules/capabilities.js ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   capabilitiesInit: () => (/* binding */ capabilitiesInit)
/* harmony export */ });
/* harmony import */ var _accordion__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./accordion */ "./assets/src/js/modules/accordion.js");
/* harmony import */ var _filter__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./filter */ "./assets/src/js/modules/filter.js");
/* harmony import */ var _add_capability__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./add-capability */ "./assets/src/js/modules/add-capability.js");




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
function capabilitiesInit(config) {
  const defaultConfig = {
    quickFilter: {
      inputSelector: '#capability-filter',
      listItemsSelector: '#capability-list li, #add-capabilities-list li',
      wrapperSelector: '#nav-menus-frame'
    },
    capabilityListSelector: '#capability-list',
    addNewCapability: {
      inputSelector: '#new-capability',
      addBtnSelector: '#add-capability'
    },
    addCapabilityFromList: {
      checkboxSelector: '#add-capabilities-list input[type="checkbox"]',
      submitButtonSelector: '#add-capabilities'
    },
    accordionToggleSelector: '.arm-postbox-accordion-header'
  };
  config = {
    ...defaultConfig,
    ...config
  };
  (0,_add_capability__WEBPACK_IMPORTED_MODULE_2__.addNewCapability)(config.addNewCapability.inputSelector, config.addNewCapability.addBtnSelector, config.capabilityListSelector);
  (0,_add_capability__WEBPACK_IMPORTED_MODULE_2__.addCapabilityFromList)(config.addCapabilityFromList.checkboxSelector, config.addCapabilityFromList.submitButtonSelector, config.capabilityListSelector);
  //addCapability();
  (0,_filter__WEBPACK_IMPORTED_MODULE_1__.quickFilter)(config.quickFilter.inputSelector, config.quickFilter.listItemsSelector, config.quickFilter.wrapperSelector);
  (0,_accordion__WEBPACK_IMPORTED_MODULE_0__.toggleAccordion)(config.accordionToggleSelector);
}

/***/ }),

/***/ "./assets/src/js/modules/filter.js":
/*!*****************************************!*\
  !*** ./assets/src/js/modules/filter.js ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   quickFilter: () => (/* binding */ quickFilter)
/* harmony export */ });
/**
 * Filter capabilities
 * @param {NodeList} capabilityListItems List items to filter
 * @param {String} search Search string
 * @returns {void}
 */
function filterCapabilities(capabilityListItems, search) {
  const capabilities = capabilityListItems;
  capabilities.forEach(function (capability) {
    capability.style.display = 'block';
  });
  if (!search) {
    return;
  }
  capabilities.forEach(function (capability) {
    const text = capability.textContent.toLowerCase();
    const match = text.indexOf(search) !== -1;
    capability.style.display = match ? 'block' : 'none';
  });
}

/**
 * Quick filter for list items
 * @param {String} searchFormSelector Search form selector, expects single element
 * @param {String} listItemsSelector List items selector, expects multiple elements
 * @param {String} wrapperSelector Wrapper selector, expects single element
 * @returns {void}
 */
function quickFilter(searchFormSelector, listItemsSelector, wrapperSelector) {
  const capabilitySearch = document.querySelector(searchFormSelector);
  const capabilityListItems = document.querySelectorAll(listItemsSelector);
  const wrapper = document.querySelector(wrapperSelector);
  if (!capabilitySearch || !capabilityListItems) {
    return;
  }
  capabilitySearch.addEventListener('input', _e => {
    const search = capabilitySearch.value.toLowerCase();
    if (search.length < 2) {
      filterCapabilities(capabilityListItems, null);
      wrapper.setAttribute('aria-expand-all', 'false');
      return;
    }
    filterCapabilities(capabilityListItems, search);
    wrapper.setAttribute('aria-expand-all', 'true');
  });
}


/***/ }),

/***/ "./assets/src/js/modules/roles.js":
/*!****************************************!*\
  !*** ./assets/src/js/modules/roles.js ***!
  \****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   deleteRole: () => (/* binding */ deleteRole),
/* harmony export */   rolesInit: () => (/* binding */ rolesInit)
/* harmony export */ });
/**
 * @param {PointerEvent} e 
 */
function deleteRole(e) {
  const link = e.target;
  const url = link.dataset.action;
  const msg = link.dataset.confirmationmessage;
  if (!confirm(msg)) {
    return;
  }
  window.location = url;
}

/**
 * @typedef {Object} DefaultConfig
 * @property {string} deleteSelector - Selectior for the delete role button.
 *
 * @param {object} config 
 */
function rolesInit(config) {
  const defaultConfig = {
    deleteSelector: '.delete_role_button'
  };
  config = {
    ...defaultConfig,
    ...config
  };
  const deleteLinks = document.querySelectorAll(config.deleteSelector);
  deleteLinks.forEach(link => {
    link.addEventListener('click', deleteRole);
  });
}

/***/ }),

/***/ "./assets/src/css/main.css":
/*!*********************************!*\
  !*** ./assets/src/css/main.css ***!
  \*********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!*******************************!*\
  !*** ./assets/src/js/main.js ***!
  \*******************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _css_main_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../css/main.css */ "./assets/src/css/main.css");
/* harmony import */ var _modules_roles__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./modules/roles */ "./assets/src/js/modules/roles.js");
/* harmony import */ var _modules_capabilities__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./modules/capabilities */ "./assets/src/js/modules/capabilities.js");



window.addEventListener('load', () => {
  (0,_modules_roles__WEBPACK_IMPORTED_MODULE_1__.rolesInit)();
  (0,_modules_capabilities__WEBPACK_IMPORTED_MODULE_2__.capabilitiesInit)();
});
})();

/******/ })()
;
//# sourceMappingURL=main.js.map