/*!
 * Matomo - DbipUpdater Plugin
 *
 * @link https://franz.agency
 * @license GPL v3+
 * @author Franz und Franz
 * @copyright Franz und Franz
 */

$(document).ready(function () {
    // Initialize any specific functionality for the DB-IP Updater settings
    $('.dbip-updater-section').on('click', '.save-settings', function () {
        // Automatically refresh the page after saving to make sure the new settings are applied
        $(this).parents('form').on('submit', function () {
            setTimeout(function () {
                window.location.reload();
            }, 1000);
            return true;
        });
    });
});
