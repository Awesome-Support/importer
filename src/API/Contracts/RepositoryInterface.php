<?php

namespace Pressware\AwesomeSupport\API\Contracts;

interface RepositoryInterface
{
    /**
     * Clears all items out of the store.
     *
     * @since 0.1.0
     *
     * @return void
     */
    public function clear();

    /**
     * Removes an item from the store.
     *
     * @since 0.1.0
     *
     * @param string|int $key
     *
     * @return bool
     */
    public function drop($key);

    /**
     * Get a specific item from the store via its key.
     *
     * @since 0.1.0
     *
     * @param string|int $key
     * @param mixed $defaultValue
     *
     * @return mixed|null
     */
    public function get($key, $defaultValue = null);

    /**
     * Get all items.
     *
     * @since 0.1.0
     *
     * @return array
     */
    public function getAll();

    /**
     * Checks if the given item exists, i.e. by its key.
     *
     * @since 0.1.0
     *
     * @param string|int $key
     *
     * @return bool
     */
    public function has($key);

    /**
     * Set a given value into the store.
     *
     * @since 0.1.0
     *
     * @param string|int $key
     * @param mixed $value
     *
     * @return void
     */
    public function set($key, $value);
}
