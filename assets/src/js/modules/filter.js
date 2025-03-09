
/**
 * Filter capabilities
 * @param {NodeList} capabilityListItems List items to filter
 * @param {String} search Search string
 * @returns {void}
 */
function filterCapabilities(capabilityListItems, search) {
    const capabilities = capabilityListItems;

    capabilities.forEach(function(capability) {
        capability.style.display = 'block';
    });

    if (!search) {
        return;
    }

    capabilities.forEach(function(capability) {
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

    if(!capabilitySearch || !capabilityListItems) {
        return;
    }

    capabilitySearch.addEventListener('input', (_e) => {
        const search = capabilitySearch.value.toLowerCase();
        if(search.length < 2) {
            filterCapabilities(capabilityListItems, null);
            wrapper.setAttribute('aria-expand-all', 'false');
            return;
        }
        filterCapabilities(capabilityListItems, search);
        wrapper.setAttribute('aria-expand-all', 'true');
    });
}

export { quickFilter };