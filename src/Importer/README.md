# Importer Module

The Importer Module is responsible for the following tasks:

1. Determining if the ticket or reply already exists in the database.
2. If no, importing (inserting) the new information.

## Determining if it Exists

To avoid duplicates in the database when importing, the `Locator` searches for the piece of content.  If it exists, the `Importer` skips over that one.

### Determining if a Reply Exists

Tickets can have 0 to many replies.  The only relationship between the ticket and reply is the `post_name` column, as each reply is prefixed with `reply-to-ticket-{$ticketId}`.  If there is more than one reply for a given ticket, each has an incremental suffix, e.g. reply-to-ticket-{$ticketId}-1`.

We use a custom SQL query to fetch all of the replies:

```
$sqlQuery = $this->wpdb->prepare(
    'SELECT p.ID AS replyId, p.post_content AS reply' . "\n" .
    "FROM {$this->wpdb->posts} AS p" . "\n" .
    "WHERE p.post_type = 'ticket_reply' AND p.post_name = %s OR p.post_name LIKE %s",
    $slug,
    $slug . '-%'
);
```

If there are records, we loop through them, comparing the record's reply to the current reply being possibly imported.  If there's a match, bail out as we don't want a duplicate.

## Exception Handling

If an error occurs, we stop.  The error is sent to the `ExceptionHandler`, which will log the error and then trigger the Ajax response.