# Zendesk Module

This module is the Zendesk API handler.  It's job is to fetch tickets from Zendesk and then build the array of ticket data structures for importing into the database.

The entire process is multi-stepped:

1. Request all of the tickets (and users) from the starting date.
2. Process the users in the Data Mapper.
3. Process the tickets in the Data Mapper.
4. Request all of the ticket events from the starting date.
5. Process the ticket events JSON in the Data Mapper, mapping the attachments, replies, and history to the appropriate Repository.
6. Assembling the ticket models within the Data Mapper.
7. Returning the tickets upon completion.

## Zendesk

We are using the [Incremental Exports API](https://developer.zendesk.com/rest_api/docs/core/incremental_export).

For each request, Zendesk will send us back up to 1,000 items.  Using a `do/while`, the module will request the ticket packets until the count is less than or equal to a 1,000.

### Synchronizing Tickets & Ticket Events

Our process has 2 separate HTTP GET response cycles:

1. [Tickets](https://developer.zendesk.com/rest_api/docs/core/incremental_export#incremental-ticket-export) with users as a sideload
2. [Ticket Events](https://developer.zendesk.com/rest_api/docs/core/incremental_export#incremental-ticket-event-export) with comments as a sideload

We are not able to combine these into one GET request, as ticket events are not sideload-enabled.  Therefore, we have to request them separately.

Each request is based upon a start time.  Then Zendesk gives us up to 1,000 items back.  That means its possible that each ticket and ticket event GET request will not be synchronized.  Why?  Each ticket event is tied to an update on a ticket.  If one ticket has multiple update events, then the number of items in the response data packet will vary from the ticket's packet.

We solve that problem by doing the GET requests and storing away the JSON responses.  Using the Data Mapper and Repositories, we can incrementally build the appropriate models and pieces of each ticket.