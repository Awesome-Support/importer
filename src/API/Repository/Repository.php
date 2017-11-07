<?php

namespace Pressware\AwesomeSupport\API\Repository;

use ArrayAccess;
use Countable;
use Fulcrum\Extender\Arr\DotArray;
use Pressware\AwesomeSupport\API\Contracts\RepositoryInterface;
use Pressware\AwesomeSupport\Notifications\Contracts\NotificationInterface;

class Repository implements ArrayAccess, RepositoryInterface, Countable
{
    /**
     * @var array
     */
    protected $items;

    /**
     * Error and log handler.
     *
     * @var NotificationInterface
     */
    protected $notifier;

    /**
     * Creates a new repository.
     *
     * @param NotificationInterface $notifier Error and log handler.
     * @param array $items
     */
    public function __construct(NotificationInterface $notifier, array $items = [])
    {
        $this->notifier = $notifier;
        $this->items    = $items;
    }

    /**
     * Clears all items out of the store.
     *
     * @since 0.1.0
     *
     * @return void
     */
    public function clear()
    {
        $this->items = [];
    }

    public function count()
    {
        if (!$this->items) {
            return 0;
        }
        return count($this->items);
    }

    /**
     * Removes an item from the store.
     *
     * @since 0.1.0
     *
     * @param string|int $key
     *
     * @return bool
     */
    public function drop($key)
    {
        if (!$this->has($key)) {
            return null;
        }
        DotArray::remove($this->items, $key);
        return true;
    }

    /**
     * Get a specific item from the store via its "dot" notation key.
     *
     * @since 0.1.0
     *
     * @param string|int $key
     * @param mixed $defaultValue
     *
     * @return mixed|null
     */
    public function get($key, $defaultValue = null)
    {
        if ($this->has($key)) {
            return DotArray::get($this->items, $key);
        }

        return $defaultValue;
    }

    /**
     * Get all items.
     *
     * @since 0.1.0
     *
     * @return array
     */
    public function getAll()
    {
        return $this->items;
    }

    /**
     * Checks if the given item exists, i.e. by its "dot" notation key.
     *
     * @since 0.1.0
     *
     * @param string|int $key
     *
     * @return bool
     */
    public function has($key)
    {
        return DotArray::has($this->items, $key);
    }

    /**
     * Push a value onto an array.
     *
     * @param  string $key "dot" notation key
     * @param  mixed $value value to push
     * @return void
     */
    public function push($key, $value)
    {
        $array   = $this->get($key);
        $array[] = $value;
        $this->set($key, $array);
    }

    /**
     * Set a given value into the store.
     *
     * @since 0.1.0
     *
     * @param string|int $key "dot" notation key.
     * @param mixed $value
     *
     * @return void
     */
    public function set($key, $value)
    {
        DotArray::set($this->items, $key, $value);
    }

    /**
     * Checks if the given item exists, i.e. by its "dot" notation key.
     *
     * @since 0.1.0
     *
     * @param string|int $key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * Get a specific item from the store via its "dot" notation key.
     *
     * @since 0.1.0
     *
     * @param string|int $key
     * @param mixed $defaultValue
     *
     * @return mixed|null
     */
    public function offsetGet($key, $defaultValue = null)
    {
        return $this->get($key, $defaultValue);
    }

    /**
     * Set a value into the store.
     *
     * @since 0.1.0
     *
     * @param string|int $key
     * @param mixed $value
     *
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Removes an item from the store.
     *
     * @since 0.1.0
     *
     * @param string|int $key
     *
     * @return bool
     */
    public function offsetUnset($key)
    {
        return $this->drop($key);
    }
}
