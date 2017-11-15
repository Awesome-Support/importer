<?php

namespace Pressware\AwesomeSupport\Subscriber;

interface ServiceProviderInterface
{
    /**
     * Gets all of the subscribers.
     *
     * @since 0.1.0
     *
     * @param array $config Current runtime parameters.
     *
     * @return array
     */
    public function get(array $config);
}
