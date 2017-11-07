<?php

namespace Pressware\AwesomeSupport;

use Pressware\AwesomeSupport\API\ApiManager;
use Pressware\AwesomeSupport\Importer\ServiceProvider as ImporterServiceProvider;
use Pressware\AwesomeSupport\Notifications\Contracts\NotificationInterface;
use Pressware\AwesomeSupport\PluginAPI\Manager;
use Pressware\AwesomeSupport\PluginAPI\PluginApiInterface;
use Pressware\AwesomeSupport\Subscriber\MenuSubscriber;
use Pressware\AwesomeSupport\Subscriber\ScriptAssetSubscriber;
use Pressware\AwesomeSupport\Subscriber\SerializationSubscriber;
use Pressware\AwesomeSupport\Subscriber\StyleAssetSubscriber;
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

    protected $notifier;

    /**
     * Abstract_Plugin constructor.
     *
     * @param string $file file path used to determine plugin path and plugin url.
     * @param array $config Array of runtime configuration parameters.
     * @param NotificationInterface $notifier Error and log handler
     * @param PluginApiInterface $pluginManager
     * @param OptionInterface $optionsManager
     */
    public function __construct(
        $file,
        array $config,
        NotificationInterface $notifier,
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
        $this->notifier         = $notifier;
        $this->options          = $optionsManager;
    }

    /**
     * Checks if the plugin is loaded.
     *
     * @return bool TODO
     */
    public function isLoaded()
    {
        return $this->loaded;
    }

    /**
     * @return bool TODO
     */
    public function load()
    {
        if ($this->isLoaded()) {
            return false;
        }

        // Turn on the notifier.
        $this->notifier->startListeningForErrors();

        foreach ($this->getSubscribers() as $subscriber) {
            $this->pluginApiManager->register($subscriber);

            // Call when form post-back option is enabled for importing.
            if ($this->config['importViaPostback'] && $subscriber instanceof TicketImportSubscriber) {
                $this->pluginApiManager->addHook('init', [$subscriber, 'importTicketsByApi'], 20);
            }
        }

        $this->loaded = true;

        return $this->isLoaded();
    }

    /**
     * @return array
     */
    public function getSubscribers()
    {
        $apiManager        = new ApiManager($this->config, $this->notifier);
        $mailboxSubscriber = $apiManager->createHelpScoutMailboxSubscriber();

        return [
            $mailboxSubscriber,
            new MenuSubscriber($this->config, $mailboxSubscriber),
            $this->createScriptsSubscriber(),
            $this->createStylesSubscriber(),
            new TicketImportSubscriber(
                $this->config,
                $apiManager,
                (new ImporterServiceProvider)->create($this->notifier),
                $this->notifier
            ),
            new SerializationSubscriber($this->config),
        ];
    }

    /**
     * Create the Scripts Subscriber.
     *
     * @since 0.0.1
     *
     * @return ScriptAssetSubscriber
     */
    protected function createScriptsSubscriber()
    {
        $config              = require $this->pluginPath . 'config/scripts.php';
        $config['pluginUrl'] = $this->pluginUrl;
        return new ScriptAssetSubscriber($config);
    }

    /**
     * Create the Scripts Subscriber.
     *
     * @since 0.0.1
     *
     * @return StyleAssetSubscriber
     */
    protected function createStylesSubscriber()
    {
        $config              = require $this->pluginPath . 'config/styles.php';
        $config['pluginUrl'] = $this->pluginUrl;
        return new StyleAssetSubscriber($config);
    }
}
