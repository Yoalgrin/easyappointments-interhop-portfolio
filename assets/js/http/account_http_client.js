/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.5.0
 * ---------------------------------------------------------------------------- */

/**
 * Account HTTP client.
 *
 * This module implements the account related HTTP requests.
 */
console.log('[BOOT] http/account_http_client.js LOADED');


App.Http.Account = (function () {
    /**
     * Save account.
     *
     * @param {Object} account
     *
     * @return {Object}
     */
    function save(account) {
        const url = App.Utils.Url.siteUrl('account/save');

        const data = {
            csrf_token: vars('csrf_token'),
            id: account.id,
            first_name: account.first_name,
            last_name: account.last_name,
            email: account.email,
            mobile_number: account.mobile_number,
            phone_number: account.phone_number,
            address: account.address,
            city: account.city,
            state: account.state,
            zip_code: account.zip_code,
            notes: account.notes,
            language: account.language,
            timezone: account.timezone,
            interhop_max_patients: account.interhop_max_patients,
            settings: account.settings,
        };


        return $.post(url, data);
    }

    /**
     * Validate username.
     *
     * @param {Number} userId
     * @param {String} username
     *
     * @return {Object}
     */
    function validateUsername(userId, username) {
        const url = App.Utils.Url.siteUrl('account/validate_username');

        const data = {
            csrf_token: vars('csrf_token'),
            user_id: userId,
            username,
        };

        return $.post(url, data);
    }

    return {
        save,
        validateUsername,
    };
})();
