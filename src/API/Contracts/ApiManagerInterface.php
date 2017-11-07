<?php

namespace Pressware\AwesomeSupport\API\Contracts;

interface ApiManagerInterface
{
    /**
     * Get the selected API.
     *
     * @since 0.1.0
     *
     * @param string $requestedApi
     * @param array $config Runtime configuration parameters
     *
     * @return bool|ApiInterface
     */
    public function getApi($requestedApi, array $config);
}
