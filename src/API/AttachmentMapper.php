<?php

namespace Pressware\AwesomeSupport\API;

use Pressware\AwesomeSupport\API\Contracts\AttachmentMapperInterface;
use Pressware\AwesomeSupport\API\Contracts\RepositoryInterface;

/**
 * Class AttachmentMapper
 * @package Pressware\AwesomeSupport\API
 *
 * Handles parsing, validating, and mapping for the attachments.
 */
class AttachmentMapper implements AttachmentMapperInterface
{
    /**
     * @var array
     */
    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

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
     * @return int
     */
    public function map($attachments, $ticketId, RepositoryInterface $repository, $attachmentCallback, $replyId = 0)
    {
        if (empty($attachments) || !is_array($attachments)) {
            return 0;
        }

        list($validAttachments, $invalidAttachments) = $this->parseAttachments($attachments, $attachmentCallback);
        $response = 0;

        $args = compact('ticketId', 'replyId', 'repository');

        if ($validAttachments) {
            array_walk($validAttachments, [$this, 'mapValidAttachment'], $args);
            $response = 1;
        }

        if ($invalidAttachments) {
            array_walk($invalidAttachments, [$this, 'mapInvalidAttachment'], $args);
            $response = 2;
        }

        return $response;
    }

    protected function parseAttachments(array $attachments, $attachmentCallback)
    {
        $dirtyAttachments = array_map($attachmentCallback, $attachments);
        return $this->validateAttachments($dirtyAttachments);
    }

    protected function mapValidAttachment($attachment, ...$args)
    {
        $args       = array_pop($args);
        $repository = $args['repository'];
        if ($args['replyId']) {
            return $repository->setAttachment($args['ticketId'], $args['replyId'], $attachment);
        }
        $repository->setAttachment($args['ticketId'], $attachment);
    }

    protected function mapInvalidAttachment($attachment, ...$args)
    {
        $args       = array_pop($args);
        $repository = $args['repository'];

        $commentKey = $args['replyId']
            ? "{$args['ticketId']}.{$args['replyId']}.reply"
            : "{$args['ticketId']}.description";

        $comment = $repository->get($commentKey);

        // Append the attachment to the comment.
        $comment .= sprintf(
            '<p>%s<pre>%s</pre></p>',
            $this->config['invalidAttachment'],
            $attachment['url']
        );

        return $repository->set($commentKey, $comment);
    }

    /**
     * Validates the attachments checking structure and URL formation.
     *
     * @since 0.1.0
     *
     * @param array $dirtyAttachments
     *
     * @return array [ 'validAttachments', 'invalidAttachments' ];
     */
    protected function validateAttachments(array $dirtyAttachments)
    {
        $validAttachments   = [];
        $invalidAttachments = (array)array_filter($dirtyAttachments, function ($attachment) use (&$validAttachments) {
            $parsed = $this->parseUrlAndFile($attachment);
            if (!$parsed) {
                return true;
            }

            $this->encodeSpaces($attachment, $parsed['urlParts'], $parsed['fileInfo']);

            $isValid = $this->isReadable($attachment['url']);
            if ($isValid) {
                $validAttachments[] = $attachment;
            }

            return !$isValid;
        });

        return [$validAttachments, $invalidAttachments];
    }

    /**
     * Parse the URL and its file to get the parts of each.  If invalid or malformed, returns false to bail out.
     *
     * @since 0.1.0
     *
     * @param array $attachment
     *
     * @return array|bool
     */
    protected function parseUrlAndFile(array $attachment)
    {
        if (!isset($attachment['url']) || !isset($attachment['filename'])) {
            return false;
        }

        if (!$attachment['filename']) {
            return false;
        }

        $fileInfo = pathinfo($attachment['filename']);

        if (!$this->isValidFile($fileInfo)) {
            return false;
        }

        $urlParts = parse_url($attachment['url']);
        // Whoops, malformed.
        if (false === $urlParts) {
            return false;
        }

        return compact('urlParts', 'fileInfo');
    }

    /**
     * Checks if the attachment is accessible and readable.
     *
     * @since 0.1.0
     *
     * @param string $url
     *
     * @return bool
     */
    protected function isReadable($url)
    {
        // Check if the attachment is callable.
        $handle = @fopen(
            $url,
            'r',
            false,
            stream_context_create(['http' => ['ignore_errors' => true]])
        );
        if (false === $handle) {
            return false;
        }

        fclose($handle);
        return true;
    }

    /**
     * Checks if the attachment's file is a valid attachment for importing.
     *
     * Allows image, audio, video, document, spreadsheet, interactive, text, and archive.
     * Does not allow code files, i.e. css, js, php, etc.
     *
     * @uses WordPress' wp_ext2type() to get the file type.
     *
     * @since 0.1.0
     *
     * @param array $fileInfo
     *
     * @return bool
     */
    protected function isValidFile(array $fileInfo)
    {
        // Make sure there's an extension.
        if (!isset($fileInfo['extension'])) {
            return false;
        }

        // Check the extension and file type.
        $type = wp_ext2type($fileInfo['extension']);
        if (!$type || 'code' === $type) {
            return false;
        }

        // Passed all of the tests. It's a valid, importable attachment.
        return true;
    }

    /******************************
     * URL Encoding - No Spaces
     ******************************/

    /**
     * Checks if the file's name has spaces in it.
     *
     * @since 0.1.0
     *
     * @param string $file
     *
     * @return bool
     */
    protected function hasSpaces($file)
    {
        return preg_match('/\s/', $file) === 1;
    }

    /**
     * Checks if the URL has spaces.  If yes, it urlencodes both the URL and filename to prepare for importing.
     *
     * @since 0.1.0
     *
     * @param array $attachment ByReference - Changes values if spaces are present.
     * @param array $urlParts
     * @param array $fileInfo
     *
     * @return void
     */
    protected function encodeSpaces(array &$attachment, array $urlParts, array $fileInfo)
    {
        $file = $this->extractFileFromUrl($urlParts, $fileInfo['extension']);

        if (!$this->hasSpaces($file)) {
            return;
        }

        // encode the URL and filename.
        $attachment['url']      = str_replace($file, urlencode($file), $attachment['url']);
        $attachment['filename'] = str_replace(
            $fileInfo['basename'],
            urlencode($fileInfo['basename']),
            $attachment['filename']
        );
    }

    /**
     * In order to urlencode for spaces, first we need to extract the file name from the URL. That means we need to
     * locate the file name, as could be in the path/to or a query parameter.
     *
     * @since 0.1.0
     *
     * @param array $urlParts
     * @param string $extension
     *
     * @return string
     */
    protected function extractFileFromUrl(array $urlParts, $extension)
    {
        // has a query. Check there first.
        if (isset($urlParts['query']) &&
            has_substring($urlParts['query'], '.' . $extension)) {
            parse_str($urlParts['query'], $vars);
            foreach ($vars as $value) {
                if (has_substring($value, '.' . $extension)) {
                    return $value;
                }
            }
        }

        return pathinfo($urlParts['path'], PATHINFO_BASENAME);
    }
}
