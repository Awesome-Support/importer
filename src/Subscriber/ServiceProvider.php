<?php

namespace Pressware\AwesomeSupport\Subscriber;

use Pressware\AwesomeSupport\API\ApiManager;
use Pressware\AwesomeSupport\API\Contracts\ApiManagerInterface;
use Pressware\AwesomeSupport\Importer\ServiceProvider as ImporterServiceProvider;
use Pressware\AwesomeSupport\Notifications\Contracts\NotificationInterface;
use Pressware\AwesomeSupport\Notifications\NotifierSubscriber;

class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @var NotificationInterface
     */
    protected $notifier;

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
     * ServiceProvider constructor.
     *
     * @since 0.1.1
     *
     * @param NotificationInterface $notifier
     */
    public function __construct(NotificationInterface $notifier)
    {
        $this->notifier = $notifier;
    }

    /**
     * Gets all of the subscribers.
     *
     * @since 0.1.1
     *
     * @param array $config Current runtime parameters.
     *
     * @return array
     */
    public function get(array $config)
    {
        $this->pluginPath = $config['pluginPath'];
        $this->pluginUrl  = $config['pluginUrl'];
        return $this->create($config);
    }

    /**
     * Create each of the subscribers.
     *
     * @since 0.1.1
     *
     * @param array $config
     *
     * @return array Returns an array of subscribers
     */
    protected function create(array $config)
    {
        $apiManager        = new ApiManager($config, $this->notifier);
        $mailboxSubscriber = $apiManager->createHelpScoutMailboxSubscriber();

        return [
            $mailboxSubscriber,
            new MenuSubscriber($config, $mailboxSubscriber),
            $this->createScriptsSubscriber(),
            $this->createStylesSubscriber(),
            $this->createTicketImportSubscriber($config, $apiManager),
            new SerializationSubscriber($config),
            new NotifierSubscriber($config, $this->notifier),
        ];
    }

    /**
     * Create the Scripts Subscriber.
     *
     * @since 0.1.1
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
     * @since 0.1.1
     *
     * @return StyleAssetSubscriber
     */
    protected function createStylesSubscriber()
    {
        $config              = require $this->pluginPath . 'config/styles.php';
        $config['pluginUrl'] = $this->pluginUrl;
        return new StyleAssetSubscriber($config);
    }

    /**
     * Create the TicketImportSubscriber.
     *
     * @since 0.1.1
     *
     * @param array $config
     * @param ApiManagerInterface $apiManager
     *
     * @return TicketImportSubscriber
     */
    protected function createTicketImportSubscriber(array $config, ApiManagerInterface $apiManager)
    {
        return new TicketImportSubscriber(
            $config,
            $apiManager,
            (new ImporterServiceProvider)->create($this->notifier),
            $this->notifier
        );
    }
}
