<div id="awesome-support-importer" class="wrap" data-selectedapi="<?php echo $selectedHelpDesk; ?>">
    <h2><?php _e('Awesome Support Importer', 'awesome-support-importer'); ?></h2>
    <?php if ($this->hasUpdatedOption()) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Your options were successfully saved.', 'awesome-support-importer'); ?></p>
        </div>
    <?php endif; ?>

    <?php if (!$this->hasValidDateRange()) : ?>
        <div class="notice notice-error">
            <p><?php _e('Your start date is after your end date.', 'awesome-support-importer'); ?></p>
        </div>
    <?php endif; ?>

    <div class="notice notice-success is-dismissible">
        <h2><?php
			//@TODO: Check for known classes from other add-ons and list the related add-ons here if they're activated.
			//@TODO: Check and warn for minimum version of Awesome Support to be 4.3.2 
            _e(
                'WARNING: You should disable *ALL* Awesome Support Add-ons to avoid unwanted emails and other alerts being sent out for imported tickets.  It will also reduce the chance of any conflicts during the import process.',
                'awesome-support-importer'
            );
            ?></h2>
    </div>

    <h2><?php
        // @codingStandardsIgnoreStart
		_e('Introduction', 'awesome-support-importer');
        // @codingStandardsIgnoreEnd
        ?></h2>
	
	
    <p><?php
        // @codingStandardsIgnoreStart
		_e('Welcome to the Awesome Support Importer where you can import your existing tickets from Zendesk, Helpscout and Ticksy.', 'awesome-support-importer');
        // @codingStandardsIgnoreEnd
        ?></p>
		
    <p><?php
        // @codingStandardsIgnoreStart
		_e('Please make sure you BACKUP your WordPress installation before proceeding!  This import process cannot be reversed.', 'awesome-support-importer');		
        // @codingStandardsIgnoreEnd
        ?></p>		
		
    <h2><?php
        // @codingStandardsIgnoreStart
		_e('What will be imported?', 'awesome-support-importer');
        // @codingStandardsIgnoreEnd
        ?></h2>

    <p><?php
        // @codingStandardsIgnoreStart
        _e('The import process will import the original ticket, the replies, create new agents in Awesome Support if necessary and create new users if necessary.', 'awesome-support-importer');
        // @codingStandardsIgnoreEnd
        ?></p>				
		
    <p><?php
        // @codingStandardsIgnoreStart
        _e('Notes, tags and other features unique to the SAAS service will not be imported.', 'awesome-support-importer');		
        // @codingStandardsIgnoreEnd
        ?></p>

    <h2><?php
        // @codingStandardsIgnoreStart
		_e('Ready? Good, Lets Do It!', 'awesome-support-importer');
        // @codingStandardsIgnoreEnd
        ?></h2>	

    <p><?php
        // @codingStandardsIgnoreStart
        _e('* means the field is required in order to import the tickets.  When all required fields are filled out, then the "Import Tickets" button will be available to you.', 'awesome-support-importer');
        // @codingStandardsIgnoreEnd
        ?></p>

    <form action="" method="post" name="awesome-support-importer">
        <p class="option">
            <label for="awesome-support-importer-help-desk">
                <?php _e('Select a Help Desk Provider', 'awesome-support-importer'); ?>
            </label>
            <select title="awesome-support-importer-help-desk" name="awesome-support-importer-help-desk">
                <option value="default"
                    <?php selected($selectedHelpDesk, 'default'); ?>>
                    <?php _e('Select...', 'awesome-support-importer'); ?>
                </option>
                <?php foreach ($this->helpDeskProviders as $value => $label) : ?>
                    <option value="<?php esc_attr_e($value); ?>"
                        <?php selected($selectedHelpDesk, $value); ?>>
                        <?php esc_attr_e($label, 'awesome-support-importer'); ?>
                    </option>
                <?php endforeach; ?>
            </select>*
        </p>

        <div class="option">
            <p>
                <label for="awesome-support-importer-api-subdomain">
                    <?php _e("Subdomain for the Help Desk Provider", 'awesome-support-importer'); ?>
                </label>
                <input type="text"
                       id="awesome-support-importer-api-subdomain"
                       title="awesome-support-importer-api-subdomain"
                       name="awesome-support-importer-api-subdomain"
                       value="<?php echo get_option('awesome-support-importer-api-subdomain'); ?>"
                       data-forapi="zendesk, ticksy"
                       readonly/>*
                <span class="description">
                    <?php
                    // @codingStandardsIgnoreStart
                    _e('Enter your "subdomain" for selected Help Desk Provider. Only include the subdomain and not the entire URL.', 'awesome-support-importer');
                    // @codingStandardsIgnoreEnd
                    ?>
                </span>
            </p>
            <div class="awesome-support-importer-invalid-subdomain awesome-support-importer-message is-error"
                 style="display: none;">
                <p><?php
                    _e('Error: Invalid subdomain. Please recheck and try again.', 'awesome-support-importer');
                    ?></p>
            </div>
        </div>

        <p class="option">
            <label for="awesome-support-importer-api-email">
                <?php _e("Help Desk's Account Email", 'awesome-support-importer'); ?>
            </label>
            <input type="email"
                   title="awesome-support-importer-api-email"
                   name="awesome-support-importer-api-email"
                   value="<?php echo get_option('awesome-support-importer-api-email'); ?>"
                   data-forapi="zendesk"
                   readonly/>*
            <span class="description">
                <?php
                _e('Please enter your admin email address for this Help Desk provider.', 'awesome-support-importer');
                ?>
            </span>
        </p>

        <div class="helpscout-section" data-helpscout="1">
            <p><?php
                // @codingStandardsIgnoreStart
                _e('Help Scout requires that you select a mailbox from which you want to import the tickets. Follow these steps:', 'awesome-support-importer');
                // @codingStandardsIgnoreEnd
                ?></p>
            <ol>
                <li><?php _e('Enter the App ID, App Secret first.', 'awesome-support-importer'); ?></li>
                <li><?php _e('Then click on the "Save" button.', 'awesome-support-importer'); ?></li>
                <li><?php _e('Then click on the "Get Authorize" button.', 'awesome-support-importer'); ?></li>
                <li><?php _e('Enter the API Token from Get Authorize page.', 'awesome-support-importer'); ?></li>
                <li><?php _e('Then click on the "Get Mailboxes" button.', 'awesome-support-importer'); ?></li>
            </ol>
        </div>

        <p class="option">
            <label for="awesome-support-importer-app-id">
                <?php _e("Help Desk's App ID", 'awesome-support-importer'); ?>
            </label>
            <input type="text"
                   id="awesome-support-importer-app-id"
                   title="awesome-support-importer-app-id"
                   name="awesome-support-importer-app-id"
                   value="<?php echo get_option('awesome-support-importer-app-id'); ?>"
                   data-forapi="help-scout"
                   readonly/>*
        </p>

        <p class="option">
            <label for="awesome-support-importer-app-secret">
                <?php _e("Help Desk's App Secret", 'awesome-support-importer'); ?>
            </label>
            <input type="text"
                   id="awesome-support-importer-app-secret"
                   title="awesome-support-importer-app-secret"
                   name="awesome-support-importer-app-secret"
                   value="<?php echo get_option('awesome-support-importer-app-secret'); ?>"
                   data-forapi="help-scout"
                   readonly/>*
            <button id="awesome-support-helpscout-authorize"
                    class="button button-secondary">
                <?php _e('Get Authorize', 'awesome-support-importer'); ?>
            </button>
        </p>

        <p class="option">
            <label for="awesome-support-importer-api-token">
                <?php _e("Help Desk's API Token", 'awesome-support-importer'); ?>
            </label>
            <input type="text"
                   id="awesome-support-importer-api-token"
                   title="awesome-support-importer-api-token"
                   name="awesome-support-importer-api-token"
                   value="<?php echo get_option('awesome-support-importer-api-token'); ?>"
                   data-forapi="all"
                   readonly/>*
            <button id="awesome-support-get-helpscout-mailboxes"
                    class="button button-secondary"
                    data-helpscout="1">
                <?php _e('Get Help Scout Mailboxes', 'awesome-support-importer'); ?>
            </button>
        </p>

        <div class="option" data-helpscout="1">
            <p>
                <label for="awesome-support-importer-api-mailbox">
                    <?php _e('Select the Mailbox to import', 'awesome-support-importer'); ?>
                </label>
                <select title="awesome-support-importer-api-mailbox"
                        name="awesome-support-importer-api-mailbox"
                        data-forapi="help-scout">
                    <option value=""
                        <?php selected($selectedMailbox, ''); ?>>
                        <?php _e('Select a mailbox...', 'awesome-support-importer'); ?>
                    </option>
                    <?php
                    if ('help-scout' === $selectedHelpDesk) :
                        foreach ((array)$mailboxes as $value => $label) : ?>
                            <option value="<?php esc_attr_e($value); ?>"
                                <?php selected($selectedMailbox, $value); ?>>
                                <?php esc_attr_e($label, 'awesome-support-importer'); ?>
                            </option>
                            <?php
                        endforeach;
                    endif; ?>
                </select>*
            </p>
            <p id="awesome-support-importing-getting-mailboxes-message" style="display: none;">
                <img src="<?php echo admin_url() . 'images/loading.gif' ?>" alt="Getting Help Scout Mailboxes"/>
                <span class="import-description description">
                    <?php _e('Getting Help Scout Mailboxes. Please wait....', 'awesome-support-importer'); ?>
                </span>
            </p>
            <div class="awesome-support-importer-mailboxes-loaded awesome-support-importer-message"
                 style="display: none;">
                <p><?php
                    _e(
                        'The mailboxes are now loaded. Please select the mailbox from which to import the tickets.',
                        'awesome-support-importer'
                    );
                    ?></p>
            </div>
            <?php if ($this->hasError()) : ?>
                <div class="awesome-support-importer-message is-error">
                    <p><?php
                        // @codingStandardsIgnoreStart
                        _e('An error occurred getting the mailboxes from Help Scout. Please recheck the API Token and then try again.', 'awesome-support-importer');
                        // @codingStandardsIgnoreEnd
                        ?></p>
                </div>
            <?php endif; ?>
        </div>

        <p class="option">
            <label for="awesome-support-importer-date-start">
                <?php
                _e('What is the earliest date you want to begin importing tickets?', 'awesome-support-importer');
                ?>
            </label>
            <input type="text"
                   title="awesome-support-importer-date-start"
                   name="awesome-support-importer-date-start"
                   value="<?php echo get_option('awesome-support-importer-date-start'); ?>"
                   data-fieldtype="date"/>
            <span class="description">
                <?php _e('(Optional) Please click to select a date.', 'awesome-support-importer'); ?>
            </span>
        </p>

        <p class="option">
            <label for="awesome-support-importer-date-end">
                <?php _e('What is the last date you want to begin importing tickets?', 'awesome-support-importer'); ?>
            </label>
            <input type="text"
                   title="awesome-support-importer-date-end"
                   name="awesome-support-importer-date-end"
                   value="<?php echo get_option('awesome-support-importer-date-end'); ?>"
                   data-fieldtype="date"/>
            <span class="description">
                <?php _e('(Optional) Please click to select a date.', 'awesome-support-importer'); ?>
            </span>
        </p>

        <p class="option">
            <button name="action"
                    id="awesome-support-import-tickets-save"
                    class="button button-primary">
                <?php _e('Save', 'awesome-support-importer'); ?>
            </button>
            <button id="awesome-support-import-tickets"
                    class="button button-secondary" disabled>
                <?php _e('Import Tickets', 'awesome-support-importer'); ?>
            </button>
        </p>

        <?php wp_nonce_field($this->security['action'], $this->security['name']); ?>
    </form>

    <p id="awesome-support-importing-tickets-message">
        <img src="<?php echo admin_url() . 'images/loading.gif' ?>" alt="Importing Tickets"/>
        <span class="import-description description">
            <?php _e('Loading tickets...', 'awesome-support-importer'); ?>
        </span>
    </p>

    <div id="awesome-support-importer-done-message"
         class="awesome-support-importer-status-message"
         style="display: none;"></div>

</div><!-- #awesome-support-importer -->