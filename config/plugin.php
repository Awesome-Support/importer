<?php

$optionsPrefix = 'awesome-support-importer-';

return [
    'pluginPath'        => '',
    'pluginUrl'         => '',
    'optionsPrefix'     => $optionsPrefix,
    'helpDeskProviders' => [
        'zendesk'    => 'Zendesk',
        'help-scout' => 'Help Scout',
        'ticksy'     => 'Ticksy',
    ],
    'optionsConfig'     => [
        $optionsPrefix . 'help-desk'     => '',
        $optionsPrefix . 'api-mailbox'   => '',
        $optionsPrefix . 'api-subdomain' => '',
        $optionsPrefix . 'api-email'     => '',
        $optionsPrefix . 'api-token'     => '',
        $optionsPrefix . 'date-start'    => '',
        $optionsPrefix . 'date-end'      => '',
        $optionsPrefix . 'app-secret'      => '',
        $optionsPrefix . 'app-id'      => '',
    ],
    'screenName'        => 'awesome_support_import_tickets',
    'redirectUri'       => 'wp-admin/edit.php?post_type=ticket&page=awesome_support_import_tickets',
    'security'          => [
        'action' => $optionsPrefix . 'save',
        'name'   => $optionsPrefix . 'save-nonce',
    ],
];
