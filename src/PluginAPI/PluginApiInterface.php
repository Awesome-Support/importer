<?php

namespace Pressware\AwesomeSupport\PluginAPI;

interface PluginApiInterface
{
    /**
     * Adds a callback to a specific hook of the WordPress plugin API.
     * add_action and add_filter are equivalent in WordPress.
     * So all hooks can use add_filter here
     *
     * @uses add_filter()
     * @see https://developer.wordpress.org/reference/functions/add_filter/
     *
     * @uses add_action()
     * @see https://developer.wordpress.org/reference/functions/add_action/
     *
     * @param $name
     * @param $callback
     * @param int $priority
     * @param int $accepted_args
     *
     * @return true
     */
    public function addHook($name, $callback, $priority = 10, $accepted_args = 1);

    /**
     * Applies all the changes associated with the given hook to the given value.
     *
     * @uses apply_filters()
     * @see https://developer.wordpress.org/reference/functions/apply_filters/
     *
     * @param array ...$args
     *
     * @return mixed
     */
    public function applyHooks(...$args);

    /**
     * Executes all the functions associated with the given hook.
     *
     * @uses do_action()
     * @see https://developer.wordpress.org/reference/functions/do_action/
     *
     * @param array ...$args
     *
     * @return null
     */
    public function doHook(...$args);

    /**
     * Get the name of the most recent hook that WordPress has executed or is executing.
     *
     * @uses current_filter()
     * @see https://developer.wordpress.org/reference/functions/current_filter/
     *
     * @return string
     */
    public function getCurrentHook();

    /**
     * Checks if the given hook has the given callback.
     * The priority of the callback will be returned or false.
     * If no callback is given will return true or false if there's any callbacks registered to the hook.
     *
     * @uses has_filter()
     * @see https://developer.wordpress.org/reference/functions/has_filter/
     *
     * @param string $name
     * @param mixed $callback
     *
     * @return bool|int
     */
    public function hasHook($name, $callback = false);

    /**
     * Registers an object with the WordPress Plugin API.
     *
     * @param mixed $object
     */
    public function register($object);
}
