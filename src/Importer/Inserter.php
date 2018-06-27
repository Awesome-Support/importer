<?php

namespace Pressware\AwesomeSupport\Importer;

use Pressware\AwesomeSupport\Entity\User;
use Pressware\AwesomeSupport\Notifications\Contracts\NotificationInterface;
use Pressware\AwesomeSupport\Importer\Exception\InserterException;
use Pressware\AwesomeSupport\Notifications\Exceptions\ImportException;
use WP_Error;
use WP_User;
use WPAS_File_Upload;

class Inserter
{
    /**
     * @var NotificationInterface
     */
    protected $notifier;

    /**
     * @var Locator
     */
    protected $locator;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var int
     */
    protected $currentUserId;

    /**
     * @var wpdb
     */
    protected $wpdb;

    /**
     * @var object|WPAS_File_Upload
     */
    protected $fileUpload;

    /**
     * Importer constructor.
     *
     * @param NotificationInterface $notifier
     * @param Locator $locator
     * @param Validator $validator
     */
    public function __construct(NotificationInterface $notifier, Locator $locator, Validator $validator)
    {
        $this->notifier  = $notifier;
        $this->locator   = $locator;
        $this->validator = $validator;

        $this->currentUserId = get_current_user_id();
        $this->fileUpload    = WPAS_File_Upload::get_instance();

        global $wpdb;
        $this->wpdb = $wpdb;

        // Let's make sure that image.php is loaded
        // Awesome Support requires this file.
        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
        }

