
/**
 * @param {PointerEvent} e 
 */
export function deleteRole(e) {

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
export function rolesInit(config) {

    const defaultConfig = {
        deleteSelector: '.delete_role_button',
    };

    config = {
        ...defaultConfig,
        ...config,
    };

    const deleteLinks = document.querySelectorAll(config.deleteSelector);
    deleteLinks.forEach(link => {
        link.addEventListener('click', deleteRole);
    });

}