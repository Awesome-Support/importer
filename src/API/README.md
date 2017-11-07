# API Module

This model handles interfacing with the selected API, processing the JSON response, building and storing the individual models, and then assembling the `Ticket` models.

It uses Guzzle for all HTTP requests and basic authentication.

## Provider Architecture

Each API Provider uses a Controller-View-Repositories architecture.  

### Repositories

The repositories create a model and store it.  Each provides the ability to:
 
- `create()` creates a single model for storage
- `clear()` empties the repository's storage, removing all of the models
- `count()` provides a count of all the models in the store
- `drop($key)` deletes a specific model from the store
- `get($key)` a specific model by a key or keys using "dot" notation.
- `getAll()` returns all of the models stored
- `has($key)` checks if a specific model or value within the model exists
- `push($key, $value)` pushes a new value onto an array within the model or store
- `set($key, $value)` sets a particular value at the key or keys using "dot notation" 

Dot notation is from Laravel.  It allows you to drill down into a multidimensional array like this:

```
key1.key2.key3
```

where each key is a level within the array.

### Data Mapper

The data mapper serves 2 purposes:

1. Processes the raw JSON and map it to the appropriate repository where the model is stored.
2. Assembles the ticket models

The Controller passes the JSON string to the data mapper's mapping method.  It's tasked with parsing the JSON components and then storing them as models into the appropriate repository for storage.

The Controller then requests an assembly task.  The data mapper builds the `Ticket` models by getting the appropriate model out of the repositories.

### Attachment Mapper

Attachments are a special case in that we need to validate the URL for import.  `AttachmentMapper` handles the following tasks:
 
1. Parses the attachments
2. Runs a series of validation tests:
    - Validates the filename is valid, meaning:
        - Has an extension
        - Extension is valid: image, audio, video, document, spreadsheet, interactive, text, and archive. But not code files such as php, css, etc.
    - Checks for spaces. When present, encodes the filename. Why? On a Mac, we can create files with spaces such as `my cool image.jpg`. That's cool on our Mac, but it's not a valid URL for importing from a remote server.
    - Checks if the URL is not malformed.
    - Validates the URL is readable.
3. If it passes the above tests, it stores the attachment into either the Reply or Ticket Repository.
4. If it fails any of the tests, it appends the attachment's URL to the ticket or reply's comment.

### Controller

Each API Provider has a Controller.  It's job includes:

1. Receive the `getTickets()` request from the view/front-end.
2. Interface with the Help Desk API via Guzzle to get the JSON packets.
3. Trigger the Data Mapper to process the raw JSON packets.
4. Trigger the Data Mapper to assemble the tickets.
5. Send the tickets back to the view/front-end.

Any errors are passed to the Notifier.  Status and tasks are logged within the Notifier's Logger.

## Using this Module

This section provides the details on how to use this module within your application.

### Creating an Instance

Create an instance of the `ApiManager` within your plugin or application.  You'll need to pass it a configuration and an instance of the Notifier.

```
use Pressware\AwesomeSupport\API\ApiManager;

$apiManager = new ApiManager($config, $notifier);
```

### Get the API Instance

Then you can get an instance of the selected Help Desk Api like this:

```
$api = $apiManager->getApi($helpDeskWanted, $config);
```
where
 
- `$helpDeskWanted` The name of the help desk you want: `zendesk`, `help-scout`, or `ticksy`.
- `$config` an array of configuration parameters that the Help Desk needs such as subdomain, token, username, etc. 

### Trigger to Get Tickets

Each API Controller exposes a `getTickets()` method.  Use it when it's time to import all of the tickets from this Help Desk.is:

```
$tickets = $api->getTickets();
```

An array of `Pressware\AwesomeSupport\Entity\Ticket` models is returned per the date query of your request.  These tickets are ready for importing into your site.

## Scalability

There are multiple factors for ensuring this module is scalable.

1. Ability to process an unlimited number of tickets.
2. Respect the API's rate limiting (throttling).
3. Synchronizing the different endpoint requests to ensure we have all of the information for processing.

### Rate Limiting (Throttling)

The Guzzle Client handles the rate limiting for this module.  It's built into the Abstract class.

If we hit the limit, the Help Desk responds with a 429 HTTP code as well as a 'Retry-After' header value.  We use that value to set the number of wait/delay seconds.

Our Guzzle Client sends the ["delay" option](http://docs.guzzlephp.org/en/stable/request-options.html#delay) with the number of microseconds to Guzzle's HTTP handler. Bam, it's handled for us.
