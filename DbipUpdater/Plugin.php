<?php

namespace Matomo\Plugins\DbipUpdater;

// DbipUpdater Plugin

use Exception;
use Matomo\Config as MatomoConfig;
use Matomo\Log;
use Matomo\Menu\MenuAdmin;
use Matomo\Matomo;
use Matomo\Plugin;
use Matomo\Plugin\Manager as PluginManager;
use Matomo\Plugins\CoreAdminHome\Controller as CoreAdminHomeController;
use Matomo\Scheduler\Schedule\Monthly;
use Matomo\Settings\Setting;
use Matomo\Settings\FieldConfig;
use Matomo\Settings\Manager as SettingsManager;
use Matomo\Settings\Matomo;
use Matomo\Url;
use Matomo\Version;
use Matomo\View;

/**
 * DbipUpdater Plugin
 *
 * This plugin automatically updates the DB-IP MMDB URL for GeoIP2 integration.
 * It fetches download links from a configurable JSON endpoint and updates the
 * Matomo configuration on a monthly schedule.
 *
 * @link https://franz.agency
 * @license GPL v3+
 * @author Franz und Franz
 * @copyright Franz und Franz
 */
class DbipUpdater extends Plugin
{
    public const PLUGIN_VERSION = '1.4.0';
    
    /**
     * Register events with Matomo system
     */
    public function registerEvents(): array
    {
        // Debug log to verify plugin load
        Log::debug('DbipUpdater: registerEvents() called');
        return [
            'AssetManager.getJavaScriptFiles' => 'getJsFiles',
            'AssetManager.getStylesheetFiles' => 'getCssFiles',
            'Template.userCountryAdmin.afterFindVisitorSection' => 'renderDbipAdminSection', // UserCountry integration
            'SettingsManager.registerSettings' => 'registerSettings', // Register settings
            'Template.adminHome.afterContent' => 'showDirectAccessLinks', // Show links on admin home page
            'Template.genericForm.afterForm' => 'showDirectAccessLinks', // Show links after forms
            'Template.dashboardSettings.afterItems' => 'showDirectAccessLinks', // Show links in dashboard settings
            'Template.systemSettings.afterSystemSettingsView' => 'showDirectAccessLinks', // Show in system settings
            'Translate.getClientSideTranslationKeys' => 'getClientSideTranslationKeys', // Translations
            'Menu.Admin.addItems' => 'configureAdminMenu', // Register admin menu items
        ];
    }
    
    /**
     * Add JavaScript files for this plugin
     *
     * @param array &$jsFiles JavaScript files array
     */
    public function getJsFiles(&$jsFiles): void
    {
        $jsFiles[] = 'plugins/DbipUpdater/javascripts/dbipupdater.js';
    }
    
    /**
     * Add CSS files for this plugin
     *
     * @param array &$cssFiles CSS files array
     */
    public function getCssFiles(&$cssFiles): void
    {
        $cssFiles[] = 'plugins/DbipUpdater/stylesheets/dbipupdater.css';
    }
    
    /**
     * Render DB-IP Updater settings section in UserCountry admin page
     * 
     * @param string &$out Output string to append to
     */
    public function renderDbipAdminSection(&$out): void
    {
        // Füge einen auffälligen Debug-Block hinzu
        $debugBlock = <<<HTML
<div style="background-color: #ffeb3b; color: #000; padding: 15px; margin: 20px 0; border-radius: 4px; border: 2px solid #f00;">
    <h3 style="margin-top: 0;">DB-IP Updater Debug-Info</h3>
    <p><strong>Diese Einstellungen sind erreichbar.</strong> Du siehst gerade den Debug-Block der DbipUpdater-Einstellungen!</p>
    <p>Klasse geladen: Settings</p>
    <p>URL: index.php?module=DbipUpdater&action=index</p>
    <a href="index.php?module=DbipUpdater&action=index" class="btn btn-flat" style="background: #2c3e50; color: white; padding: 8px 16px; text-decoration: none; border-radius: 3px; display: inline-block; margin-top: 10px;">Zur vollständigen Einstellungsseite</a>
</div>
HTML;

        $out .= $debugBlock;
        
        // Lade original View
        $view = new View('@DbipUpdater/dbip-admin-section');
        $view->settings = new Settings();
        $out .= $view->render();
    }
    
