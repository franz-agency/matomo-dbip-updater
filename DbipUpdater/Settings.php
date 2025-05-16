<?php

namespace Matomo\Plugins\DbipUpdater;
// Corrected use statements for Piwik namespace to Matomo
use Matomo\Settings\FieldConfig;
use Matomo\Settings\Plugin\SystemSettings;
use Matomo\Settings\Plugin\SystemSetting; // This class is often used, but individual settings are usually just properties.
                                         // The base SystemSettings class handles their creation via makeSetting.

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
    /** @var \Matomo\Settings\Setting */ // More specific type hint
    public $jsonUrl;
    
    /** @var \Matomo\Settings\Setting */
    public $enableDetailedLogging;
    
    /** @var \Matomo\Settings\Setting */
    public $connectionTimeout;
    
    /** @var \Matomo\Settings\Setting */
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
                $field->uiControl = FieldConfig::UI_CONTROL_TEXT; // Changed from UI_CONTROL_URL as it's a general text field for a URL
                $field->validate = function ($value, $setting) { // Added $setting parameter
                    if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                        throw new \Exception('Please enter a valid URL for '. $setting->title);
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
                $field->validate = function ($value, $setting) { // Added $setting parameter
                    if (!is_numeric($value) || (int)$value < 0) {
                        throw new \Exception($setting->title . ' must be a positive integer.');
                    }
                };
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
                $field->validate = function ($value, $setting) { // Added $setting parameter
                    if (!is_numeric($value) || (int)$value < 0) {
                        throw new \Exception($setting->title . ' must be a positive integer.');
                    }
                };
            }
        );

    }
}
