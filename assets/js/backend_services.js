/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.0.0
 * ---------------------------------------------------------------------------- */

window.BackendServices = window.BackendServices || {};

/**
 * Backend Services
 *
 * This namespace handles the js functionality of the backend services page.
 *
 * @module BackendServices
 */
(function (exports) {

    'use strict';

    /**
     * Contains the basic record methods for the page.
     *
     * @type {ServicesHelper|CategoriesHelper/ExternalToolsHelper}
     */
    var helper;

    var servicesHelper = new ServicesHelper();
    var categoriesHelper = new CategoriesHelper();
    let externalToolsHelper = new ExternalToolsHelper();

    /**
     * Default initialize method of the page.
     *
     * @param {Boolean} [defaultEventHandlers] Optional (true), determines whether to bind the  default event handlers.
     */
    exports.initialize = function (defaultEventHandlers) {

        defaultEventHandlers = defaultEventHandlers || true;

        // Fill available service categories listbox.
        GlobalVariables.categories.forEach(function (category) {
            $('#service-category').append(new Option(category.name, category.id));
        });

        // Create external tools checkboxes list.
        createExternalToolsCheckboxes()


        // // Fill external tool types listbox.
        GlobalVariables.externalsToolsTypes.forEach(function (type) {
            $('#external-tool-type').append(new Option(type.name, type.id))
        })

        // Instantiate helper object (service helper by default).
        helper = servicesHelper;
        helper.resetForm();
        helper.filter('');
        helper.bindEventHandlers();

        if (defaultEventHandlers) {
            bindEventHandlers();
        }
    };

    /**
     * Binds the default event handlers of the backend services page.
     *
     * Do not use this method if you include the "BackendServices" namespace on another page.
     */
    function bindEventHandlers() {
        /**
         * Event: Page Tab Button "Click"
         *
         * Changes the displayed tab.
         */
        $('a[data-toggle="tab"]').on('shown.bs.tab', function () {

            if (helper) {
                helper.unbindEventHandlers();
            }

            if ($(this).attr('href') === '#services') {
                $('#service-externals-tools').empty()
                createExternalToolsCheckboxes()
                helper = servicesHelper;
            } else if ($(this).attr('href') === '#categories') {
                helper = categoriesHelper;
            } else if ($(this).attr('href') === '#externals-tools') {
                helper = externalToolsHelper;
            }

            helper.resetForm();
            helper.filter('');
            helper.bindEventHandlers();
            $('.filter-key').val('');
            Backend.placeFooterToBottom();
        });
    }

    /**
     * Update the service category list box.
     *
     * Use this method every time a change is made to the service categories db table.
     */
    exports.updateAvailableCategories = function () {
        var url = GlobalVariables.baseUrl + '/index.php/backend_api/ajax_filter_service_categories';

        var data = {
            csrfToken: GlobalVariables.csrfToken,
            key: ''
        };

        $.post(url, data)
            .done(function (response) {
                GlobalVariables.categories = response;
                var $select = $('#service-category');

                $select.empty();

                response.forEach(function (category) {
                    $select.append(new Option(category.name, category.id));
                });

                $select.append(new Option('- ' + EALang.no_category + ' -', null)).val('null');
            });
    };

    /**
     * Update the external tools list box.
     *
     * Use this method every time a change is made to the external tools db table.
     */
    exports.updateAvailableExternalTools = function () {

        let url = GlobalVariables.baseUrl + '/index.php/backend_api/ajax_filter_service_externals_tools';

        let data = {
            csrfToken: GlobalVariables.csrfToken,
            key: ''
        };

        $.post(url, data)
            .done(function (response) {
                GlobalVariables.externalsTools = response;
                let $checkBoxes = $('#service-externals-tools');

                $checkBoxes.empty();

                response.forEach(function (externalTool) {
                    $checkBoxes.append(new Option(externalTool.name, externalTool.id));
                });

                if (response.length === 0) {
                    $checkBoxes.append(new Option('- ' + EALang.no_external_tool + ' -', null)).val('null');
                }
            });
    }

    /**
     * Update the external tool types list box.
     *
     * Use this method every time a change is made to the tool types db table.
     */
    exports.updateAvailableToolTypes = function () {
        let url = GlobalVariables.baseUrl + '/index.php/backend_api/ajax_get_tool_types';

        let data = {
            csrfToken: GlobalVariables.csrfToken,
            key: ''
        };

        $.post(url, data)
            .done(function (response) {
                GlobalVariables.externalsToolsTypes = response;
                let $select = $('#external-tool-type');

                $select.empty();

                response.forEach(function (toolType) {
                    $select.append(new Option(toolType.name, toolType.id));
                });

                if (response.length === 0)
                    $select.append(new Text('- ' + EALang.no_tool_type + ' -'));
            });
    }


    /**
     * Fills the external tools checkboxes list in the service tab.
     *
     * Use this method upon service page loading, and every time service tab is clicked.
     */
    function createExternalToolsCheckboxes() {
        let typesWithTools = {};
        typesWithTools.types = [];

        GlobalVariables.externalsToolsTypes.forEach(function (toolType) {

            let toolsOfType = GlobalVariables.externalsTools.filter(tool => tool.id_type === toolType.id)
            if (toolsOfType.length > 0) {
                let typeWithTools = {
                    type: toolType,
                    tools: toolsOfType
                };
                typesWithTools.types.push(typeWithTools)
            }
        })

        typesWithTools.types.forEach(function (typeWithTools) {
            let typeDiv = $('<div/>', {
                'class': 'type-div-column'
            });
            $('<h6/>', {
                'text': typeWithTools.type.name
            }).appendTo(typeDiv);

            typeWithTools.tools.forEach(function (tool) {

                $('<div/>', {
                    'class': 'checkbox form-check',
                    'html': [
                        $('<input/>', {
                            'class': 'form-check-input',
                            'type': 'checkbox',
                            'data-id': tool.id,
                            'prop': {
                                'disabled': true,
                            }
                        }),
                        $('<label/>', {
                            'class': 'form-check-label',
                            'text': tool.name,
                            'for': tool.id
                        })
                    ]
                }).appendTo(typeDiv)
            });

            typeDiv.appendTo($('#service-externals-tools'))
        })

        if (GlobalVariables.externalsTools.length === 0) {
            $('#service-externals-tools').text(EALang.no_external_tool);
        }
    }

})(window.BackendServices);
