/**
 * Toggle accordion
 * @param {string} toggleSelector Accordion toggle selector
 * @returns {void}
 */
export function toggleAccordion(toggleSelector) {

    const accordions = document.querySelectorAll(toggleSelector);

    accordions.forEach(accordion => {
        accordion.addEventListener('click', () => {
            const parent = accordion.parentElement;
            const is_open = parent.getAttribute('aria-expanded') === 'true';
            parent.setAttribute('aria-expanded', !is_open ? 'true' : 'false');
        });
    });
}