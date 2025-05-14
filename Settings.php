<?php
namespace Matomo\Plugins\DbipUpdater;

use Matomo\Settings\TextSetting;
use Matomo\Settings\BoolSetting;
use Matomo\Settings\IntegerSetting;
use Matomo\Settings\PluginSettings;

/**
 * DbipUpdater Plugin Settings
 *
 * Defines the configurable settings for the DbipUpdater plugin.
 *
 * @author Franz & Franz
 * @copyright Franz & Franz
 */
class Settings extends PluginSettings
{
    /**
     * Initialize plugin settings
     */
    protected function init(): void
    {
        // Main JSON URL setting
        $this->addSetting(new TextSetting(
            'jsonUrl',               // setting key
            'Download JSON URL',     // setting title in UI
            'https://db-ip.com/account/changeme/db/ip-to-location/', // default value
            'The URL endpoint returning JSON with download links to your DB-IP files. ' .
            'Replace "changeme" with your DB-IP account ID.' // description in UI
        ));
        
        // Enable detailed logging
        $this->addSetting(new BoolSetting(
            'enableDetailedLogging',  // setting key
            'Enable Detailed Logging', // setting title in UI
            false,                    // default value (disabled)
            'When enabled, additional detailed information will be logged during updates. ' .
            'Useful for troubleshooting but may increase log size.' // description in UI
        ));
        
        // Connection timeout setting
        $this->addSetting(new IntegerSetting(
            'connectionTimeout',      // setting key
            'Connection Timeout',     // setting title in UI
            30,                       // default value (30 seconds)
            'Timeout in seconds when connecting to the JSON endpoint. ' .
            'Increase this value if you experience timeout issues.' // description in UI
        ));
        
        // Retry count on failure
        $this->addSetting(new IntegerSetting(
            'maxRetries',            // setting key
            'Maximum Retries',        // setting title in UI
            3,                        // default value (3 retries)
            'Number of attempts to retry download if a connection fails. ' .
            'Set to 0 to disable retry functionality.' // description in UI
        ));
    }
}