        $this->initUploadsDir(wp_upload_dir());
    }

    /**
     * Insert the user into the database.
     *
     * @since 0.1.0
     *
     * @param User $user
     *
     * @return false|WP_User
     */
    public function insertUser(User $user)
    {
        add_filter('wpas_insert_user_data', function ($data) use ($user) {
            $data['role'] = $user->getRole();

            return $data;
        });

        $lastName = $user->getLastName();
        $userId   = wpas_insert_user([
            'email'      => $user->getEmail(),
            'first_name' => $user->getFirstName(),
            'last_name'  => $lastName ?: 'AwesomeSupport',
            'pwd'        => 'xyz',
        ], false);

        if ($userId instanceof WP_Error) {
            $this->throwUserError($user, $userId, __METHOD__);
        }

        return get_user_by('id', $userId);
    }

    /**
     * @param $source
     */
    public function insertSource($source)
    {
        wp_insert_term($source, 'ticket_channel');
    }

    /**
     * Insert the Ticket into the database.
     *
     * @since 0.1.1
     *
     * @param $customerId
     * @param $agentId
     * @param $subject
     * @param $description
     * @param $source
     *
     * @return bool|int|WP_Error
     */
    public function insertTicket($customerId, $agentId, $subject, $description, $source)
    {
        // If there is no agent, set to the current User's ID.
        if (!$agentId) {
            $agentId = $this->currentUserId;
        }

        wp_set_current_user($customerId);

        $ticketId = wpas_insert_ticket([
            'post_title'   => $subject,
            'post_content' => $description,
        ], false, $agentId, $source);

        wp_set_current_user($this->currentUserId);

        if ($ticketId instanceof WP_Error) {
            $this->throwTicketError($ticketId, $subject, $description, $source, __METHOD__);
        }

        return $ticketId;
    }

    /**
     * Set the Help Desk's Ticket ID in the post meta database table.
     *
     * @since 0.2.0
     *
     * @param int $ticketId The ticket's post ID.
     * @param string|int $helpDeskId The Help Desk's original ID.
     *
     * @return bool|int
     */
    public function setHelpDeskTicketId($ticketId, $helpDeskId)
    {
        return update_post_meta($ticketId, '_wpas_help_desk_ticket_id', sanitize_text_field($helpDeskId));
    }
    /**
     * Set the Help Desk's Ticket date in the post meta database table.
     *
     * @since 0.2.0
     *
     * @param int $ticketId The ticket's post ID.
     * @param string $createdAt The Help Desk's creation date.
     * @param string $updatedAt The Help Desk's modified date.
     *
     * @return bool|int
     */
    public function setHelpDeskTicketDate($ticketId, $createdAt, $updatedAt)
    {
        return wp_update_post(
            [
                'ID'                => $ticketId,
                'post_date'         => $createdAt,
                'post_date_gmt'     => get_gmt_from_date($createdAt),
                'post_modified'     => $updatedAt,
                'post_modified_gmt' => get_gmt_from_date($updatedAt),
            ]
        );
    }
    
    /**
     * Upload and insert the attachment into the database.
     *
     * @since 0.1.0
     *
     * @param int $postId
     * @param array $attachment
     * @return null
     */
    public function insertAttachment($postId, array $attachment)
    {
        if (!$attachment || !isset($attachment['url'])) {
            return;
        }

        $this->fileUpload->process_attachments($postId, [
            [
                'filename' => $attachment['filename'],
                'data'     => file_get_contents($attachment['url']),
            ],
        ]);
    }

    /**
     * Insert the reply into the database.
     *
     * @since 0.1.0
     *
     * @param int $ticketId
     * @param string $reply
     * @param WP_User $author
     * @param string $date
     * @param bool|string $read
     *
     * @return int|WP_Error
     * @throws ImportException
     */
    public function insertReply($ticketId, $reply, WP_User $author, $date, $read, $private = false)
    {
        wp_set_current_user($author->ID);

        $replyId = wpas_insert_reply(
            [
                'post_content'     => $reply,
                'post_author'      => $author->ID,
                'post_date'        => $date,
                'post_status'      => $read ? 'read' : 'unread',
            ], 
            $ticketId
        );

        $this->setHelpDeskReplyDate($replyId, $date);
        $this->setHelpDeskReplyPrivate($replyId, $private);

        wp_set_current_user($this->currentUserId);

        if ($replyId instanceof WP_Error) {
            $this->throwReplyError($replyId, __METHOD__);
        }

        return $replyId;
    }

    /**
     * Set the Help Desk's Reply ID in the post meta database table.
     *
     * @since 0.2.0
     *
     * @param int $replyId The reply's post ID.
     * @param string|int $helpDeskId The Help Desk's original ID.
     *
     * @return bool|int
     */
    public function setHelpDeskReplyId($replyId, $helpDeskId)
    {
        return update_post_meta($replyId, '_wpas_help_desk_reply_id', sanitize_text_field($helpDeskId));
    }

    /**
     * Set the Help Desk's Reply date in the post database table.
     *
     * @since 0.2.0
     *
     * @param int $replyId The reply's post ID.
     * @param string $date The Help Desk's date.
     *
     * @return bool|int
     */
    public function setHelpDeskReplyDate($replyId, $date)
    {
        return wp_update_post(
            [
                'ID'                => $replyId,
                'post_date'         => $date,
                'post_date_gmt'     => get_gmt_from_date($date),
                'post_modified'     => $date,
                'post_modified_gmt' => get_gmt_from_date($date),
            ]
        );
    }
    
    /**
     * Set the Help Desk's Reply author in the post database table.
     *
     * @since 0.2.0
     *
     * @param int $replyId The reply's post ID.
     * @param string $author The Help Desk's author.
     *
     * @return bool|int
     */
    public function setHelpDeskReplyAuthor($replyId, WP_User $author)
    {
        return wp_update_post(
            [
                'ID'                => $replyId,
                'post_author'       => $author->ID,
            ]
        );
    }

    /**
     * Set the Help Desk's Reply as a private note in the post database table.
     *
     * @since 0.2.0
     *
     * @param int $replyId The reply's post ID.
     * @param bool $private The Help Desk's private status.
     *
     * @return bool|int
     */
    public function setHelpDeskReplyPrivate($replyId, $private)
    {
        return wp_update_post(
            [
                'ID'            => $replyId,
                'post_type'     => ($private) ? 'ticket_note' : 'ticket_reply'
            ]
        );
    }
    
    /**
     * Insert/Update History Item into the database.
     *
     * @since 0.1.0
     *
     * @param int $ticketId
     * @param WP_User $author
     * @param string $date
     * @param string $status
     *
     * @return int|WP_Error
     * @throws InserterException
     */
    public function insertHistoryItem($ticketId, WP_User $author, $date, $status)
    {
        wp_set_current_user($author->ID);

        if ('closed' === $status) {
            wpas_close_ticket($ticketId, 0, true);
        }
        if ('open' === $status) {
            wpas_reopen_ticket($ticketId);
        }
        if ('closed' !== $status && 'open' !== $status) {
            wpas_update_ticket_status($ticketId, $status);
        }

        $historyId = $this->locator->findPostByMetaId($this->wpdb->insert_id);
        $response = wp_update_post([
            'ID'            => $historyId,
            'post_author'   => $author->ID,
            'post_date'     => $date,
            'post_date_gmt' => get_gmt_from_date($date),
        ]);

        if ('closed' === $status) {
            update_post_meta($ticketId, '_ticket_closed_on', $date);
            update_post_meta($ticketId, '_ticket_closed_on_gmt', get_gmt_from_date($date));
        }

        wp_set_current_user($this->currentUserId);

        if (!$response instanceof WP_Error) {
            return $response;
        }

        throw new InserterException(
            $response->get_error_message(),
            $response->get_error_code(),
            [
                'userId'   => $author->ID,
                'postDate' => $date,
                'status'   => $status,
            ],
            __CLASS__
        );

        return $historyId;
    }

    /**
     * Set the history ID in the post meta database table.
     *
     * @since 0.2.0
     *
     * @param int $historyId The history's post ID.
     * @param string|int $helpDeskId The original ID.
     *
     * @return bool|int
     */

    public function setHelpDeskHistoryId($historyId, $helpDeskId)
    {
        return update_post_meta($historyId, '_wpas_help_desk_history_id', sanitize_text_field($helpDeskId));
    }

    /**
     * Initialize the uploads directory. Let's make sure our folder exists.
     *
     * @since 0.1.0
     *
     * @param array $uploadsDir
     *
     * @return void
     */
    protected function initUploadsDir(array $uploadsDir)
    {
        $uploadsDir = trailingslashit($uploadsDir['basedir']);
        if (!file_exists($uploadsDir . 'awesome-support')) {
            mkdir($uploadsDir . 'awesome-support');
        }
    }

    /************************
     * Exceptions
     ***********************/

    /**
     * Throw the ticket error.
     *
     * @since 0.1.0
     *
     * @param WP_Error $error
     * @param string $subject
     * @param string $description
     * @param string $source
     * @param string $callingMethodName
     *
     * @return void
     * @throws InserterException
     */
    protected function throwTicketError(WP_Error $error, $subject, $description, $source, $callingMethodName)
    {
        throw new InserterException(
            $error->get_error_message(),
            $error->get_error_code(),
            [
                'subject'     => $subject,
                'description' => $description,
                'source'      => $source,
                'method'      => $callingMethodName,
            ],
            __CLASS__
        );
    }

    /**
     * Whoops, something happened when processing the User. Throw an error.
     *
     * @since 0.1.0
     *
     * @param User $userEntity
     * @param WP_User|false $user
     * @param string $callingMethodName
     *
     * @return void
     * @throws InserterException
     */
    protected function throwUserError(User $userEntity, $user, $callingMethodName)
    {
        $isError = $user instanceof WP_Error;

        $message = $isError
            ? $user->get_error_message()
            : __(
                "User was not found and could not be inserted. Check the information to ensure it's complete.",
                'awesome-support-importer'
            );

        throw new InserterException(
            $message,
            $isError ? $user->get_error_code() : E_ERROR,
            [
                'email'       => $userEntity->getEmail(),
                'subject'     => $userEntity->getSubject(),
                'description' => $userEntity->getDescription(),
                'method'      => $callingMethodName,
            ],
            __CLASS__
        );
    }

    /**
     * An error was reported from WordPress while inserting the reply.
     * Package it up and throw a InserterException error.
     *
     * @since 0.1.0
     *
     * @param WP_Error $error
     * @param string $callingMethod
     *
     * @return void
     * @throws InserterException
     */
    protected function throwReplyError(WP_Error $error, $callingMethod)
    {
        throw new InserterException(
            $error->get_error_message(),
            $error->get_error_code(),
            [
                'subject'     => $error->getSubject(),
                'description' => $error->getDescription(),
                'method'      => $callingMethod,
            ],
            __CLASS__
        );
    }
}
