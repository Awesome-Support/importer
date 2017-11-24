<?php
/**
 * @package   Awesome Support: Importer
 * @author    Pressware, LLC <support@pressware.co>
 * @license   GPL-2.0+
 * @link      https://pressware.co
 * @copyright 2017 Pressware, LLC
 *
 * @wordpress-plugin
 * Plugin Name:       Awesome Support: Importer
 * Plugin URI:        TODO: URL to the final build.
 * Description:       Allows users to import tickets from a variety of help desks into Awesome Support.
 * Version:           1.0.0
 * Author:            Pressware, LLC
 * Author URI:        https://pressware.co
 * Text Domain:       awesome-support-importer
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 */

namespace Pressware\AwesomeSupport;

use Pressware\AwesomeSupport\Notifications\NotifierServiceProvider;
use Pressware\AwesomeSupport\PluginAPI\Manager;
use Pressware\AwesomeSupport\Subscriber\ServiceProvider as SubscriberServiceProvider;

require_once 'vendor/autoload.php';

$loggerConfig             = (array)require __DIR__ . '/config/logger.php';
$loggerConfig['rootPath'] = __DIR__;

$plugin = new Plugin(
    __FILE__,
    (array)require __DIR__ . '/config/plugin.php',
    new SubscriberServiceProvider(
        (new NotifierServiceProvider())->create($loggerConfig)
    ),
    new Manager(),
    new Options('awesome_support')
);
add_action('plugins_loaded', [$plugin, 'load']);
