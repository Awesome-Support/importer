<?php
namespace Pressware\AwesomeSupport\PluginAPI;

abstract class AbstractAssetSubscriber implements HookSubscriberInterface
{
    /**
     * @var string
     */
    protected $pluginUrl;

    /**
     * AbstractAssetSubscriber constructor.
     *
     * @param array $config Runtime configuration parameters
     */
    public function __construct(array $config)
    {
        foreach ($config as $property => $value) {
            if (property_exists($this, $property)) {
                $this->{$property} = $value;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getHooks()
    {
        return [];
    }

    public function isCurrentAdminPage()
    {
        return 'ticket_page_awesome_support_import_tickets' === get_current_screen()->id;
    }

    abstract public function enqueue();
}
