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

(function () {

    'use strict';

    /**
     * ExternalToolsHelper Class
     *
     * This class contains the core method implementations that belong to the external tools tab
     * of the backend services page.
     *
     * @class ExternalToolsHelper
     */
    function ExternalToolsHelper() {
        this.filterResults = {};
        this.filterLimit = 20;
    }

    /**
     * Binds the default event handlers of the external tools tab.
     */
    ExternalToolsHelper.prototype.bindEventHandlers = function () {
        let instance = this;


        /**
         * Event: Filter External Tools Cancel Button "Click"
         */
        $('#externals-tools').on('click', '#filter-externals-tools .clear', function () {
            $('#filter-externals-tools .key').val('');
            instance.filter('');
            instance.resetForm();
        });

        /**
         * Event: Filter External Tools Form "Submit"
         *
         * @param {jQuery.Event} event
         */
        $('#externals-tools').on('submit', '#filter-externals-tools form', function (event) {
            event.preventDefault();
            let key = $('#filter-externals-tools .key').val();
            $('.selected').removeClass('selected');
            instance.resetForm();
            instance.filter(key);
        });

        /**
         * Event: Filter External Tools Row "Click"
         *
         * Displays the selected row data on the right side of the page.
         */
        $('#externals-tools').on('click', '.external-tool-row', function () {
            if ($('#filter-externals-tools .filter').prop('disabled')) {
                $('#filter-externals-tools .results').css('color', '#AAA');
                return; // exit because we are on edit mode
            }

            let externalToolId = $(this).attr('data-id');

            let externalTool = instance.filterResults.find(function (filterResult) {
                return Number(filterResult.id) === Number(externalToolId);
            });

            instance.display(externalTool);
            $('#filter-externals-tools .selected').removeClass('selected');
            $(this).addClass('selected');
            $('#edit-external-tool, #delete-external-tool').prop('disabled', false);
        });

        /**
         * Event: Add External Tool Button "Click"
         */
        $('#externals-tools').on('click', '#add-external-tool', function () {
            instance.resetForm();

            $('#externals-tools .add-edit-delete-group').hide();
            $('#externals-tools .save-cancel-group').show();
            $('#externals-tools .record-details').find('input, select, textarea').prop('disabled', false);
            $('#filter-externals-tools button').prop('disabled', true);
            $('#filter-externals-tools .results').css('color', '#AAA');
            $('#cancel-external-tool').prop('disabled', false);

        });

        /**
         * Event: Edit External Tool Button "Click"
         */
        $('#externals-tools').on('click', '#edit-external-tool', function () {
            $('#externals-tools .add-edit-delete-group').hide();
            $('#externals-tools .save-cancel-group').show();
            $('#externals-tools .record-details').find('input, select, textarea').prop('disabled', false);
            $('#filter-externals-tools button').prop('disabled', true);
            $('#filter-externals-tools .results').css('color', '#AAA');
            $('#cancel-external-tool').prop('disabled', false);
        });

        /**
         * Event: Delete External Tool Button "Click"
         */
        $('#externals-tools').on('click', '#delete-external-tool', function () {
            let externalToolId = $('#external-tool-id').val();

            let buttons = [
                {
                    text: EALang.cancel,
                    click: function () {
                        $('#message-box').dialog('close');
                    }
                },
                {
                    text: EALang.delete,
                    click: function () {
                        instance.delete(externalToolId);
                        $('#message-box').dialog('close');
                    }
                },
            ];

            GeneralFunctions.displayMessageBox(EALang.delete_external_tool,
                EALang.delete_record_prompt, buttons);
        });

        /**
         * Event: External Tools Save Button "Click"
         */
        $('#externals-tools').on('click', '#save-external-tool', function () {
            let externalTool = {
                name: $('#external-tool-name').val(),
                description: $('#external-tool-description').val(),
                link: $('#external-tool-link').val(),
                id_type: $('#external-tool-type').val(),
            };

            if ($('#external-tool-id').val() !== '') {
                externalTool.id = $('#external-tool-id').val();
            }

            if (!instance.validate()) {
                return;
            }

            instance.save(externalTool);
        });

        /**
         * Event: Cancel External Tool Button "Click"
         */
        $('#externals-tools').on('click', '#cancel-external-tool', function () {
            let id = $('#external-tool-id').val();
            instance.resetForm();
            if (id !== '') {
                instance.select(id, true);
            }
        });




        /**
         * Event: Add tool type "Click".
         *
         * On click, generate a prompt to save a new tool type in the database.
         */
        $('#add-tool-type').on('click', function () {
            GeneralFunctions.displayMessageBox(EALang.add_type, EALang.name + ' :', [
                {
                    text: EALang.save,
                    click: function () {
                        instance.saveToolType()
                        $('#message-box').dialog('close');
                    }
                },
                {
                    text: EALang.cancel,
                    click: function () {
                        $('#message-box').dialog('close');
                    }
                }
            ]);
            $('#message-box').append($('<input>', {
                type: 'text',
                id: 'type-input',
                class: 'form-control required'
            }));
        });

        /**
         * Event: Delete tool type "Click".
         *
         * On click, generate a prompt to delete a tool type from the database.
         */
         $('#external-tool-type').on('change', function () {
            $('#delete-tool-type').prop('disabled', false)

        });
        $('#delete-tool-type').on('click', function () {
            GeneralFunctions.displayMessageBox(EALang.delete_type, $('#external-tool-type').find(':selected').text() , [
                {
                    text: EALang.delete,
                    click: function () {
                        instance.deleteToolType($('#external-tool-type').val())
                        $('#message-box').dialog('close');
                    }
                },
                {
                    text: EALang.cancel,
                    click: function () {
                        $('#message-box').dialog('close');
                    }
                }
            ]);
        });

    };


    /**
     * Remove the previously registered event handlers.
     */
    ExternalToolsHelper.prototype.unbindEventHandlers = function () {
        $('#externals-tools')
            .off('click', '#filter-externals-tools .clear')
            .off('submit', '#filter-externals-tools form')
            .off('click', '.external-tool-row')
            .off('click', '#add-external-tool')
            .off('click', '#edit-external-tool')
            .off('click', '#delete-external-tool')
            .off('click', '#save-external-tool')
            .off('click', '#cancel-external-tool');
    };

    /**
     * Filter service External Tools records.
     *
     * @param {String} key This key string is used to filter the external tool records.
     * @param {Number} selectId Optional, if set then after the filter operation the record with the given
     * ID will be selected (but not displayed).
     * @param {Boolean} display Optional (false), if true then the selected record will be displayed on the form.
     */
    ExternalToolsHelper.prototype.filter = function (key, selectId, display) {
        let url = GlobalVariables.baseUrl + '/index.php/backend_api/ajax_filter_service_externals_tools';

        let data = {
            csrfToken: GlobalVariables.csrfToken,
            key: key,
            limit: this.filterLimit
        };

        $.post(url, data)
            .done(function (response) {
                this.filterResults = response;

                $('#filter-externals-tools .results').empty();

                response.forEach(function (externalTool) {

                    $('#filter-externals-tools .results')
                        .append(this.getFilterHtml(externalTool))
                        .append($('<hr/>'));
                }.bind(this));

                if (response.length === 0) {
                    $('#filter-externals-tools .results').append(
                        $('<em/>', {
                            'text': EALang.no_records_found
                        })
                    );
                } else if (response.length === this.filterLimit) {
                    $('<button/>', {
                        'type': 'button',
                        'class': 'btn btn-block btn-outline-secondary load-more text-center',
                        'text': EALang.load_more,
                        'click': function () {
                            this.filterLimit += 20;
                            this.filter(key, selectId, display);
                        }.bind(this)
                    })
                        .appendTo('#filter-externals-tools .results');
                }

                if (selectId) {
                    this.select(selectId, display);
                }
            }.bind(this));
    };

    /**
     * Save an external tool record to the database (via AJAX post).
     *
     * @param {Object} externalTool Contains the external tool data.
     */
    ExternalToolsHelper.prototype.save = function (externalTool) {
        let url = GlobalVariables.baseUrl + '/index.php/backend_api/ajax_save_service_external_tool';

        let data = {
            csrfToken: GlobalVariables.csrfToken,
            external_tool: JSON.stringify(externalTool)
        };

        $.post(url, data)
            .done(function (response) {
                Backend.displayNotification(EALang.service_external_tool_saved);
                this.resetForm();
                $('#filter-externals-tools .key').val('');
                this.filter('', response.id, true);
                BackendServices.updateAvailableExternalTools();
            }.bind(this));
    };

    /**
     * Delete external tool record (via AJAX post).
     *
     * @param {Number} id Record ID to be deleted.
     */
    ExternalToolsHelper.prototype.delete = function (id) {
        let url = GlobalVariables.baseUrl + '/index.php/backend_api/ajax_delete_service_external_tool';

        let data = {
            csrfToken: GlobalVariables.csrfToken,
            externalTool_id: id
        };

        $.post(url, data)
            .done(function () {
                Backend.displayNotification(EALang.service_external_tool_deleted);

                this.resetForm();
                this.filter($('#filter-externals-tools .key').val());
                BackendServices.updateAvailableExternalTools();
            }.bind(this));
    };

    /**
     * Display an external tool record on the form.
     *
     * @param {Object} externalTool Contains the external tool data.
     */
    ExternalToolsHelper.prototype.display = function (externalTool) {

        $('#external-tool-id').val(externalTool.id);
        $('#external-tool-name').val(externalTool.name);
        $('#external-tool-description').val(externalTool.description);
        $('#external-tool-link').val(externalTool.link);
        $('#external-tool-type').val(externalTool.id_type)
    };

    /**
     * Validate external tool data before save (insert or update).
     *
     * @return {Boolean} Returns the validation result.
     */
    ExternalToolsHelper.prototype.validate = function () {
        $('#externals-tools .has-error').removeClass('has-error');
        $('#externals-tools .form-message')
            .removeClass('alert-danger')
            .hide();

        try {
            let missingRequired = false;

            $('#externals-tools .required').each(function (index, requiredField) {
                if (!$(requiredField).val()) {
                    $(requiredField).closest('.form-group').addClass('has-error');
                    missingRequired = true;
                }
            });

            if (missingRequired) {
                throw new Error(EALang.fields_are_required);
            }

            return true;
        } catch (error) {
            $('#externals-tools .form-message')
                .addClass('alert-danger')
                .text(error.message)
                .show();
            return false;
        }
    };



    /**
     * Save an external tool type record to the database (via AJAX post).
     */
    ExternalToolsHelper.prototype.saveToolType = function () {
        let Type = {
            name: $('#type-input').val()
        }

        let url = GlobalVariables.baseUrl + '/index.php/backend_api/ajax_save_tool_type';

        let data = {
            csrfToken: GlobalVariables.csrfToken,
            tool_type: JSON.stringify(Type)
        };

        $.post(url, data)
            .done(function (response) {
                Backend.displayNotification(EALang.new_type_saved);
                BackendServices.updateAvailableToolTypes();
            }.bind(this));
    };


    /**
     * Delete an external tool type record in the database (via AJAX post).
     *
     * @param {Number} id Record ID to be deleted.
     */
    ExternalToolsHelper.prototype.deleteToolType = function (id) {

        let url = GlobalVariables.baseUrl + '/index.php/backend_api/ajax_delete_tool_type';

        let data = {
            csrfToken: GlobalVariables.csrfToken,
            tool_type_id: id
        };

        $.post(url, data)
            .done(function () {
                Backend.displayNotification(EALang.type_deleted);
                BackendServices.updateAvailableToolTypes();
            }.bind(this));
    }


    /**
     * Get all tool types records from database (via AJAX post).
     */
    ExternalToolsHelper.prototype.getTypes = function () {

        let url = GlobalVariables.baseUrl + '/index.php/backend_api/ajax_get_tool_types';

        $.post(url)
            .done(function (response) {
                $('#external-tool-type').empty();

                response.forEach(function (toolType) {
                    $('#external-tool-type').append(toolType);
                }.bind(this));
            }.bind(this));
    }


    /**
     * Bring the external tool form back to its initial state.
     */
    ExternalToolsHelper.prototype.resetForm = function () {

        $('#filter-externals-tools .selected').removeClass('selected');
        $('#filter-externals-tools button').prop('disabled', false);
        $('#filter-externals-tools .results').css('color', '');

        $('#externals-tools .add-edit-delete-group').show();
        $('#externals-tools .save-cancel-group').hide();
        $('#externals-tools .record-details')
            .find('input[type!="color"], select, textarea')
            .val('');
        $('#externals-tools .record-details')
            .find('input, select, textarea')
            .prop('disabled', true);
        $('#edit-external-tool, #delete-external-tool').prop('disabled', true);
        $('#delete-tool-type').prop('disabled', true);

        $('#externals-tools .record-details .has-error').removeClass('has-error');
        $('#externals-tools .record-details .form-message').hide();
    };

    /**
     * Get the filter results row HTML code.
     *
     * @param {Object} externalTool Contains the external tool data.
     *
     * @return {String} Returns the record HTML code.
     */
    ExternalToolsHelper.prototype.getFilterHtml = function (externalTool) {
        return $('<div/>', {
            'class': 'external-tool-row entry',
            'data-id': externalTool.id,
            'html': [
                $('<strong/>', {
                    'text': externalTool.name
                }),
                $('<br/>'),
            ]
        });
    };

    /**
     * Select a specific record from the current filter results.
     *
     * If the external tool ID does not exist in the list then no record will be selected.
     *
     * @param {Number} id The record ID to be selected from the filter results.
     * @param {Boolean} display Optional (false), if true then the method will display the record
     * on the form.
     */
    ExternalToolsHelper.prototype.select = function (id, display) {
        display = display || false;

        $('#filter-externals-tools .selected').removeClass('selected');

        $('#filter-externals-tools .external-tool-row[data-id="' + id + '"]').addClass('selected');

        if (display) {
            let externalTool = this.filterResults.find(function (externalTool) {
                return Number(externalTool.id) === Number(id);
            }.bind(this));

            this.display(externalTool);

            $('#edit-external-tool, #delete-external-tool').prop('disabled', false);
        }
    };

    window.ExternalToolsHelper = ExternalToolsHelper;
})();
