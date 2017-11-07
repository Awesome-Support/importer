<?php

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 */
if (!function_exists('__')) {
    function __($message)
    {
        return $message;
    }
}

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 */
if (!function_exists('_e')) {
    function _e($message)
    {
        echo $message;
    }
}

if (!function_exists('wp_ext2type')) {
    function wp_ext2type($ext)
    {
        $ext = strtolower($ext);

        $ext2type = wp_get_ext_types();
        foreach ($ext2type as $type => $exts) {
            if (in_array($ext, $exts)) {
                return $type;
            }
        }
    }
}

if (!function_exists('wp_get_ext_types')) {
    function wp_get_ext_types()
    {
        return [
            'image'       => ['jpg', 'jpeg', 'jpe', 'gif', 'png', 'bmp', 'tif', 'tiff', 'ico'],
            'audio'       => ['aac', 'ac3', 'aif', 'aiff', 'm3a', 'm4a', 'm4b', 'mka', 'mp1', 'mp2', 'mp3', 'ogg',
                'oga', 'ram', 'wav', 'wma'],
            'video'       => ['3g2', '3gp', '3gpp', 'asf', 'avi', 'divx', 'dv', 'flv', 'm4v', 'mkv', 'mov', 'mp4',
                'mpeg', 'mpg', 'mpv', 'ogm', 'ogv', 'qt', 'rm', 'vob', 'wmv'],
            'document'    => ['doc', 'docx', 'docm', 'dotm', 'odt', 'pages', 'pdf', 'xps', 'oxps', 'rtf', 'wp', 'wpd',
                'psd', 'xcf'],
            'spreadsheet' => ['numbers', 'ods', 'xls', 'xlsx', 'xlsm', 'xlsb'],
            'interactive' => ['swf', 'key', 'ppt', 'pptx', 'pptm', 'pps', 'ppsx', 'ppsm', 'sldx', 'sldm', 'odp'],
            'text'        => ['asc', 'csv', 'tsv', 'txt'],
            'archive'     => ['bz2', 'cab', 'dmg', 'gz', 'rar', 'sea', 'sit', 'sqx', 'tar', 'tgz', 'zip', '7z'],
            'code'        => ['css', 'htm', 'html', 'php', 'js'],
        ];
    }
}
