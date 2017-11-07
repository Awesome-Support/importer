(function($, window, document, importer) {
    'use strict';

    var fields = {},
        $apiInputs,
        $invalidSubdomainMessage;

    /**
     * Initializes the settings page.
     *
     * @private
     */
    fields.init = function($importerContainer) {
        $apiInputs = $importerContainer
            .find('input')
            .not('input[type="hidden"], [data-fieldtype="date"]');
        $invalidSubdomainMessage = $importerContainer.find('.awesome-support-importer-invalid-subdomain');

        $('#awesome-support-importer-api-subdomain').on('change', function(){
            _validateSubdomain($(this));
        });
    };

    fields.getValues = function($values) {
        $values = $values || {};

        $.each($apiInputs, function() {
            var $input                   = $(this);
            $values[$input.attr('name')] = $input.val();
        });

        return $values;
    }

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
    var _isForThisApi = function($input, selectedAPI) {
        var forApi = $input.data('forapi') || 'all';

        // Handle multiple values.
        if (_hasSubstring(forApi, ',')) {
            var forMultipleApis = $.map(forApi.split(','), $.trim);
            return _isInArray(selectedAPI, forMultipleApis);
        }

        // Handle single value.
        return forApi === 'all' || forApi === selectedAPI;
    }

    /**
     * Check if a character or substring (needle) exists within the
     * given string (haystack).
     *
     * @param {string} haystack The string to search
     * @param {string} needle The character or substring to search for
     * @returns {boolean}
     * @private
     */
    var _hasSubstring = function(haystack, needle) {
        return haystack.indexOf(needle) !== -1;
    }

    /**
     * Checks if the value (needle) exists within the given
     * array (haystack).
     *
     * @param {mixed} needle The value to search for
     * @param {array} haystack The array to search
     * @returns {boolean}
     * @private
     */
    var _isInArray = function(needle, haystack) {
        return $.inArray(needle, haystack) !== -1;
    }

    /**
     * Checks if the required fields are filled out for the
     * import process to begin.
     *
     * @param {boolean} isReady
     * @returns {boolean}
     * @private
     */
    fields.readyForImport = function(isReady) {
        $.each($apiInputs, function() {
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
    fields.toReadonly = function(clearValue) {
        $.each($apiInputs, function() {
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
    var resetApiInput = function($input, clearValue, hideOption) {
        $input.prop('readonly', true);
        if (clearValue === true) {
            $input.val('');
        }

        if (true === hideOption) {
            $input.parents('.option').hide();
        }
    }

    /**
     * Show the input fields for this Help Desk.
     *
     * @param {string} selectedAPI Selected help desk.
     * @private
     */
    fields.show = function(selectedAPI, clearValue) {
        $.each($apiInputs, function() {
            var $input = $(this);

            // not this one. hide it and then skip.
            if (!_isForThisApi($input, selectedAPI)) {
                resetApiInput($input, true, importer.vars.hideFieldWhenNotActive);
                return true;
            }

            if (true === clearValue) {
                $input.val('');
            }

            $input
                .removeAttr('readonly')
                .parents('.option').show();
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
    var _validateSubdomain = function($input) {
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
    }

    importer.fields = fields;

})(jQuery, window, document, window.awesomeSupportImporter);