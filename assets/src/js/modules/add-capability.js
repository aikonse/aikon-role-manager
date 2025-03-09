
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
export function addNewCapability(inputSelector, addBtnSelector, listSelector) {

    const input = document.querySelector(inputSelector)
    const addBtn = document.querySelector(addBtnSelector);
    const capabilitiesList = document.querySelector(listSelector);

    addBtn.addEventListener('click', function() {
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
    capabilityItem.innerHTML =
        `<input type="hidden" name="${name}" value="0"> 
        <label>
            <input type="checkbox" name="${name}" value="1">
            ${capability}
        </label>`;

    return capabilityItem;
}