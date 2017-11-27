<?php

namespace Pressware\AwesomeSupport\Subscriber;

use Pressware\AwesomeSupport\PluginAPI\AbstractAssetSubscriber;
use Pressware\AwesomeSupport\PluginAPI\HookSubscriberInterface;

class StyleAssetSubscriber extends AbstractAssetSubscriber implements HookSubscriberInterface
{
    /**
     * @var array
     */
    protected $styles = [];

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

        foreach ($this->styles as $handle => $config) {
            $src = '';
            if ($config['usePluginUrl']) {
                $src = $this->pluginUrl;
            }
            $src .= $config['src'];

            wp_enqueue_style(
                $handle,
                $src,
                $config['dependencies'],
                $config['version'],
                $config['media']
            );
        }
    }
}
