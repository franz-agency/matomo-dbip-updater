<?php
/**
 * DB-IP Updater API
 *
 * @link https://franz.agency
 * @license GPL v3+
 * @author Franz und Franz
 * @copyright Franz und Franz
 */

namespace Matomo\Plugins\DbipUpdater;

use Matomo\Piwik;
use Exception;

/**
 * API for DB-IP Updater plugin
 */
class API extends \Matomo\Plugin\API
{
    /**
     * Get current plugin settings
     *
     * @return array Settings values
     */
    public function getSettings()
    {
        Piwik::checkUserHasSuperUserAccess();
        
        try {
            $settings = new Settings();
            return [
                'jsonUrl' => $settings->jsonUrl->getValue(),
                'enableDetailedLogging' => $settings->enableDetailedLogging->getValue(),
                'connectionTimeout' => $settings->connectionTimeout->getValue(),
                'maxRetries' => $settings->maxRetries->getValue(),
                'status' => 'success'
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Save plugin settings
     *
     * @param string $jsonUrl JSON API URL
     * @param bool $enableDetailedLogging Enable detailed logging
     * @param int $connectionTimeout Connection timeout in seconds
     * @param int $maxRetries Maximum number of retries
     * @return array Status of the operation
     */
    public function saveSettings($jsonUrl, $enableDetailedLogging = false, $connectionTimeout = 30, $maxRetries = 3)
    {
        Piwik::checkUserHasSuperUserAccess();
        
        try {
            $settings = new Settings();
            $settings->jsonUrl->setValue($jsonUrl);
            $settings->enableDetailedLogging->setValue($enableDetailedLogging);
            $settings->connectionTimeout->setValue($connectionTimeout);
            $settings->maxRetries->setValue($maxRetries);
            $settings->save();
            
            return [
                'status' => 'success',
                'message' => 'Settings saved successfully'
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}
