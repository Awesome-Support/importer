<?php

namespace Pressware\AwesomeSupport\Subscriber;

use Pressware\AwesomeSupport\PluginAPI\AbstractAssetSubscriber;
use Pressware\AwesomeSupport\PluginAPI\HookSubscriberInterface;

class ScriptAssetSubscriber extends AbstractAssetSubscriber implements HookSubscriberInterface
{
    /**
     * @var array
     */
    protected $scriptLocalVars;

    /**
     * @var array
     */
    protected $scripts;

    /**
     * {@inheritdoc}
     */
    public static function getHooks()
    {
        return [
            'admin_enqueue_scripts' => 'enqueue',
        ];
    }

    public function enqueue()
    {
        if (!$this->isCurrentAdminPage()) {
            return;
        }

        foreach ($this->scripts as $handle => $config) {
            wp_enqueue_script(
                $handle,
                $this->pluginUrl . $config['file'],
                $config['dependencies'],
                $config['version'],
                $config['inFooter']
            );
        }

        $this->localizeScript();
    }


    /**
     * When enabled, localize the script variables.
     *
     * @since 0.1.0
     */
    protected function localizeScript()
    {
        if (!property_exists($this, 'scriptLocalVars')) {
            return;
        }

        foreach ((array)$this->scriptLocalVars as $handle => $config) {
            wp_localize_script($handle, $config['objectName'], $config['data']);
        }
    }
}
