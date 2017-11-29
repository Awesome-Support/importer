<?php

namespace Pressware\AwesomeSupport\Importer;

class EmailSubscriber
{
    /**
     * Disable the email subscribers to avoid emailing during the importing process.
     *
     * @since 0.1.2
     *
     * @return void
     */
    public function disable()
    {
        /**
         * Fires the event, alerting all subscribers that tickets are importing
         * and to disable all email notifications.
         *
         * @since 0.1.2
         */
        do_action('wpas_disable_emails_during_importer');

        $this->unregisterEmails();
    }

    /**
     * Unregister Awesome Support email notifiers.
     *
     * @see Action events are found in `awesome-support/includes/functions-email-notifications.php`
     *
     * @since 0.2.1
     *
     * @return void
     */
    protected function unregisterEmails()
    {
        $config = [
            ['wpas_open_ticket_after', 'wpas_notify_confirmation', 11],
            ['wpas_open_ticket_after', 'wpas_notify_assignment', 12],
            ['wpas_ticket_after_update_admin_success', 'wpas_notify_admin_assignment', 12],
            ['wpas_post_new_ticket_admin', 'wpas_notify_admin_new_ticket', 12],
            ['wpas_insert_reply_admin_success', 'wpas_notify_admin_reply', 10],
            ['wpas_ticket_closed_by_agent', 'wpas_notify_ticket_closed_by_agent', 12],
            ['wpas_add_reply_complete', 'wpas_notify_reply', 10],
            ['wpas_after_close_ticket', 'wpas_notify_close', 10],
            ['wpas_custom_field_updated', 'wpas_additional_agents_new_assignment_notify', 10],
        ];
		
		/* @TODO: Unregister other actions from rules engine - just in case user ignored our warnings its activated */		
		/* @TODO: Unregister other actions from notifications add-on - just in case user ignored our warnings its activated */
		
        foreach ($config as $eventConfig) {
            remove_action($eventConfig[0], $eventConfig[1], $eventConfig[2]);
        }
    }
}
