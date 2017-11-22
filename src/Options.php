<?php

namespace Pressware\AwesomeSupport;

/**
 * Class Options
 *
 * @since     0.1.0
 * @package   Pressware\AwesomeSupport
 * @author    Toby Schrapel <toby@tobyschrapel.com>
 */
class Options implements OptionInterface
{
    /**
     * The prefix used by all options names.
     *
     * @var string
     */
    private $prefix;

    /**
     * The WordPress options array
     *
     * @var array
     */
    private $options;

    /**
     * Options constructor.
     *
     * @param string $prefix
     * @param array $options
     */
    public function __construct($prefix, array $options = [])
    {
        $this->prefix  = $prefix;
        $this->options = $options;
    }

    /**
     * Get the option name used to store the option in the WordPress database.
     *
     * @param string $name
     *
     * @return string
     */
    private function generateKey($name)
    {
        return $this->prefix . '_' . $name;
    }

    /**
     * Deletes the option with the given name.
     *
     * @uses delete_option()
     * @see https://developer.wordpress.org/reference/functions/delete_option/
     *
     * @param string $name
     *
     * @return bool
     */
    public function delete($name)
    {
        if (!$this->has($name)) {
            return false;
        }

        unset($this->options[$name]);

        return delete_option($this->generateKey($name));
    }

    /**
     * Checks if the option with the given name is empty.
     *
     * @param string $name
     *
     * @return bool
     */
    public function isEmpty($name)
    {
        return empty($this->get($name));
    }

    /**
     * Gets the option for the given name.
     * Returns the default value if the value does not exist
     *
     * @param string $name
     * @param null $default
     *
     * @return array|mixed|null
     */
    public function get($name, $default = null)
    {
        if (!$this->has($name)) {
            return $default;
        }

        $return = $this->options[$name];

        if (is_array($default) && !is_array($return)) {
            $return = (array)$return;
        }

        return $return;
    }

    /**
     * Checks if the option with the given name exists or not.
     *
     * @uses get_option()
     * @see https://developer.wordpress.org/reference/functions/get_option/
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        if (!isset($this->options[$name])) {
            $this->options[$name] = get_option($this->generateKey($name), null);
        }

        return null !== $this->options[$name];
    }

    /**
     * Sets an option.
     * Overwrites the existing option if the name is already in use.
     *
     * @uses update_option()
     * @see https://developer.wordpress.org/reference/functions/update_option/
     *
     * @param string $name
     * @param $value
     *
     * @return bool
     */
    public function set($name, $value)
    {
        $this->options[$name] = $value;

        return update_option($this->generateKey($name), $value, false);
    }
}
