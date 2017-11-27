<p><?php esc_html_e($message); ?></p>
<p><?php _e('Here are the results:', 'awesome-support-importer'); ?></p>
<ul>
    <li><?php _e('Number of tickets received from Help Desk: ', 'awesome-support-importer'); ?>
        <?php echo (int)$ticketsReceived; ?></li>
    <li><?php _e('Number of tickets imported: ', 'awesome-support-importer'); ?>
        <?php echo (int)$ticketsImported; ?></li>
    <li><?php _e('Number of tickets not imported (already in ' .
            'database): ', 'awesome-support-importer'); ?>
        <?php echo (int)($ticketsReceived - $ticketsImported); ?></li>
    <li><?php _e('Number of replies imported: ', 'awesome-support-importer'); ?>
        <?php echo (int)$repliesImported; ?></li>
</ul>