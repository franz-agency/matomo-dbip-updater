<?php

namespace Matomo\Plugins\DbipUpdater;

use Piwik\Settings\FieldConfig;
use Piwik\Settings\Plugin\SystemSettings;
use Piwik\Settings\Plugin\SystemSetting;

/**
 * DbipUpdater Plugin Settings
 *
 * Defines the configurable settings for the DbipUpdater plugin.
 *
 * @author Franz und Franz
 * @copyright Franz und Franz
 */
class Settings extends SystemSettings
{
    /** @var SystemSetting */
    public $jsonUrl;
    
    /** @var SystemSetting */
    public $enableDetailedLogging;
    
    /** @var SystemSetting */
    public $connectionTimeout;
    
    /** @var SystemSetting */
    public $maxRetries;
    
    /**
     * Initialize plugin settings
     */
    protected function init()
    {
        // Main JSON URL setting
        $this->jsonUrl = $this->makeSetting(
            'jsonUrl', 
            'https://db-ip.com/account/changeme/db/ip-to-location/', 
            FieldConfig::TYPE_STRING,
            function (FieldConfig $field) {
                $field->title = 'Download JSON URL';
                $field->description = 'The URL endpoint returning JSON with download links to your DB-IP files. Replace "changeme" with your DB-IP account ID.';
                $field->uiControl = FieldConfig::UI_CONTROL_URL;
                $field->validate = function ($value) {
                    if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                        throw new \Exception('Please enter a valid URL');
                    }
                };
            }
        );

        // Enable detailed logging
        $this->enableDetailedLogging = $this->makeSetting(
            'enableDetailedLogging', 
            false, 
            FieldConfig::TYPE_BOOL,
            function (FieldConfig $field) {
                $field->title = 'Enable Detailed Logging';
                $field->description = 'When enabled, additional detailed information will be logged during updates. Useful for troubleshooting but may increase log size.';
                $field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
            }
        );

        // Connection timeout setting
        $this->connectionTimeout = $this->makeSetting(
            'connectionTimeout', 
            30, 
            FieldConfig::TYPE_INT,
            function (FieldConfig $field) {
                $field->title = 'Connection Timeout';
                $field->description = 'Timeout in seconds when connecting to the JSON endpoint. Increase this value if you experience timeout issues.';
                $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            }
        );

        // Retry count on failure
        $this->maxRetries = $this->makeSetting(
            'maxRetries', 
            3, 
            FieldConfig::TYPE_INT,
            function (FieldConfig $field) {
                $field->title = 'Maximum Retries';
                $field->description = 'Number of attempts to retry download if a connection fails. Set to 0 to disable retry functionality.';
                $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            }
        );

    }
}
