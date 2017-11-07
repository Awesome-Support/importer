<?php

namespace Pressware\AwesomeSupport\API\Contracts;

interface DataMapperInterface
{
    /**
     * Assembles the individual datasets into
     * the final Ticket model.
     *
     * @since 0.1.0
     *
     * @return void
     */
    public function assemble();

    /**
     * Maps the incoming JSON to the individual repositories.
     *
     * @since 1.0.0
     *
     * @param string $json
     * @param string $key (Optional)
     *
     * @return void
     */
    public function mapJSON($json, $key = '');

    public function toJSON($toBeConverted);

    public function toArray($json);
}
