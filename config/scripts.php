<?php

$productionScriptHandle = 'awesome-support-importer-admin';

$config = [
    'pluginUrl'       => '',
    'scripts'         => [],
    'scriptLocalVars' => [],
];

/**********************************************************
 * Script Localized variables runtime configuration.
 ********************************************************/
$config['scriptLocalVars'] = [
    $productionScriptHandle => [
        'objectName' => 'awesomeSupportImporterVars',
        'data'       => [
            'ajaxErrorMessage'       => __(
                '<h3 class="error-heading">An error occurred during the import process.</h3>',
                'awesome-support-importer'
            ),
            'ajaxMailboxesMessage'   => __(
                '<p>The mailboxes are now loaded. Select the mailbox from which to import.</p>',
                'awesome-support-importer'
            ),
            'hideFieldWhenNotActive' => true,
        ],
    ],
];


/**********************************************************
 * Raw individual scripts for Debug/Dev mode
 ********************************************************/

if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) {
    $config['scripts'] = [
        $productionScriptHandle               => [
            'file'         => 'assets/scripts/jquery.admin.js',
            'dependencies' => ['jquery'],
            'version'      => null,
            'inFooter'     => false,
        ],
        'awesome-support-importer-fields'     => [
            'file'         => 'assets/scripts/jquery.fields.js',
            'dependencies' => ['jquery'],
            'version'      => null,
            'inFooter'     => false,
        ],
        'awesome-support-importer-helpscout'  => [
            'file'         => 'assets/scripts/jquery.helpscout.js',
            'dependencies' => ['jquery'],
            'version'      => null,
            'inFooter'     => false,
        ],
        'awesome-support-importer-datepicker' => [
            'file'         => 'assets/scripts/jquery.datepicker.js',
            'dependencies' => ['jquery', 'jquery-effects-core', 'jquery-ui-datepicker'],
            'version'      => null,
            'inFooter'     => false,
        ],
    ];

    return $config;
}

/**********************************************************
 * Production script
 ********************************************************/

$config['scripts'][$productionScriptHandle] = [
    'file'         => 'assets/dist/importer.js',
    'dependencies' => ['jquery', 'jquery-effects-core', 'jquery-ui-datepicker'],
    'version'      => null,
    'inFooter'     => false,
];

return $config;
