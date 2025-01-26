/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

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


window.addEventListener('load', () => {
  (0,_modules_roles__WEBPACK_IMPORTED_MODULE_1__.rolesInit)({
    deleteSelector: '.delete_role_button',
    addRoleValidator: {
      name: {
        selector: '#role_name',
        reg: /^[a-zA-Z0-9_]+$/
      },
      slug: {
        selector: '#role_slug',
        reg: /^[a-zA-Z0-9_]+$/
      }
    }
  });
});
})();

/******/ })()
;
//# sourceMappingURL=main.js.map