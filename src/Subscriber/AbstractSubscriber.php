<?php

namespace Pressware\AwesomeSupport\Subscriber;

use Pressware\AwesomeSupport\PluginAPI\HookSubscriberInterface;

abstract class AbstractSubscriber implements HookSubscriberInterface
{
    /**
     * @var string
     */
    protected $pluginPath;

    /**
     * @var string
     */
    protected $pluginUrl;

    /**
     * @var string
     */
    protected $optionsPrefix;

    /**
     * @var array
     */
    protected $optionsConfig = [];

    /**
     * Available help desk providers.
     *
     * @var array
     */
    protected $helpDeskProviders = [];

    /**
     * Nonce security.
     *
     * @var array
     */
    protected $security;

    /**
     * AbstractSubscriber constructor.
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
     * Returns an array of hooks to which an object subscribes.
     *
     * The array key is the name of the hook. The value can be:
     *
     *  - the method name
     *  - an array with the method name and priority
     *  - an array with the method name, priority and number of accepted arguments
     *
     * For instance:
     *
     *  - [ 'hook_name' => 'method_name' ]
     *  - [ 'hook_name' => [ 'method_name', $priority ] ]
     *  - [ 'hook_name' => [ 'method_name', $priority, $accepted_args ] ]
     *
     * @return array
     */
    abstract public static function getHooks();
}
