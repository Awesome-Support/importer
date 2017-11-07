<option value="" selected>
    <?php _e('Select a mailbox...', 'awesome-support-importer'); ?>
</option>
<?php foreach ($mailboxes as $value => $label) : ?>
    <option value="<?php esc_attr_e($value); ?>">
        <?php esc_attr_e($label, 'awesome-support-importer'); ?>
    </option>
<?php endforeach; ?>