    /**
     * Render our settings section on the UserCountry admin page
     * 
     * @param string $out The output string to append to
     */
    public function renderAdminSettingsSection(&$out): void
    {
        $view = new View('@DbipUpdater/admin-settings');
        $view->settings = new Settings();
        $out .= $view->render();
    }

    /**
     * Register plugin settings container
     *
     * @param SettingsManager $settingsManager The settings manager
     */
    public function registerSettings(SettingsManager $settingsManager): void
    {
        $settingsManager->registerSettingContainer(Settings::class);
    }

    /**
     * Get translation keys that need to be available in JavaScript
     *
     * @return array Array of translation keys
     */
    public function getClientSideTranslationKeys(&$translationKeys)
    {
        $translationKeys[] = 'DbipUpdater_Settings';
        $translationKeys[] = 'DbipUpdater_SettingsDescription';
    }
    
    /**
     * Show direct access links to the plugin settings
     * This will be displayed in various locations in the Piwik admin UI
     * 
     * @param string &$out The output HTML content
     */
    public function showDirectAccessLinks(&$out): void
    {
        // Nur für Superuser anzeigen
        if (!\Matomo\Matomo::hasUserSuperUserAccess()) {
            return;
        }
        
        // Render das Template für die direkten Links
        $view = new View('@DbipUpdater/direct-access');
        $out .= $view->render();
    }

    /**
     * Get scheduled tasks triggered by this plugin
     *
     * @return array List of scheduled tasks
     */
    public function getScheduledTasks(): array
    {
        // Execute task monthly, two days after the start of the month
        $schedule = new Monthly();
        $schedule->setDayOfMonth(2);
        
        return [
            new \Matomo\Scheduler\Task(
                $this, 
                'UpdateMmdbUrl',
                null, 
                $schedule
            )
        ];
    }

    /**
     * Called on plugin installation
     *
     * @throws Exception If required Piwik version is not met
     */
    public function install(): void
    {
        // Check required Matomo version
        $requiredMatomoVersion = '5.0.0';
        if (version_compare(Version::VERSION, $requiredMatomoVersion, '<')) {
            throw new Exception(
                "The DbipUpdater plugin requires Matomo $requiredMatomoVersion or later. " .
                "You are using Matomo " . Version::VERSION
            );
        }

        // Make sure the GeoIP2 section exists in config
        try {
            $config = PiwikConfig::getInstance();
            $geoIpConfig = $config->GeoIP2;
            
            if (empty($geoIpConfig['dbipMmdbUrl'])) {
                $geoIpConfig['dbipMmdbUrl'] = '';
                $config->GeoIP2 = $geoIpConfig;
                $config->forceSave();
                Log::info("DbipUpdater: Plugin installed successfully, config initialized");
            }
        } catch (Exception $e) {
            Log::error("DbipUpdater: Error during plugin installation: {$e->getMessage()}");
            throw new Exception("Failed to initialize plugin configuration: {$e->getMessage()}");
        }

        parent::install();
    }

    /**
     * Called on plugin uninstallation
     */
    public function uninstall(): void
    {
        try {
            // We don't actually remove the config value as it might be in use
            Log::info("DbipUpdater: Plugin uninstalled successfully, config preserved");
        } catch (Exception $e) {
            Log::error("DbipUpdater: Error during plugin uninstallation: {$e->getMessage()}");
        }

        parent::uninstall();
    }

    /**
     * Plugin activation - logs the event
     */
    public function activate(): void
    {
        try {
            // Log activation
            Log::info("DbipUpdater: Plugin activated");
        } catch (Exception $e) {
            // Don't throw here as it would prevent activation
            Log::error("DbipUpdater: Error during plugin activation: {$e->getMessage()}");
        }

        parent::activate();
    }

    /**
     * Plugin deactivation - logs the event
     */
    public function deactivate(): void
    {
        try {
            // Log deactivation
            Log::info("DbipUpdater: Plugin deactivated");
        } catch (Exception $e) {
            // Don't throw here as it would prevent deactivation
            Log::error("DbipUpdater: Error during plugin deactivation: {$e->getMessage()}");
        }

        parent::deactivate();
    }

    /**
     * Register plugin admin menu items
     *
     * @param MenuAdmin $menu
     */
    public function configureAdminMenu(\Matomo\Menu\MenuAdmin $menu): void
{
    if (\Matomo\Matomo::hasUserSuperUserAccess()) {
        $menu->addItem(
            'General_Settings',
            'DB-IP Updater',
            ['module' => 'DbipUpdater', 'action' => 'index'],
            true,
            30
        );
    }
}
}
