# Help Scout Module

This module is the Help Scout API handler.  It's job is to fetch tickets from Help Scout and then build the array of ticket data structures for importing into the database.

The entire process is multi-stepped as follows:

1. User inputs the API Token and then presses the "Get Mailboxes" button.
2. Via Ajax, we request all of the mailboxes from Help Scout. When done, the `<select>` is populated.
3. The user selects the mailbox from which to import.
4. Date selection is optional.
5. The user presses the "Import Tickets" button.

Now the magic happens:

1. The conversations are requested from Help Scout.
2. Loop through each conversation do the following:
    * Request the conversation object from Help Scout (as this endpoint gives us back the original ticket, replies, users, attachments, and history)
    * Parse the JSON within the Data Mapper, mapping each item into its repository.
3. Assemble the ticket models within the Data Mapper, pulling the items out of the repository.
4. Return the tickets upon completion.

## Help Scout

Help Scout calls tickets "conversations".  As noted above, there are 3 steps to fetch all of the information we need to build the ticket models:

1. [Get all of the mailboxes](https://developer.helpscout.com/help-desk-api/mailboxes/list/).
2. [Get all of the conversations](https://developer.helpscout.com/help-desk-api/conversations/list/).
3. [Get each conversation details](https://developer.helpscout.com/help-desk-api/conversations/get/).

### Authorization

Help Scout requirements basic authorization using the following format:

```
apiToken:X
```

where 

- `apiToken` is a token generated within your Help Scout admin dashboard.
- `X` is a dummy password.

We collect the token from the user when the form is submitted.

#### Get Mailboxes

Conversations are attached to a mailbox.  Help Scout requires that we request conversations based upon a single mailbox.  

To comply, a `<select>` is provided in the admin settings page along with a `token` input field and a "Get Help Scout Mailboxes" button. Clicking the button causes an Ajax request.  

Invoking the `getMailboxes()` method, requests the mailboxes using the endpoint:

```
https://api.helpscout.net/v1/mailboxes.json
```

Refer to the [documentation](https://developer.helpscout.com/help-desk-api/mailboxes/list/) for more information.

#### Get Conversations

Conversations are ticket partials.  These JSON packets include ticket information, but do not include the ticket's comment, replies, attachments, or history.  However, we need to fetch all of the conversations in order to iterate through each and request its conversation details from Help Scout.

Invoking `getTickets()` starts the process, where this module will send a `GET` request to this endpoint:

```
https://api.helpscout.net/v1/mailboxes/{$mailboxId}/conversations.json
```

where `$mailboxId` is the selected mailbox ID (which is passed to the module in the configuration dataset).

We continue fetching conversations until the `page` property is equal to the `pages` property, meaning there are no more available.  To receive the next page, we append `?page=2` to the endpoint.

Refer to the [documentation](https://developer.helpscout.com/help-desk-api/conversations/list/) for more information.

#### Get Conversation Details

All of the ticket details are received when we request a specific conversation ID. These packets include the ticket, replies, users, attachments, and history.  We use this endpoint:

```
https://api.helpscout.net/v1/conversations/{$conversationId}.json
```

Once we receive the JSON packet, it is sent to the Data Mapper for parsing, validation, and storage.  When complete, this module assembles the ticket module and returns them back to the caller.

Refer to the [documentation](https://developer.helpscout.com/help-desk-api/conversations/get/) for more information.


## Rate Limiting (Throttling)

Help Scout has a generous limit of 200 requests / minute.  If we exceed that limit, we will receive a 429 status code as well as `Retry-After` header with the number of seconds to wait.  Guzzle takes care of throttling by capturing this error and applying the `Retry-After` seconds to a `delay` parameter.  The request is sent back again.

## Known Issues

None.