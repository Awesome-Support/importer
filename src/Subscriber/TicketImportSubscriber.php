<?php

namespace Pressware\AwesomeSupport\Subscriber;

use Pressware\AwesomeSupport\API\Contracts\ApiManagerInterface;
use Pressware\AwesomeSupport\Importer\ImporterInterface;
use Pressware\AwesomeSupport\Notifications\Contracts\NotificationInterface;

/**
 * Class TicketImportSubscriber
 * @package Pressware\AwesomeSupport\Subscriber
 * @SuppressWarnings(PHPMD.Superglobals)
 */
class TicketImportSubscriber extends AbstractSubscriber
{
    /**
     * @var string
     */
    protected $selectedAPI;

    /**
     * Process via Form Postback (testing) or Ajax.
     * @var bool
     */
    protected $initiatedViaAjax;

    /**
     * When true, button appears for importing via form post back instead of Ajax.
     *
     * @var bool
     */
    protected $importViaPostback;

    /**
     * Error and log handler.
     *
     * @var NotificationInterface
     */
    protected $notifier;

    /**
     * @var ApiManagerInterface
     */
    protected $apiManager;

    /**
     * @var ImporterInterface
     */
    protected $importer;

    /**
     * @var string
     */
    protected $redirectUri;

    /**
     * @var string
     */
    protected $pluginPath;

    public function __construct(
        array $config,
        ApiManagerInterface $apiManager,
        ImporterInterface $importer,
        NotificationInterface $notifier
    ) {
        $this->selectedAPI       = 'default';
        $this->importViaPostback = $config['importViaPostback'];
        $this->apiManager        = $apiManager;
        $this->importer          = $importer;
        $this->notifier          = $notifier;
        parent::__construct($config);
    }

    /**
     * {@inheritdoc}
     */
    public static function getHooks()
    {
        return [
            'wp_ajax_importTicketsByApi'    => 'importTicketsByApi',
        ];
    }

    public function importTicketsByApi()
    {
        if (!$this->okayToImport()) {
            return;
        }

        $this->setSelectedApi();
        $api     = $this->apiManager->getApi($this->selectedAPI, $this->getData());
        $tickets = $api->getTickets();

        $importStats = $this->importer->clear()->importTickets($tickets);
        $this->render($importStats);
        wp_die();
    }

    /**
     * Render the view.
     *
     * @since 0.1.0
     *
     * @param array $importStats
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function render(array $importStats)
    {
        $message         = $importStats['message'];
        $ticketsReceived = $importStats['ticketsReceived'];
        $ticketsImported = $importStats['ticketsImported'];
        $repliesImported = $importStats['repliesImported'];

        $viewFile = $ticketsReceived
            ? 'views/importer-success.php'
            : 'views/importer-no-tickets.php';

        require $this->pluginPath . $viewFile;
    }

    protected function okayToImport()
    {
        if (!$this->importViaPostback) {
            wp_verify_nonce($this->security['name'], $_POST['security']);
            return (defined('DOING_AJAX') && DOING_AJAX);
        }

        /**
         * Processing via Form Post-back. Used for setup and testing.
         */
        // Are we on the right page?
        if (!array_key_exists('page', $_GET) ||
            $_GET['page'] !== 'awesome_support_import_tickets') {
            return false;
        }
        wp_verify_nonce($this->security['name'], $this->security['action']);
        return array_key_exists('import-tickets-action', $_POST);
    }

    protected function setSelectedApi()
    {
        $this->selectedAPI = $_POST[$this->optionsPrefix . 'help-desk'];
        if (!array_key_exists($this->selectedAPI, $this->helpDeskProviders)) {
            throw new \InvalidArgumentException(
                sprintf(
                    __('[%s] is not an available Help Desk.', 'awesome-support-importer'),
                    $this->selectedAPI
                )
            );
        }
    }

    protected function getData()
    {
        $data = [];
        foreach (array_keys($this->optionsConfig) as $key) {
            $data[$key] = strip_tags(stripslashes($_POST[$key]));
        }
        $data['redirectUri'] = $this->redirectUri;
        return $data;
    }
}
