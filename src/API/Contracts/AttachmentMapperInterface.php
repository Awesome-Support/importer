<?php

namespace Pressware\AwesomeSupport\API\Contracts;

interface AttachmentMapperInterface
{
    /**
     * Maps an array of Attachment Data Structures to the specified repository.
     *
     * @since 0.1.0
     *
     * @param array|mixed $attachments
     * @param int $ticketId
     * @param RepositoryInterface $repository
     * @param callable $attachmentCallback Callback to map an individual attachment's array data structure.
     * @param int $replyId
     *
     * @return bool
     */
    public function map($attachments, $ticketId, RepositoryInterface $repository, $attachmentCallback, $replyId = 0);
}
