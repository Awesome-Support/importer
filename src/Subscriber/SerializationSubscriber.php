<?php

namespace Pressware\AwesomeSupport\Subscriber;

class SerializationSubscriber extends AbstractSubscriber
{
    /**
     * {@inheritdoc}
     */
    public static function getHooks()
    {
        return [
            'load-ticket_page_awesome_support_import_tickets' => 'saveImportOptions',
        ];
    }

    public function saveImportOptions()
    {
        if (!$this->userCanSave('awesome-support-importer-save', 'awesome-support-importer-save-nonce') ||
            'ticket_page_awesome_support_import_tickets' !== get_current_screen()->id) {
            return;
        }

        $help_desk = strip_tags(stripslashes($_POST[$this->optionsPrefix . 'help-desk']));
        if ('default' === $help_desk) {
            foreach ($this->optionsConfig as $key => $value) {
                delete_option($key);
            }
            return;
        }

        $options = [];
        foreach ($this->optionsConfig as $key => $value) {
            $value = strip_tags(stripslashes($_POST[$key]));
            $options[$key] = $value;
            update_option($key, $value);
        }

        $this->processDates(
            $options[$this->optionsPrefix . 'date-start'],
            $options[$this->optionsPrefix . 'date-end']
        );
    }

    private function processDates($start, $end)
    {
        $isInvalidRange = empty($end) ? false : (strtotime($end) < strtotime($start));
        update_option(
            $this->optionsPrefix . 'invalid-date-range',
            $isInvalidRange
        );
    }

    private function userCanSave($action, $nonce)
    {
        if (!isset($_POST[$nonce])) {
            return false;
        }

        $is_nonce_set = isset($_POST[$nonce]);
        $is_valid_nonce = $is_nonce_set ? wp_verify_nonce($_POST[$nonce], $action) : false;
        return ($is_nonce_set && $is_valid_nonce);
    }
}
