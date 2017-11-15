<?php

namespace Pressware\AwesomeSupport\Importer;

interface ImporterInterface
{
    /**
     * Clears and resets the importer.
     *
     * @since 0.1.0
     *
     * @return ImporterInterface
     */
    public function clear();

    /**
     * Imports the supplied tickets into the database.
     *
     * @since 0.1.0
     *
     * @param array $tickets
     *
     * @return boolean
     */
    public function import(array $tickets);
}
