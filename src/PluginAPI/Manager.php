<?php

namespace Pressware\AwesomeSupport\PluginAPI;

/**
 * Class Manager
 *
 * @since     0.1.0
 * @package   Pressware\AwesomeSupport\API
 * @author    Pressware, LLC <support@pressware.co>
 */
class Manager implements PluginApiInterface
{
    /**
     * The default priority of a given hook.
     */
    const DEFAULT_PRIORITY = 10;

    /**
     * Te default number of arguments passed to a given hook.
     */
    const DEFAULT_ARG_COUNT = 1;

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
    public function addHook($name, $callback, $priority = 10, $accepted_args = 1)
    {
        return add_filter($name, $callback, $priority, $accepted_args);
    }

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
    public function applyHooks(...$args)
    {
        return apply_filters(...$args);
    }

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
    public function doHook(...$args)
    {
        return do_action(...$args);
    }

    /**
     * Get the name of the most recent hook that WordPress has executed or is executing.
     *
     * @uses current_filter()
     * @see https://developer.wordpress.org/reference/functions/current_filter/
     *
     * @return string
     */
    public function getCurrentHook()
    {
        return current_filter();
    }

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
    public function hasHook($name, $callback = false)
    {
        return has_filter($name, $callback);
    }

    /**
     * Registers an object with the WordPress Plugin API.
     *
     * @param mixed $object
     */
    public function register($object)
    {
        if ($object instanceof HookSubscriberInterface) {
            $this->registerHooks($object);
        }

        if ($object instanceof AbstractManagerAwareSubscriber) {
            $object->setPluginAPIManager($this);
        }
    }

    /**
     * Register a filter hook subscriber with a specific hook.
     *
     * @param HookSubscriberInterface $subscriber
     * @param string $name
     * @param mixed $parameters
     */
    private function registerHook(HookSubscriberInterface $subscriber, $name, $parameters)
    {
        if (is_string($parameters)) {
            $this->addHook($name, [$subscriber, $parameters]);
        }

        if (is_array($parameters) && isset($parameters[0])) {
            $this->addHook(
                $name,
                [$subscriber, $parameters[0]],
                isset($parameters[1]) ? $parameters[1] : self::DEFAULT_PRIORITY,
                isset($parameters[2]) ? $parameters[2] : self::DEFAULT_ARG_COUNT
            );
        }
    }

    /**
     * Registers a hook subscriber with all its hooks.
     *
     * @param HookSubscriberInterface $subscriber
     */
    private function registerHooks(HookSubscriberInterface $subscriber)
    {
        foreach ($subscriber->getHooks() as $name => $parameters) {
            $this->registerHook($subscriber, $name, $parameters);
        }
    }
}
