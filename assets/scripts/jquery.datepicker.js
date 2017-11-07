(function($, window, document, importer) {
    'use strict';

    var dates = {},
        $dateFields;

    /**
     * Handles the jQuery Date Picker allowing the user to select
     * a date range for the ticket import.
     *
     * @private
     */
    dates.init = function() {
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
        $dateFields.on('keypress', function(event){ event.preventDefault() });
    };

    dates.isDateField = function($field) {
        return $field.data('fieldtype') === 'date';
    }

    dates.getDates = function($values) {
        $values = $values || {};

        $.each($dateFields, function() {
            var $input                   = $(this);
            $values[$input.attr('name')] = $input.val();
        });

        return $values;
    }

    importer.dates = dates;

})(jQuery, window, document, window.awesomeSupportImporter);