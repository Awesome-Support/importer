<?php

namespace Pressware\AwesomeSupport;

interface OptionInterface
{
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
    public function delete($name);

    /**
     * Checks if the option with the given name is empty.
     *
     * @param string $name
     *
     * @return bool
     */
    public function isEmpty($name);

    /**
     * Gets the option for the given name.
     * Returns the default value if the value does not exist
     *
     * @param string $name
     * @param null $default
     *
     * @return array|mixed|null
     */
    public function get($name, $default = null);

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
    public function has($name);

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
    public function set($name, $value);
}
