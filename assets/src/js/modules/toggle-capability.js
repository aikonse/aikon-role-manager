
/**
 * 
 * @param {string} capabilityListSelector 
 * @param {string} accordionToggleSelector 
 * @returns void
 */
export function capabilityToggle(capabilityListSelector, accordionToggleSelector) {
    const capabilityList = document.querySelector(capabilityListSelector);

    if (!capabilityList) {
        return;
    }

    
    capabilityList.addEventListener('click', function(e) {
        const removeText = capabilityList.dataset.removeText || 'Remove';
        const restoreText = capabilityList.dataset.restoreText || 'Restore';

        if (e.target.tagName !== 'BUTTON') {
            return;
        }
        const deleteName = 'delete__';
        const parent = e.target.parentElement;
        const checkbox = parent.querySelector('input[type="checkbox"]');
        const hidden = parent.querySelector('input[type="hidden"]');
        let name = hidden.name;

        const isDelete = name.includes(deleteName);

        if (isDelete) {
            name = name.replace(deleteName, '');
            checkbox.name = name;
            hidden.name = name;
            checkbox.disabled = false
            e.target.innerHTML = removeText;
        } else {
            name = deleteName + name;
            checkbox.name = name;
            hidden.name = name;
            checkbox.disabled = true;
            e.target.innerHTML = restoreText;
        }
        console.dir({ name, checkbox, hidden, isDelete });
    });
}
