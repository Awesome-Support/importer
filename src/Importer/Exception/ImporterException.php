<?php

namespace Pressware\AwesomeSupport\Importer\Exception;

use Exception;
use Pressware\AwesomeSupport\Traits\ExceptionTrait;

class ImporterException extends Exception
{
    use ExceptionTrait;

    public function __construct($message = "", $code = 0, array $context = [], $moduleName = '', $previous = null)
    {
        $this->context    = $context;
        $this->moduleName = $moduleName;
        parent::__construct($message, $code, $previous);
    }
}
