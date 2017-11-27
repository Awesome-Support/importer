# Notifications Module

The notifications module is responsible for the following:

1. Intercepting and handling all PHP exceptions.
2. Notifying all registered listeners of an exception.
3. Logging exceptions using Monolog (stored in logs folder).
4. Logging informational status to track the entire process.

## Exception Handler

This module intercepts all PHP exceptions and then processes them to prepare for the logger.  If we're doing Ajax, it packages up the error and sends it using `wp_send_json_error`; else, it throws the error for the display.

Listeners can register to be alerted when an exception has happened.  For example, the Notifier registers as a listener in order to send the error package to the error logger.

In your application, you throw errors normally.  This module will intercept them.  You don't need to call the module directly.  For example, the module will detect and handle when you do this:

```
throw new Exception('Testing throwing an error.');
```

## Logger

The logger handles writing to the appropriate log file.  This module has 2 loggers:

1. Error logging
2. Informational status logging

### Error Logging

Error logging is handled automatically for you within the module.  When a PHP error is thrown, the `Notifier` will trigger the Error Logger to record it into the `/logs/error.php` file.

### Informational Status Logging

This module provides logging of informational messages to the `/logs/info.log` file.  To log a message, you need the instance of the `Notifier` and then invoke the `log()` method like this:

```
$this->notifier->log(
    $message,
    $context
);
```

where:

`$message` is the message you want to be in the log file
`$context` is an array of parameters you want logged with your message, e.g. `['httpCode' => 429, 'delay' => $delay]`
