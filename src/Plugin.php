<?php

namespace Pressware\AwesomeSupport;

use Pressware\AwesomeSupport\PluginAPI\Manager;
use Pressware\AwesomeSupport\PluginAPI\PluginApiInterface;
use Pressware\AwesomeSupport\Subscriber\ServiceProviderInterface;
use Pressware\AwesomeSupport\Subscriber\TicketImportSubscriber;

class Plugin
{
    /**
     * The plugin event manager.
     *
     * @var Manager
     */
    private $pluginApiManager;

    /**
     * Absolute path to the directory where WordPress installed the plugin.
     *
     * @var string
     */
    protected $pluginPath;

    /**
     * URL to the directory where WordPress installed the plugin.
     *
     * @var string
     */
    protected $pluginUrl;

    /**
     * Flag to track if the plugin is loaded.
     *
     * @var bool
     */
    private $loaded;

    /**
     * The plugin options.
     *
     * @var Options
     */
    private $options;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var ServiceProviderInterface
     */
    protected $subscribers;

    /**
     * Plugin constructor.
     *
     * @since 0.1.1
     *
     * @param string $file file path used to determine plugin path and plugin url.
     * @param array $config Array of runtime configuration parameters.
     * @param ServiceProviderInterface $subscribers Handles creating and fetching all subscribers
     * @param PluginApiInterface $pluginManager
     * @param OptionInterface $optionsManager
     */
    public function __construct(
        $file,
        array $config,
        ServiceProviderInterface $subscribers,
        PluginApiInterface $pluginManager,
        OptionInterface $optionsManager
    ) {
        $this->loaded     = false;
        $this->pluginPath = plugin_dir_path($file);
        $this->pluginUrl  = plugin_dir_url($file);

        $this->config                = $config;
        $this->config['pluginPath']  = $this->pluginPath;
        $this->config['pluginUrl']   = $this->pluginUrl;
        $this->config['redirectUri'] = home_url($this->config['redirectUri'], 'https');

        $this->pluginApiManager = $pluginManager;
        $this->subscribers      = $subscribers;
        $this->options          = $optionsManager;
    }

    /**
     * Checks if the plugin is loaded.
     *
     * @since 0.1.0
     *
     * @return bool
     */
    public function isLoaded()
    {
        return $this->loaded;
    }

    /**
     * Load the plugin and register subscribers.
     *
     * @since 0.2.2
     *
     * @return bool
     */
    public function load()
    {
        if ($this->isLoaded()) {
            return false;
        }

        // Bail out if Awesome Support is not loaded.
        if (!$this->isAwesomeSupportLoaded()) {
            return;
        }

        foreach ($this->getSubscribers() as $subscriber) {
            $this->pluginApiManager->register($subscriber);
        }

        $this->addCustomFields();

        $this->loaded = true;

        return $this->isLoaded();
    }

    /**
     * Get the subscribers.
     *
     * @since 0.1.1
     *
     * @return array
     */
    public function getSubscribers()
    {
        return $this->subscribers->get($this->config);
    }

    /**
     * Add a new custom field for the ticket display in Awesome Support.
     *
     * @since 0.2.0
     *
     * @return void
     */
    protected function addCustomFields()
    {
        if (!function_exists('wpas_add_custom_field')) {
            return;
        }

        // Note: Awesome Support automatically adds a `_wpas_` prefix to this custom field name.
        wpas_add_custom_field('help_desk_ticket_id', [
            'title'        => __('Help Desk SaaS Ticket ID', 'awesome-support-importer'),
            'backend_only' => true,
        ]);
    }

    /**
     * Checks if Awesome Support is loaded.
     *
     * If no, it registers the admin notice callback to alert the user.
     *
     * @since 0.2.2
     *
     * @return bool
     */
    protected function isAwesomeSupportLoaded()
    {
        $isLoaded = class_exists('Awesome_Support');

        if (!$isLoaded) {
            add_action('admin_notices', [$this, 'renderAwesomeSupportRequired']);
        }

        return $isLoaded;
    }

    /**
     * Display the Admin Error Notice to alert the user that Awesome Support is required to run this plugin.
     *
     * @since 0.2.2
     *
     * @return void
     */
    public function renderAwesomeSupportRequired()
    {
        include $this->pluginPath . 'views/awesome-support-required.php';
    }
}
