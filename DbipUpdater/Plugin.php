<?php

namespace Matomo\Plugins\DbipUpdater;

use Matomo\Plugin;
use Matomo\Settings\Manager as SettingsManager;
use Matomo\Plugin\ScheduledTask;
use Matomo\ConfigWriter;
use Matomo\Log;
use Matomo\Version;
use Exception;

/**
 * DbipUpdater Plugin
 *
 * This plugin automatically updates the DB-IP MMDB URL for GeoIP2 integration.
 * It fetches download links from a configurable JSON endpoint and updates the
 * Matomo configuration on a monthly schedule.
 *
 * @link https://franzundfranzisweb.com
 * @license GPL v3+
 * @author Franz und Franz
 * @copyright Franz und Franz
 */
class DbipUpdater extends Plugin
{
    public const PLUGIN_VERSION = '1.4.0';

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
     * Get scheduled tasks triggered by this plugin
     *
     * @return array List of scheduled tasks
     */
    public function getScheduledTasks(): array
    {
        // Execute task monthly, two days after the start of the month
        $task = new ScheduledTask(
            'Matomo\\Plugins\\DbipUpdater\\Task\\UpdateMmdbUrl',
            ScheduledTask::MONTHLY
        );
        // Set to run on the 2nd day of each month
        $task->setParameter(['dayOfMonth' => 2]);

        return [$task];
    }

    /**
     * Called on plugin installation
     *
     * @throws Exception If required Matomo version is not met
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
            $configWriter = ConfigWriter::getInstance();
            if (!$configWriter->getConfigValue('GeoIP2', 'dbipMmdbUrl')) {
                $configWriter->updateConfig('GeoIP2', 'dbipMmdbUrl', '');
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
}
