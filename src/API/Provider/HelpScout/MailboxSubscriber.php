<?php

namespace Pressware\AwesomeSupport\API\Provider\HelpScout;

use Pressware\AwesomeSupport\API\Contracts\ApiManagerInterface;
use Pressware\AwesomeSupport\Notifications\Contracts\NotificationInterface;
use Pressware\AwesomeSupport\Subscriber\AbstractSubscriber;

/**
 * Class MailboxSubscriber
 * @package Pressware\AwesomeSupport\API\Provider\HelpScout
 * @SuppressWarnings(PHPMD.Superglobals)
 */
class MailboxSubscriber extends AbstractSubscriber
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var ApiManagerInterface
     */
    protected $apiManager;

    /**
     * @var NotificationInterface
     */
    protected $notifier;

    /**
     * @var array
     */
    protected $errorPacket;

    /**
     * MailboxSubscriber constructor.
     *
     * @param array $config Runtime configuration parameters
     * @param ApiManagerInterface $apiManager
     * @param NotificationInterface $notifier
     */
    public function __construct(array $config, ApiManagerInterface $apiManager, NotificationInterface $notifier)
    {
        parent::__construct($config);
        $this->config     = $config;
        $this->apiManager = $apiManager;
        $this->notifier   = $notifier;
    }

    /**
     * {@inheritdoc}
     */
    public static function getHooks()
    {
        return [
            'wp_ajax_getHelpScoutMailboxes' => 'getViaAjax',
        ];
    }

    /**
     * Get the mailboxes via Ajax.
     *
     * @since 0.1.0
     *
     * @return void
     */
    public function getViaAjax()
    {
        wp_verify_nonce($this->security['name'], $_POST['security']);

        $data = $this->getAjaxData();
        if (!$data[$this->optionsPrefix . 'api-token']) {
            throw new \InvalidArgumentException('No API Token was provided.', 404);
        }

        $this->render($this->getMailboxes($data));
        wp_die();
    }

    /**
     * Render the mailbox options.
     *
     * @since 0.1.0
     *
     * @param array $mailboxes Array of mailboxes
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function render(array $mailboxes)
    {
        require $this->config['pluginPath'] . 'views/select-mailbox.php';
    }

    /**
     * Help Scout needs a specific mailbox. Go fetch all of them.
     *
     * @since 0.1.0
     *
     * @param string $selectedMailbox
     *
     * @return array
     */
    public function get($selectedMailbox = '')
    {
        $data = $this->mergeData($selectedMailbox);
        if (!$data[$this->optionsPrefix . 'api-token']) {
            return [];
        }

        try {
            return $this->getMailboxes($data);
        } catch (\Exception $error) {
            $this->errorPacket = $this->notifier->logError($error);
            return [];
        }
    }

    /**
     * Checks if an error occurred when getting the mailboxes from Help Scout.
     *
     * @since 0.1.0
     *
     * @return array
     */
    public function hasError()
    {
        return ($this->errorPacket);
    }

    /**
     * Get the error packet.
     *
     * @since 0.1.0
     *
     * @return array
     */
    public function getError()
    {
        return $this->errorPacket;
    }

    /**
     * Get mailboxes from Help Scout.
     *
     * @since 0.1.0
     *
     * @param array $data
     *
     * @return array
     */
    protected function getMailboxes($data)
    {
        return (array)$this->apiManager
            ->getApi('help-scout', $data)
            ->getMailboxes();
    }

    /**
     * Get the mailboxes data.
     *
     * @since 0.1.0
     *
     * @return array
     */
    protected function getAjaxData()
    {
        $data = [];
        foreach (array_keys($this->config['optionsConfig']) as $key) {
            $data[$key] = strip_tags(stripslashes($_POST[$key]));
        }

        return $data;
    }

    /**
     * Get the mailboxes data.
     *
     * @since 0.1.0
     *
     * @param string $selectedMailbox
     *
     * @return array
     */
    protected function mergeData($selectedMailbox)
    {
        return array_merge(
            $this->optionsConfig,
            [
                $this->optionsPrefix . 'help-desk'   => 'help-scout',
                $this->optionsPrefix . 'api-mailbox' => $selectedMailbox,
                $this->optionsPrefix . 'api-token'   => get_option($this->optionsPrefix . 'api-token'),
            ]
        );
    }
}
