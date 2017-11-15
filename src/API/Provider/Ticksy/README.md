# Ticksy Module

This module is the Ticksy API handler.  It's job is to fetch tickets from Ticksy and then build the array of ticket data structures for importing into the database.

The entire process is multi-stepped:

1. Request all of open tickets and then store the JSON responses.
2. Process the JSON responses to:
    - Create the user model(s) and store in the User's Repository
    - Create the ticket and store it into the Ticket Repository
    - Store the history into the History Repository
    - Store the reply(ies) into the Reply Repository
3. Repeat 1 and 2 until there are no more tickets received.    
4. Request all closed tickets and then store the JSON responses.
5. Repeat Step 2 above for the closed tickets.
6. Then assemble the tickets by pulling each part of it from the respective repository.

## API Details

Ticksy sends up to 100 items with each request. There are 2 endpoints: open and closed tickets.

### Get All Tickets

To get the next set of 100 items, we append a page number to the endpoint, e.g. `/2` for page 2.  Ticksy does not provide a next page number.  Therefore, the `ApiController` continues indexing the pages until no tickets are received.

### Authentication

Ticksy incorporates the basic authentication into the endpoint like this:

```
// for Open tickets
https://api.ticksy.com/v1/{subdomain}/{token}/open-tickets.json

// for closed tickets
https://api.ticksy.com/v1/{subdomain}/{token}/closed-tickets.json
``` 

### Within Date Limits

Ticksy does not have endpoints for date requests.  Therefore, we request all of the tickets and then as we iterate through each one, we do a date limit test.  If the ticket is within the limits, it's processed; else, we skip it.

This limitation makes this API run a little slower.

## Known Issues

None.
