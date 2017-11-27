<?php

/**
 * @credit This trait is inspired by Alain Schlesser's ModuleExceptionTrait
 * @link https://github.com/brightnucleus/exceptions/blob/master/src/ModuleExceptionTrait.php
 */

namespace Pressware\AwesomeSupport\Notifications\Exceptions;

trait ExceptionTrait
{
    /**
     * @var string
     */
    protected $helpDesk;

    /**
     * Array of contextual data.
     * @var array
     */
    protected $context = [];

    /**
     * Name of the module that thrown the exception.
     * @var string
     */
    protected $moduleName = '';

    /**
     * @var string
     */
    protected $ajaxMessage = '';

    /******************
     * Module Name
     *****************/

    /**
     * Checks if this Exception has a help desk.
     *
     * @since 0.1.0
     *
     * @return bool
     */
    public function hasHelpDesk()
    {
        return ('' !== $this->helpDesk);
    }

    /**
     * Get the name of the help desk.
     *
     * @since 0.1.0
     *
     * @return string
     */
    public function getHelpDesk()
    {
        return $this->helpDesk;
    }

    /**
     * Set the name of the help desk.
     *
     * @since 0.1.0
     *
     * @param string $helpDesk Name of the Help Desk
     *
     * @return string
     */
    public function setHelpDesk($helpDesk)
    {
        $this->helpDesk = (string)$helpDesk;
    }


    /******************
     * Module Name
     *****************/

    /**
     * Checks if this Exception has a module name.
     *
     * @since 0.1.0
     *
     * @return bool
     */
    public function hasModuleName()
    {
        return ('' !== $this->moduleName);
    }

    /**
     * Get the name of the module that threw this exception.
     *
     * @since 0.1.0
     *
     * @return string
     */
    public function getModule()
    {
        return $this->moduleName;
    }

    /**
     * Set the name of the module that threw this exception.
     *
     * @since 0.1.0
     *
     * @param string $moduleName Name of the module that threw this exception.
     *
     * @return string
     */
    public function setModule($moduleName)
    {
        $this->moduleName = (string)$moduleName;
    }

    /******************
     * Contextual data
     *****************/

    /**
     * Checks if contextual data is available for this exception.
     *
     * @since 0.1.0
     *
     * @return bool
     */
    public function hasContext()
    {
        return !empty($this->context);
    }

    /**
     * Gets the contextual data for this exception.
     *
     * @since 0.1.0
     *
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Sets the contextual data for this exception.
     *
     * @since 0.1.0
     *
     * @param array $context Array of contextual data
     *
     * @return array
     */
    public function setContext(array $context)
    {
        $this->context = $context;
    }

    /******************
     * AJAX Message
     *****************/

    /**
     * Checks if this exception has an AJAX Message.
     *
     * @since 0.1.0
     *
     * @return bool
     */
    public function hasAjaxMessage()
    {
        return ('' !== $this->ajaxMessage);
    }

    /**
     * Sets the AJAX message.
     *
     * @since 0.1.0
     *
     * @param string $message The message to store.
     *
     * @return void
     */
    public function setAjaxMessage($message)
    {
        $this->ajaxMessage = $message;
    }

    /**
     * Gets the AJAX message if it exists, else the message is returned.
     *
     * @since 0.1.0
     *
     * @return string
     */
    public function getAjaxMessage()
    {
        if (!$this->hasAjaxMessage()) {
            return $this->toMessage($this->getMessage(), false);
        }
        return $this->ajaxMessage;
    }

    /**
     * Builds and returns the Ajax message packet.
     *
     * @since 0.1.0
     *
     * @return array
     */
    public function getAjax()
    {
        return [
            'code'    => $this->getCode(),
            'message' => $this->hasAjaxMessage()
                ? '<p>' . $this->getAjaxMessage() . '</p>'
                : $this->toMessage($this->getMessage(), true),
        ];
    }

    /**
     * Formats the outgoing message to include the Help Desk (if available) and error code.
     *
     * @since 0.1.0
     *
     * @param string $message
     * @param bool|null $applyHTML
     *
     * @return string
     */
    protected function toMessage($message, $applyHTML = null)
    {
        if (true === $applyHTML) {
            $message = "<p>{$message}</p><p>";
        }

        if ($this->hasHelpDesk()) {
            $message = sprintf(
                '%s%s[Help Desk Provider: %s]',
                $message,
                true === $applyHTML ? '' : ' ',
                $this->getHelpDesk()
            );
        }

        return sprintf(
            '%s [Error: %s]%s',
            $message,
            $this->getCode(),
            true === $applyHTML ? '</p>' : ''
        );
    }
}
