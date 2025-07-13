/**
 * Toolbar Actions for Capabilities
 */


/**
 * Toggle all capabilities checkboxes
 */
export function initToggleAllCapabilities() {
    const checkAll = document.querySelector('.arm_roles-manager-form-toolbar #check-all');
    const uncheckAll = document.querySelector('.arm_roles-manager-form-toolbar #check-none');

    if (!checkAll || !uncheckAll) {
        return;
    }

    checkAll.addEventListener('click', () => {
        getCheckboxes().forEach(checkbox => {
            checkbox.checked = true;
        });
    });

    uncheckAll.addEventListener('click', () => {
        getCheckboxes().forEach(checkbox => {
            checkbox.checked = false;
        });
    });
}

/**
 * Get all checkboxes in the toolbar
 * @returns {NodeListOf<HTMLInputElement>}
 */
function getCheckboxes() {
    return document.querySelectorAll('#capability-list .capability-item input[type="checkbox"]');
}