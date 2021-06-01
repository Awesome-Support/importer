<div id="awesome-support-importer" class="wrap" data-selectedapi="<?php echo $selectedHelpDesk; ?>">
    <h2><?php _e('Awesome Support Importer', 'awesome-support-importer'); ?></h2>

    <h2><?php
        // @codingStandardsIgnoreStart
		_e('Get Help Scout Access Token', 'awesome-support-importer');
        // @codingStandardsIgnoreEnd
        ?></h2>

    <form action="" method="post" name="awesome-support-importer">

        <div class="helpscout-section" data-helpscout="1">
            <p><?php
                // @codingStandardsIgnoreStart
                _e('Follow these steps to get Help Scout Access Token:', 'awesome-support-importer');
                // @codingStandardsIgnoreEnd
                ?></p>
            <ol>
                <li><?php _e('Click on the "Get Help Scout Access Token" button.', 'awesome-support-importer'); ?></li>
                <li><?php _e('Then copy the access token.', 'awesome-support-importer'); ?></li>
            </ol>
        </div>
        <input type="hidden" title="awesome-support-importer-help-desk" name="awesome-support-importer-help-desk" value="help-scout" />

        <p class="option">
            <label for="awesome-support-importer-app-id">
                <?php _e("Help Desk's App ID", 'awesome-support-importer'); ?>
            </label>
            <input type="text"
                   id="awesome-support-importer-app-id"
                   title="awesome-support-importer-app-id"
                   name="awesome-support-importer-app-id"
                   value="<?php echo get_option('awesome-support-importer-app-id'); ?>"
                   readonly
                   disabled/>
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
                   readonly
                   disabled/>
        </p>

        <p class="option">
            <label for="awesome-support-importer-app-code">
                <?php _e("Help Desk's API Code", 'awesome-support-importer'); ?>
            </label>
            <input type="text"
                   id="awesome-support-importer-app-code"
                   title="awesome-support-importer-app-code"
                   name="awesome-support-importer-app-code"
                   value="<?php echo $_GET['code']; ?>"
                   readonly
                   disabled
                   />
        </p>

        <p class="option">
            <button name="action"
                    id="awesome-support-helpscout-access-token"
                    class="button button-primary">
                <?php _e('Get Help Scout Access Token', 'awesome-support-importer'); ?>
            </button>
        </p>

        <?php wp_nonce_field($this->security['action'], $this->security['name']); ?>
    </form>


    <div id="awesome-support-access-token-message"
         class="awesome-support-importer-status-message"
         style="display: none;"></div>

</div><!-- #awesome-support-importer -->