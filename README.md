> [!IMPORTANT]
> This bundle have been originally created by Ekino and can be found [here](https://github.com/ekino/EkinoNewRelicBundle). Due to lack of maintenance, we decided to fork it and here is the result.

Tiime NewRelic Bundle

This bundle integrates the NewRelic PHP API into Symfony. For more information about NewRelic, please visit http://newrelic.com. The built-in New Relic agent doesn't add as much Symfony integration as it claims. This bundle adds a lot more essentials. Here's a quick list:

1. **Better transaction naming strategy**: Your transaction traces can be named accurately by route names, the controller name or you can decide on a custom naming strategy via a seamless interface that uses any naming convention you deem fit. While running console commands, it also sets the transaction name as the command name.

2. **Console Commands Enhancements**: While running console commands, its sets the options and arguments passed via the CLI as custom parameters to the transaction trace for easier debugging.

3. **Exception Listening**: It also captures all Symfony exceptions in web requests and console commands and sends them to New Relic (something new relic doesn't do too well itself as symfony aggressively catches all exceptions/errors). It also ensures all HTTP Exceptions (4xx codes) are logged as notices in New Relic and not exceptions to reduce the noise in New Relic.

4. **Interactor Service**: It provides you the New Relic PHP Agent API via a Service class `NewRelicInteractorInterface::class` so in my code, I can inject it into any class, controller, service and do stuff like -

    ```php
    // Bundle
    $this->newRelic->addCustomParameter('name', 'john');

    // Extension
    if (extension_loaded('newrelic')) {
        \newrelic_add_custom_parameter('name', 'john');
    }
    ```

5. **Logging Support**: In development, you are unlikely to have New Relic setup. There's a configuration to enable logging which outputs all New Relic actions to your Symfony log, hence emulating what it would actually do in production.

6. **Ignored Routes, Paths, Commands**: You can configure a list of route name, url paths and console commands to be ignored from New Relic traces.

    ![image](https://cloud.githubusercontent.com/assets/670655/5153003/5c956c1e-7235-11e4-9eb2-d203fa42420b.png)

7. **Misc**: There are other useful configuration like your New Relic API Key, explicitly defining your app name instead of php.ini, notifying New Relic about new deployments via capifony, etc.

## Installation

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Applications that use Symfony Flex

Open a command console, enter your project directory and execute:

```console
composer require tiime/newrelic-bundle
```

### Applications that don't use Symfony Flex

#### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
composer require tiime/newrelic-bundle
```

#### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    Tiime\NewRelicBundle\TiimeNewRelicBundle::class => ['all' => true],
];
```

### Configure the bundle

In New Relic's web interface, make sure to get a valid (REST) API Key, not to be confused with your License key : New Relic Dashboard > Account settings > Integration > API Keys

```yaml
# app/config/config.yml

tiime_new_relic:
    enabled: true                         # Defaults to true
    application_name: Awesome Application # default value in newrelic is "PHP Application", or whatever is set
                                          # as php ini-value
    deployment_names: ~                   # default value is 'application_name', supports string array or semi-colon separated string
    api_key:                              # New Relic API
    api_host: ~                           # New Relic API Host (default value is api.newrelic.com, for EU should be set to api.eu.newrelic.com )
    license_key:                          # New Relic license key (optional, default value is read from php.ini)
    xmit: false                           # if you want to record the metric data up to the point newrelic_set_appname is called, set this to true (default: false)
    logging: false                        # If true, logs all New Relic interactions to the Symfony log (default: false)
    interactor: ~                         # The interactor service that is used. Setting enabled=false will override this value
    twig: true                            # Allows you to disable twig integration (falls back to class_exists(\Twig_Environment::class))
    exceptions: true                      # If true, sends exceptions to New Relic (default: true)
    deprecations: true                    # If true, reports deprecations to New Relic (default: true)
    instrument: false                     # If true, uses enhanced New Relic RUM instrumentation (see below) (default: false)
    http:
        enabled: true
        using_symfony_cache: false        # Symfony HTTP cache (see below) (default: false)
        transaction_naming: route         # route, controller or service (see below)
        transaction_naming_service: ~     # Transaction naming service (see below)
        ignored_routes: []                # No transaction recorded for this routes
        ignored_paths: []                 # No transaction recorded for this paths
    monolog:
        enabled: false                    # When enabled, send application's logs to New Relic (default: disabled)
        channels: [app]                   # Channels to listen (default: null). [See Symfony's documentation](http://symfony.com/doc/current/logging/channels_handlers.html#yaml-specification)
        level: error                      # Report only logs higher than this level (see \Psr\Log\LogLevel) (default: error)
        service: app.my_custom_handler    # Define a custom log handler (default: tiime.new_relic.monolog_handler)
    commands:
        enabled: true                     # If true, logs CLI commands to New Relic as Background jobs (>2.3 only) (default: true)
        ignored_commands: []              # No transaction recorded for this commands (background tasks)
```

## Enhanced RUM instrumentation

The bundle comes with an option for enhanced real user monitoring. Ordinarily the New Relic extension (unless disabled by configuration) automatically adds a tracking code for RUM instrumentation to all HTML responses. Using enhanced RUM instrumentation, the bundle allows you to selectively disable instrumentation on certain requests.

This can be useful if, e.g. you're returning HTML verbatim for an HTML editor.

If enhanced RUM instrumentation is enabled, you can *disable* instrumentation for a given request by passing along a `_instrument` request parameter, and setting it to `false`. This can be done e.g. through the routing configuration.

## Transaction naming strategies

The bundle comes with two built-in transaction naming strategies. `route` and `controller`, naming the New Relic transaction after the route or controller respectively. However, the bundle supports custom transaction naming strategies through the `service` configuration option. If you have selected the `service` configuration option, you must pass the name of your own transaction naming service as the `transaction_naming_service` configuration option.

The transaction naming service class must implement the `Tiime\NewRelicBundle\TransactionNamingStrategy\TransactionNamingStrategyInterface` interface. For more information on creating your own services, see the Symfony documentation on [Creating/Configuring Services in the Container](http://symfony.com/doc/current/book/service_container.html#creating-configuring-services-in-the-container).

## Symfony HTTP Cache

When you are using Symfony's HTTP cache your `app/AppCache.php` will build up a response with your Edge Side Includes (ESI). This will look like one transaction in New Relic. When you set `using_symfony_cache: true` will these ESI request be separate transaction which improves the statistics. If you are using some other reverse proxy cache or no cache at all, leave this to false.

If true is required to set the `application_name`.


## Deployment notification

You can use the `newrelic:notify-deployment` command to send deployment notifications to New Relic. This requires the `api_key` configuration to be set.

The command has a bunch of options, as displayed in the help data.

```
$ app/console newrelic:notify-deployment --help
Usage:
 newrelic:notify-deployment [--user[="..."]] [--revision[="..."]] [--changelog[="..."]] [--description[="..."]]

Options:
 --user         The name of the user/process that triggered this deployment
 --revision     A revision number (e.g., git commit SHA)
 --changelog    A list of changes for this deployment
 --description  Text annotation for the deployment — notes for you
```

## Interactor services

The config key `tiime_new_relic.interactor` will accept a service ID to a service implementing `NewRelicInteractorInterface`.
This bundle comes with a few services that may be suitable for you.

| Configuration value | Description |
| ------------------- | ----------- |
| `Tiime\NewRelicBundle\NewRelic\AdaptiveInteractor` | This is the default interactor. It will check once per request if the NewRelic PHP extension is installed or not. It is a decorator for the `NewRelicInteractor` |
| `Tiime\NewRelicBundle\NewRelic\NewRelicInteractor` | This interactor communicates with NewRelic. It is the one decorator that actually does some work. |
| `Tiime\NewRelicBundle\NewRelic\BlackholeInteractor` | This interactor does nothing. |
| `auto` | This value will check if the NewRelic PHP extension is installed when you build your container. |

Note that if you set `tiime_new_relic.enabled: false` you will always use the `BlackholeInteractor` no matter what value
used for `tiime_new_relic.interactor`.


## Flow of the Request

1. A request comes in and the first thing we do is to `setApplicationName` so that we use the correct license key and name.
2. The `RouterListener` might throw a 404 or add routing values to the request.
3. If no 404 was thrown we `setIgnoreTransaction` which means that we call `NewRelicInteractorInterface::ignoreTransaction()` if we have configured to ignore the route.
4. The Firewall is the next interesting thing that will happen. It could change the controller or throw a 403.
5. The developer might have configured many more request listeners that will now execute and possibly add stuff to the request.
6. We will execute `setTransactionName` to use our `TransactionNamingStrategyInterface` to set a nice name.

All 6 steps will be executed for a normal request. Exceptions to this is 404 and 403 responses that will be created in
step 2 and step 4 respectively. If an exception to these step occurs (I'm not talking about `\Exception`) you will have
the transaction logged with the correct license key but you do not have the proper transaction name. The `setTransactionName` may
have dependencies on data set by other listeners that is why it has such low priority.
