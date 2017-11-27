(function($, window, document, importer) {
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
    helpScout.init = function() {
        $mailboxSelect          = $('select[name="awesome-support-importer-api-mailbox"]');
        $helpScoutMessage       = $('#awesome-support-importing-getting-mailboxes-message');
        $mailboxesLoadedMessage = $('.awesome-support-importer-mailboxes-loaded');
        $errorMessage           = $('.awesome-support-importer-message');
        $('#awesome-support-get-helpscout-mailboxes').on('click', getMailboxes);
    };

    helpScout.render = function(selectedApi) {
        if (isHelpScout(selectedApi)) {
            show();
        } else {
            helpScout.hideMailboxSelect(true);
        }
    }

    helpScout.getValues = function($values) {
        $values = $values || {};

        var selectedMailbox                  = $mailboxSelect.val();
        $values[$mailboxSelect.attr('name')] = null === selectedMailbox
            ? ''
            : selectedMailbox;

        return $values;
    }

    helpScout.hideMessage = function() {
        $helpScoutMessage.hide();
        $mailboxesLoadedMessage.hide();
        $errorMessage.hide();
    }

    helpScout.hideMailboxSelect = function(resetOption) {
        $mailboxSelect.parent('.option').hide();
        if (true === resetOption) {
            $mailboxSelect.val('');
        }
    }

    helpScout.readyForImport = function(isReady, selectedApi) {
        if (true !== isReady || !isHelpScout(selectedApi)) {
            return isReady;
        }

        return '' !== $mailboxSelect.val();
    }

    var isHelpScout = function(selectedApi) {
        return 'help-scout' === selectedApi;
    }

    var show = function() {
        $mailboxSelect.parent('.option').show();
    }


    var getMailboxes = function(event) {
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
        var data      = importer.getOptionValues();
        data.action   = 'getHelpScoutMailboxes';
        data.security = $('#awesome-support-importer-save-nonce').val();

        // Then do the actual request.
        $.post(ajaxurl, data, function(html) {
            $mailboxesLoadedMessage.slideDown();
console.log('got them!');
            $mailboxSelect
                .empty()
                .append(html)
                .val('');

        }).fail(function(jqXHR) {
            importer.loadErrorMessage(jqXHR);
        }).always(function() {
            // Always reset the form's interface when the Ajax request is done.
            $helpScoutMessage.hide();
            importer.resetFormAjaxDone();
        });
    }

    importer.helpScout = helpScout;

})(jQuery, window, document, window.awesomeSupportImporter);