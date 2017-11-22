<?php

namespace Pressware\AwesomeSupport\PluginAPI;

/**
 * HookSubscriberInterface
 *
 * @since     0.1.0
 * @package   Pressware\AwesomeSupport\API
 * @author    Pressware, LLC <support@pressware.co>
 */
interface HookSubscriberInterface
{
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
    public static function getHooks();
}
