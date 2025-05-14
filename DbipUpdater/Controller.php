<?php

namespace Matomo\Plugins\DbipUpdater;

use Matomo\Plugins\CoreAdminHome\Controller as CoreAdminController;
use Matomo\Common;
use Matomo\View;
use Matomo\Log;
use Matomo\Notice\NoticePool;
use Piwik\Piwik;

/**
 * DbipUpdater Plugin Controller
 *
 * Handles the UI for the DbipUpdater plugin settings.
 *
 * @link https://franz.agency
 * @license GPL v3+
 * @author Franz und Franz
 * @copyright Franz und Franz
 */
class Controller extends CoreAdminController
{
    /**
     * Main index action - displays settings page
     *
     * @return string Generated HTML for the settings page
     */
    public function index(): string
    {
        // Überprüfe, ob der Benutzer Superuser-Zugriff hat
        Piwik::checkUserHasSuperUserAccess();
        
        // Create and configure the view
        $view = new View('@DbipUpdater/settings');
        $this->setBasicVariablesView($view);
        $view->title = 'DB-IP Updater';
        
        // Make plugin settings available to the view
        $view->settings = new Settings();
        
        // Füge einen Debug-Log hinzu, um zu verifizieren, dass die Settings geladen werden
        if (method_exists(Log::class, 'debug')) {
            Log::debug('DbipUpdater: Settings geladen für Controller: ' . get_class($view->settings));
        }
        
        // Add UserCountry side menu to link back to main settings
        if (class_exists('\\Matomo\\Plugins\\UserCountry\\UserCountry')) {
            $userCountryController = new \Matomo\Plugins\UserCountry\Controller();
            if (method_exists($userCountryController, 'renderAdmin')) {
                $view->userCountryAdminUrl = 'index.php?module=UserCountry&action=admin';
            }
        }
        
        return $view->render();
    }
    
    /**
     * Save plugin settings
     * 
     * This handles the form submission for the plugin's system settings
     */
    public function saveSettings(): void
    {
        Piwik::checkUserHasSuperUserAccess();
        
        if (method_exists(Log::class, 'debug')) {
            Log::debug('DbipUpdater: saveSettings() method called');
        }
        
        $settings = new Settings();
        
        // Get form values from request
        $jsonUrl = Common::getRequestVar('jsonUrl', '', 'string');
        $enableDetailedLogging = Common::getRequestVar('enableDetailedLogging', '0', 'string');
        $connectionTimeout = Common::getRequestVar('connectionTimeout', '30', 'int');
        $maxRetries = Common::getRequestVar('maxRetries', '3', 'int');
        
        // Update settings
        $settings->jsonUrl->setValue($jsonUrl);
        $settings->enableDetailedLogging->setValue($enableDetailedLogging == '1');
        $settings->connectionTimeout->setValue($connectionTimeout);
        $settings->maxRetries->setValue($maxRetries);
        
        // Save all settings
        $settings->save();
        
        // Add success notification if available
        if (class_exists('\Matomo\Notice\NoticePool')) {
            $noticePool = NoticePool::getInstance();
            $noticePool->addNotice(Common::translate('CoreAdminHome_SettingsSaveSuccess'));
        }
        
        // After saving, redirect back to the settings page
        $this->redirectToIndex('DbipUpdater', 'index');
    }
}
