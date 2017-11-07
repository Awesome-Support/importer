<?php

namespace Pressware\AwesomeSupport\PluginAPI;

use Pressware\AwesomeSupport\API\Manager;

/**
 * Class AbstractManagerAwareSubscriber
 *
 * @since     0.1.0
 * @package   Pressware\AwesomeSupport\API
 * @author    Pressware, LLC <support@pressware.co>
 */
class AbstractManagerAwareSubscriber
{
    /**
     * WordPress Plugin API manager.
     *
     * @var Manager
     */
    protected $plugin_api_manager;

    /**
     * Set the WordPress Plugin API manager for the subscriber.
     *
     * @param Manager $plugin_api_manager
     */
    public function set(Manager $plugin_api_manager)
    {
        $this->plugin_api_manager = $plugin_api_manager;
    }
}
