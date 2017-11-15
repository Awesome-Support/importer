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
        $this->selectedAPI = 'default';
        $this->apiManager  = $apiManager;
        $this->importer    = $importer;
        $this->notifier    = $notifier;
        parent::__construct($config);
    }

    /**
     * {@inheritdoc}
     */
    public static function getHooks()
    {
        return [
            'wp_ajax_importTicketsByApi' => 'importTicketsByApi',
        ];
    }

    /**
     * Import the tickets. Ajax callback.
     *
     * @since 0.2.0
     *
     * @return string
     */
    public function importTicketsByApi()
    {
        wp_verify_nonce($this->security['name'], $_POST['security']);

        // Turn on the notifier to listen for any errors.
        $this->notifier->startListeningForErrors();

        // Get the tickets from the selected Help Desk Provider.
        $this->setSelectedApi();
        $api     = $this->apiManager->getApi($this->selectedAPI, $this->getData());
        $tickets = $api->getTickets();

        // Import the tickets.
        $importStats = $this->importer->clear()->import($tickets);

        // Render the results and send it back to the browser.
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
