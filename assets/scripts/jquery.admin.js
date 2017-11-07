// Make sure the localized script variable is an object.
window.awesomeSupportImporterVars = window.awesomeSupportImporterVars || {};
window.awesomeSupportImporter = window.awesomeSupportImporter || {
    fields: {},
    dates: {},
    helpScout: {}
};

(function($, window, document, importer, importerVars) {
    'use strict';

    importer.isLoading = false;

    var $importerContainer,
        $helpDesk,
        $save,
        $importTickets,
        $loadingMessage,
        $doneMessage;

    /**
     * Initializes the settings page.
     *
     * @private
     */
    var init = function() {
        initGlobalVars();

        $importerContainer = $('#awesome-support-importer');

        // Fields
        $helpDesk      = $('select[name="awesome-support-importer-help-desk"]');
        $save          = $('#awesome-support-import-tickets-save');
        $importTickets = $('#awesome-support-import-tickets');

        // Messages
        $loadingMessage   = $('#awesome-support-importing-tickets-message');
        $doneMessage      = $('#awesome-support-importer-done-message');

        // Bindings
        $helpDesk.on('change', manageFields);
        $importTickets.on('click', runImporter);
    };

    var initGlobalVars = function() {
        // fail-safe should the localized script variable not be passed to us.
        if (typeof importerVars !== 'object' || !importerVars.hasOwnProperty('ajaxErrorMessage')) {
            importerVars = {
                ajaxErrorMessage: 'An error occurred during the import process. Here are the error details:',
                hideFieldWhenNotActive: true
            };
        }

        // Type cast the value to boolean.
        importerVars.hideFieldWhenNotActive = !!importerVars.hideFieldWhenNotActive;
    }

    /**
     * Change the input field "readonly" property for
     * the selected Help Desk.
     *
     * @private
     */
    var manageFields = function() {
        var selectedAPI = $helpDesk.val();

        $importerContainer.attr('data-selectedapi', selectedAPI);
        $importerContainer.data('selectedapi', selectedAPI);

        if ('default' === selectedAPI) {
            importer.helpScout.hideMailboxSelect(true);
            return importer.fields.toReadonly(true);
        }

        if (false === importer.isLoading) {
            $loadingMessage.removeAttr('disabled');
            $loadingMessage.hide();
            $doneMessage.hide();
        }

        importer.fields.show(selectedAPI, !importer.isLoading);
        importer.helpScout.render(selectedAPI);

        // show or hide Import Tickets button.
        $importTickets.prop('disabled', !_isReadyForImport(selectedAPI));
    };

    /**
     * Run the Importer.
     *
     * @param {object} event Click event.
     */
    var runImporter = function(event) {
        event.preventDefault();

        // Prepare the form.
        importer.prepareFormForAjaxGet();

        // Prepare the data to be sent to the server.
        var data      = importer.getOptionValues();
        data.action   = 'importTicketsByApi';
        data.security = $('#awesome-support-importer-save-nonce').val();

        // Then do the actual request.
        $.post(ajaxurl, data, function(responseMessage) {
            importer.loadSuccessMessage(responseMessage);
        }).fail(function(jqXHR) {
            importer.loadErrorMessage(jqXHR);
        }).always(function() {
            // Always reset the form's interface when the Ajax request is done.
            importer.resetFormAjaxDone();
        });
    };

    importer.prepareFormForAjaxGet = function() {
        $doneMessage.hide();
        $loadingMessage.show();
        $save.prop('disabled', true);
        $importTickets.prop('disabled', true);
        importer.fields.toReadonly(false);
    }

    importer.resetFormAjaxDone = function() {
        var selectedApi = $helpDesk.val();

        $loadingMessage.hide();
        importer.fields.show(selectedApi, false);
        importer.helpScout.render(selectedApi);
        $save.prop('disabled', false);
        $importTickets.prop('disabled', false);
    }

    importer.getOptionValues = function() {
        var $values = {};

        $values[$helpDesk.attr('name')] = $helpDesk.val();

        $values = importer.fields.getValues($values);
        $values = importer.helpScout.getValues($values);
        $values = importer.dates.getDates($values);

        return $values;
    }

    importer.loadSuccessMessage = function(responseMessage) {
        $doneMessage
            .addClass('is-success')
            .removeClass('is-error')
            .empty()
            .html(responseMessage)
            .show();
    }

    importer.loadErrorMessage = function(jqXHR) {
        $doneMessage
            .removeClass('is-success')
            .addClass('is-error')
            .empty()
            .html(importerVars.ajaxErrorMessage + importer.getErrorMessage(jqXHR))
            .show();
    }

    /**
     * It seems weird, right? Let's protect against the edge case
     * where we don't get data back in the Ajax fail response.
     *
     * @param jqXHR
     * @returns {string}
     * @private
     */
    importer.getErrorMessage = function(jqXHR) {
        if (!jqXHR || !jqXHR.hasOwnProperty('responseJSON')) {
            return '';
        }

        if (!jqXHR.responseJSON.hasOwnProperty('data')) {
            return '';
        }

        if (!jqXHR.responseJSON.data.hasOwnProperty('message')) {
            return '';
        }

        return jqXHR.responseJSON.data.message;
    }

    /**************************
     * Checkers
     **************************/

    /**
     * Checks if the required fields are filled out for the
     * import process to begin.
     *
     * @param {string} selectedApi
     * @returns {boolean}
     * @private
     */
    var _isReadyForImport = function(selectedApi) {
        if ($('.notice-error').length > 0) {
            return false;
        }

        var isReady = true;

        isReady = importer.fields.readyForImport(isReady, selectedApi);
        isReady = importer.helpScout.readyForImport(isReady, selectedApi);

        return isReady;
    };

    /**************************
     * Launch
     **************************/

    $(function() {
        importer.isLoading = true;
        importer.vars = awesomeSupportImporterVars;

        init();

        importer.dates.init();
        importer.helpScout.init();
        importer.fields.init($importerContainer);

        manageFields();

        importer.isLoading = false;
    });

})(jQuery, window, document, window.awesomeSupportImporter, awesomeSupportImporterVars);