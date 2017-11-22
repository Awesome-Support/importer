# Awesome Support: Importer Tests

By default, the end point and throttle (rate limiter) tests are turned off.  Why? Each Help Desk API needs configuration parameters.

To turn on the end point tests, do the following:

1. Copy the configuration files from `/config/` and put into the `/config/private/` folder.
2. Configure each Help Desk provider, providing the needed credentials.
3. Then set the constant `RUN_HELP_DESK_ENDPOINT_TESTS` to `true`.

```
define('RUN_HELP_DESK_ENDPOINT_TESTS', true);
```
