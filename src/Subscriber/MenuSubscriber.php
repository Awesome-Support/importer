<?php

namespace Pressware\AwesomeSupport\Subscriber;

use Pressware\AwesomeSupport\API\Provider\HelpScout\MailboxSubscriber;
use Pressware\AwesomeSupport\Traits\CastToTrait;

/**
 * Class MenuSubscriber
 * @package Pressware\AwesomeSupport\Subscriber
 */
class MenuSubscriber extends AbstractSubscriber
{
    use CastToTrait;

    /**
     * @var bool
     */
    private $updatedOption;

    /**
     * When true, button appears for importing via form post back instead of Ajax.
     *
     * @var bool
     */
    protected $importViaPostback;

    /**
     * @var MailboxSubscriber
     */
    protected $mailboxSubscriber;

    /**
     * MenuSubscriber constructor.
     *
     * @param array $config Runtime configuration parameters
     * @param MailboxSubscriber $mailboxSubscriber
     */
    public function __construct(array $config, MailboxSubscriber $mailboxSubscriber)
    {
        parent::__construct($config);
        $this->updatedOption     = false;
        $this->importViaPostback = $config['importViaPostback'];
        $this->mailboxSubscriber = $mailboxSubscriber;
    }

    /**
     * {@inheritdoc}
     */
    public static function getHooks()
    {
        return [
            'added_option'   => ['setUpdatedOption', 1],
            'updated_option' => ['setUpdatedOption', 1],
            'deleted_option' => ['setUpdatedOption', 1],
            'admin_menu'     => 'addImportTicketSubmenu',
        ];
    }

    public function addImportTicketSubmenu()
    {
        add_submenu_page(
            'edit.php?post_type=ticket',
            __('Import Other Help Desk Tickets', 'awesome-support-importer'),
            __('Import Other Help Desk Tickets', 'awesome-support-importer'),
            'manage_options',
            'awesome_support_import_tickets',
            [$this, 'render']
        );
    }

    public function setUpdatedOption($optionKey)
    {
        if (!$this->updatedOption) {
            $this->updatedOption = $this->isValidOption($optionKey);
        }
    }

    /**
     * Render the view. View data is compiled before calling the view.
     *
     * @since 0.1.0
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function render()
    {
        $selectedHelpDesk = get_option($this->optionsPrefix . 'help-desk', 'default');
        $selectedMailbox  = 'help-scout' === $selectedHelpDesk
            ? get_option($this->optionsPrefix . 'api-mailbox', '')
            : '';
        $mailboxes        = 'help-scout' === $selectedHelpDesk
            ? $this->mailboxSubscriber->get($selectedMailbox)
            : [];

        include_once $this->pluginPath . '/views/import-options.php';
    }

    protected function hasUpdatedOption()
    {
        return $this->updatedOption;
    }

    protected function hasValidDateRange()
    {
        return $this->isValidDateRange();
    }

    private function isValidDateRange()
    {
        if (!$this->hasValidOptions()) {
            return true;
        }

        $validDateRange = get_option($this->optionsPrefix . 'invalid-date-range');
        if (empty($validDateRange)) {
            delete_option($this->optionsPrefix . 'invalid-date-range');
            return true;
        }

        return false;
    }

    private function hasValidOptions()
    {
        return get_option($this->optionsPrefix . 'help-desk');
    }

    private function isValidOption($optionKey)
    {
        return in_array(
            $optionKey,
            $this->optionsConfig
        );
    }

    /**
     * Checks if an error occurred when getting the mailboxes from Help Scout.
     *
     * @since 0.1.0
     *
     * @return array
     */
    protected function hasError()
    {
        return $this->mailboxSubscriber->hasError();
    }
}
