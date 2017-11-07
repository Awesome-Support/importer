'use strict';

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

// Make sure the localized script variable is an object.
window.awesomeSupportImporterVars = window.awesomeSupportImporterVars || {};
window.awesomeSupportImporter = window.awesomeSupportImporter || {
    fields: {},
    dates: {},
    helpScout: {}
};

(function ($, window, document, importer, importerVars) {
    'use strict';

    importer.isLoading = false;

    var $importerContainer, $helpDesk, $save, $importTickets, $loadingMessage, $doneMessage;

    /**
     * Initializes the settings page.
     *
     * @private
     */
    var init = function init() {
        initGlobalVars();

        $importerContainer = $('#awesome-support-importer');

        // Fields
        $helpDesk = $('select[name="awesome-support-importer-help-desk"]');
        $save = $('#awesome-support-import-tickets-save');
        $importTickets = $('#awesome-support-import-tickets');

        // Messages
        $loadingMessage = $('#awesome-support-importing-tickets-message');
        $doneMessage = $('#awesome-support-importer-done-message');

        // Bindings
        $helpDesk.on('change', manageFields);
        $importTickets.on('click', runImporter);
    };

    var initGlobalVars = function initGlobalVars() {
        // fail-safe should the localized script variable not be passed to us.
        if ((typeof importerVars === 'undefined' ? 'undefined' : _typeof(importerVars)) !== 'object' || !importerVars.hasOwnProperty('ajaxErrorMessage')) {
            importerVars = {
                ajaxErrorMessage: 'An error occurred during the import process. Here are the error details:',
                hideFieldWhenNotActive: true
            };
        }

        // Type cast the value to boolean.
        importerVars.hideFieldWhenNotActive = !!importerVars.hideFieldWhenNotActive;
    };

    /**
     * Change the input field "readonly" property for
     * the selected Help Desk.
     *
     * @private
     */
    var manageFields = function manageFields() {
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
    var runImporter = function runImporter(event) {
        event.preventDefault();

        // Prepare the form.
        importer.prepareFormForAjaxGet();

        // Prepare the data to be sent to the server.
        var data = importer.getOptionValues();
        data.action = 'importTicketsByApi';
        data.security = $('#awesome-support-importer-save-nonce').val();

        // Then do the actual request.
        $.post(ajaxurl, data, function (responseMessage) {
            importer.loadSuccessMessage(responseMessage);
        }).fail(function (jqXHR) {
            importer.loadErrorMessage(jqXHR);
        }).always(function () {
            // Always reset the form's interface when the Ajax request is done.
            importer.resetFormAjaxDone();
        });
    };

    importer.prepareFormForAjaxGet = function () {
        $doneMessage.hide();
        $loadingMessage.show();
        $save.prop('disabled', true);
        $importTickets.prop('disabled', true);
        importer.fields.toReadonly(false);
    };

    importer.resetFormAjaxDone = function () {
        var selectedApi = $helpDesk.val();

        $loadingMessage.hide();
        importer.fields.show(selectedApi, false);
        importer.helpScout.render(selectedApi);
        $save.prop('disabled', false);
        $importTickets.prop('disabled', false);
    };

    importer.getOptionValues = function () {
        var $values = {};

        $values[$helpDesk.attr('name')] = $helpDesk.val();

        $values = importer.fields.getValues($values);
        $values = importer.helpScout.getValues($values);
        $values = importer.dates.getDates($values);

        return $values;
    };

    importer.loadSuccessMessage = function (responseMessage) {
        $doneMessage.addClass('is-success').removeClass('is-error').empty().html(responseMessage).show();
    };

    importer.loadErrorMessage = function (jqXHR) {
        $doneMessage.removeClass('is-success').addClass('is-error').empty().html(importerVars.ajaxErrorMessage + importer.getErrorMessage(jqXHR)).show();
    };

    /**
     * It seems weird, right? Let's protect against the edge case
     * where we don't get data back in the Ajax fail response.
     *
     * @param jqXHR
     * @returns {string}
     * @private
     */
    importer.getErrorMessage = function (jqXHR) {
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
    };

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
    var _isReadyForImport = function _isReadyForImport(selectedApi) {
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

    $(function () {
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
'use strict';

(function ($, window, document, importer) {
    'use strict';

    var fields = {},
        $apiInputs,
        $invalidSubdomainMessage;

    /**
     * Initializes the settings page.
     *
     * @private
     */
    fields.init = function ($importerContainer) {
        $apiInputs = $importerContainer.find('input').not('input[type="hidden"], [data-fieldtype="date"]');
        $invalidSubdomainMessage = $importerContainer.find('.awesome-support-importer-invalid-subdomain');

        $('#awesome-support-importer-api-subdomain').on('change', function () {
            _validateSubdomain($(this));
        });
    };

    fields.getValues = function ($values) {
        $values = $values || {};

        $.each($apiInputs, function () {
            var $input = $(this);
            $values[$input.attr('name')] = $input.val();
        });

        return $values;
    };

    /**************************
     * Checkers
     **************************/

    /**
     * Checks if this input is for the selected Help Desk API.
     *
     * @param {object} $input
     * @param {string} selectedAPI
     * @returns {boolean}
     * @private
     */
    var _isForThisApi = function _isForThisApi($input, selectedAPI) {
        var forApi = $input.data('forapi') || 'all';

        // Handle multiple values.
        if (_hasSubstring(forApi, ',')) {
            var forMultipleApis = $.map(forApi.split(','), $.trim);
            return _isInArray(selectedAPI, forMultipleApis);
        }

        // Handle single value.
        return forApi === 'all' || forApi === selectedAPI;
    };

    /**
     * Check if a character or substring (needle) exists within the
     * given string (haystack).
     *
     * @param {string} haystack The string to search
     * @param {string} needle The character or substring to search for
     * @returns {boolean}
     * @private
     */
    var _hasSubstring = function _hasSubstring(haystack, needle) {
        return haystack.indexOf(needle) !== -1;
    };

    /**
     * Checks if the value (needle) exists within the given
     * array (haystack).
     *
     * @param {mixed} needle The value to search for
     * @param {array} haystack The array to search
     * @returns {boolean}
     * @private
     */
    var _isInArray = function _isInArray(needle, haystack) {
        return $.inArray(needle, haystack) !== -1;
    };

    /**
     * Checks if the required fields are filled out for the
     * import process to begin.
     *
     * @param {boolean} isReady
     * @returns {boolean}
     * @private
     */
    fields.readyForImport = function (isReady) {
        $.each($apiInputs, function () {
            var $input = $(this);

            // If there's a value, skip over this one.
            if ('' !== $.trim($input.val())) {

                // Whoops, invalid subdomain.
                if (true !== _validateSubdomain($input)) {
                    isReady = false;
                    return false;
                }

                return true;
            }

            // Whoops, no value. Set state to false.
            // Break out of the loop.
            if (!$input.attr('readonly')) {
                isReady = false;
                return false;
            }
        });

        return isReady;
    };

    /**************************
     * Workers
     **************************/

    /**
     * Reset all fields to read only.
     *
     * @param {boolean} clearValue
     * @private
     */
    fields.toReadonly = function (clearValue) {
        $.each($apiInputs, function () {
            resetApiInput($(this), clearValue, false);
        });
    };

    /**
     * Reset the Api Input field.
     *
     * @param {object} $input
     * @param {boolean} clearValue
     * @param {boolean} hideOption When true, hide the field.
     * @private
     */
    var resetApiInput = function resetApiInput($input, clearValue, hideOption) {
        $input.prop('readonly', true);
        if (clearValue === true) {
            $input.val('');
        }

        if (true === hideOption) {
            $input.parents('.option').hide();
        }
    };

    /**
     * Show the input fields for this Help Desk.
     *
     * @param {string} selectedAPI Selected help desk.
     * @private
     */
    fields.show = function (selectedAPI, clearValue) {
        $.each($apiInputs, function () {
            var $input = $(this);

            // not this one. hide it and then skip.
            if (!_isForThisApi($input, selectedAPI)) {
                resetApiInput($input, true, importer.vars.hideFieldWhenNotActive);
                return true;
            }

            if (true === clearValue) {
                $input.val('');
            }

            $input.removeAttr('readonly').parents('.option').show();
        });
    };

    /**
     * Checks if the subdomain has spaces in it. If yes, then show an error message.
     * We're doing a check here before ever sending the Ajax request back to the server.
     *
     * @param {object} $input
     * @returns {boolean}
     * @private
     */
    var _validateSubdomain = function _validateSubdomain($input) {
        if ('awesome-support-importer-api-subdomain' !== $input.attr('name')) {
            return true;
        }

        if (/\s/.test($input.val())) {
            $invalidSubdomainMessage.slideDown();
            return false;
        }

        if ($invalidSubdomainMessage.is(":visible")) {
            $invalidSubdomainMessage.slideUp();
        }
        return true;
    };

    importer.fields = fields;
})(jQuery, window, document, window.awesomeSupportImporter);
"use strict";

(function ($, window, document, importer) {
    'use strict';

    var dates = {},
        $dateFields;

    /**
     * Handles the jQuery Date Picker allowing the user to select
     * a date range for the ticket import.
     *
     * @private
     */
    dates.init = function () {
        $.datepicker.setDefaults({
            // showOn: "both",
            buttonImageOnly: true,
            buttonImage: "calendar.gif",
            // buttonText: "Calendar"
            altFormat: "yyyy-mm-dd"
        });

        $dateFields = $('*[data-fieldtype="date"]');

        //
        $dateFields.datepicker({
            constrainInput: true
        });

        // Force user to use the datepicker and NOT type in the text box.
        $dateFields.on('keypress', function (event) {
            event.preventDefault();
        });
    };

    dates.isDateField = function ($field) {
        return $field.data('fieldtype') === 'date';
    };

    dates.getDates = function ($values) {
        $values = $values || {};

        $.each($dateFields, function () {
            var $input = $(this);
            $values[$input.attr('name')] = $input.val();
        });

        return $values;
    };

    importer.dates = dates;
})(jQuery, window, document, window.awesomeSupportImporter);
'use strict';

(function ($, window, document, importer) {
    'use strict';

    var helpScout = {},
        $mailboxSelect,
        $helpScoutMessage,
        $mailboxesLoadedMessage,
        $errorMessage;

    /**
     * Initializes the settings page.
     *
     * @private
     */
    helpScout.init = function () {
        $mailboxSelect = $('select[name="awesome-support-importer-api-mailbox"]');
        $helpScoutMessage = $('#awesome-support-importing-getting-mailboxes-message');
        $mailboxesLoadedMessage = $('.awesome-support-importer-mailboxes-loaded');
        $errorMessage = $('.awesome-support-importer-message');
        $('#awesome-support-get-helpscout-mailboxes').on('click', getMailboxes);
    };

    helpScout.render = function (selectedApi) {
        if (isHelpScout(selectedApi)) {
            show();
        } else {
            helpScout.hideMailboxSelect(true);
        }
    };

    helpScout.getValues = function ($values) {
        $values = $values || {};

        var selectedMailbox = $mailboxSelect.val();
        $values[$mailboxSelect.attr('name')] = null === selectedMailbox ? '' : selectedMailbox;

        return $values;
    };

    helpScout.hideMessage = function () {
        $helpScoutMessage.hide();
        $mailboxesLoadedMessage.hide();
        $errorMessage.hide();
    };

    helpScout.hideMailboxSelect = function (resetOption) {
        $mailboxSelect.parent('.option').hide();
        if (true === resetOption) {
            $mailboxSelect.val('');
        }
    };

    helpScout.readyForImport = function (isReady, selectedApi) {
        if (true !== isReady || !isHelpScout(selectedApi)) {
            return isReady;
        }

        return '' !== $mailboxSelect.val();
    };

    var isHelpScout = function isHelpScout(selectedApi) {
        return 'help-scout' === selectedApi;
    };

    var show = function show() {
        $mailboxSelect.parent('.option').show();
    };

    var getMailboxes = function getMailboxes(event) {
        event.preventDefault();
        if (!$('#awesome-support-importer-api-token').val()) {
            return;
        }

        // Prepare the form.
        importer.prepareFormForAjaxGet();
        $helpScoutMessage.show();
        $mailboxesLoadedMessage.hide();
        $errorMessage.hide();

        // Prepare the data to be sent to the server.
        var data = importer.getOptionValues();
        data.action = 'getHelpScoutMailboxes';
        data.security = $('#awesome-support-importer-save-nonce').val();

        // Then do the actual request.
        $.post(ajaxurl, data, function (html) {
            $mailboxesLoadedMessage.slideDown();
            console.log('got them!');
            $mailboxSelect.empty().append(html).val('');
        }).fail(function (jqXHR) {
            importer.loadErrorMessage(jqXHR);
        }).always(function () {
            // Always reset the form's interface when the Ajax request is done.
            $helpScoutMessage.hide();
            importer.resetFormAjaxDone();
        });
    };

    importer.helpScout = helpScout;
})(jQuery, window, document, window.awesomeSupportImporter);