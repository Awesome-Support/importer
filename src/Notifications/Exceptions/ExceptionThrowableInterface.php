<?php

namespace Pressware\AwesomeSupport\Notifications\Exceptions;

interface ExceptionThrowableInterface
{
    /********************************
     * Throwable Interface - PHP 7.0
     *******************************/

    /**
     * Gets the exception code
     *
     * @since 0.1.0
     *
     * @link http://php.net/manual/en/throwable.getcode.php
     * @return int Returns the exception code as integer in
     *              {@see Exception} but possibly as other type in
     *              {@see Exception} descendants (for example as
     *              string in {@see PDOException}).
     */
    public function getCode();

    /**
     * Gets the message
     *
     * @since 0.1.0
     *
     * @link http://php.net/manual/en/throwable.getmessage.php
     * @return string
     */
    public function getMessage();

    /**
     * Gets the file in which the exception occurred
     *
     * @since 0.1.0
     *
     * @link http://php.net/manual/en/throwable.getfile.php
     * @return string Returns the name of the file from which the object was thrown.
     */
    public function getFile();

    /**
     * Gets the line on which the object was instantiated
     *
     * @since 0.1.0
     *
     * @link http://php.net/manual/en/throwable.getline.php
     * @return int Returns the line number where the thrown object was instantiated.
     */
    public function getLine();

    /**
     * Gets the stack trace
     *
     * @since 0.1.0
     *
     * @link http://php.net/manual/en/throwable.gettrace.php
     * @return array Returns the stack trace as an array in the same format as
     *              {@see debug_backtrace()}.
     */
    public function getTrace();

    /**
     * Gets the stack trace as a string
     *
     * @since 0.1.0
     *
     * @link http://php.net/manual/en/throwable.gettraceasstring.php
     * @return string Returns the stack trace as a string.
     */
    public function getTraceAsString();

    /**
     * Returns the previous Throwable
     *
     * @since 0.1.0
     *
     * @link http://php.net/manual/en/throwable.getprevious.php
     * @return \Throwable|ThrowableInterface|\Exception Returns the previous
     *                  {@see Throwable} if available, or <b>NULL</b> otherwise.
     */
    public function getPrevious();

    /**
     * Gets a string representation of the thrown object
     *
     * @since 0.1.0
     *
     * @link http://php.net/manual/en/throwable.tostring.php
     * @return string Returns the string representation of the thrown object.
     */
    public function __toString();

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
    public function hasModuleName();

    /**
     * Get the name of the module that threw this exception.
     *
     * @since 0.1.0
     *
     * @return string
     */
    public function getModule();

    /**
     * Set the name of the module that threw this exception.
     *
     * @since 0.1.0
     *
     * @param string $moduleName Name of the module that threw this exception.
     *
     * @return string
     */
    public function setModule($moduleName);

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
    public function hasContext();

    /**
     * Gets the contextual data for this exception.
     *
     * @since 0.1.0
     *
     * @return array
     */
    public function getContext();

    /**
     * Sets the contextual data for this exception.
     *
     * @since 0.1.0
     *
     * @param array $context Array of contextual data
     *
     * @return array
     */
    public function setContext(array $context);

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
    public function hasAjaxMessage();

    /**
     * Sets the AJAX message.
     *
     * @since 0.1.0
     *
     * @param string $message The message to store.
     *
     * @return void
     */
    public function setAjaxMessage($message);

    /**
     * Gets the AJAX message.
     *
     * @since 0.1.0
     *
     * @return string|null
     */
    public function getAjaxMessage();

    /**
     * Builds and returns the Ajax message packet.
     *
     * @since 0.1.0
     *
     * @return array
     */
    public function getAjax();
}
