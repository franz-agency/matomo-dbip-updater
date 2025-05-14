<?php
namespace Matomo\Plugins\DbipUpdater\Task;

use Matomo\Plugin\ScheduledTask;
use Matomo\ConfigWriter;
use Matomo\Container;
use Matomo\Settings\Manager as SettingsManager;
use Matomo\Log;
use Matomo\Date;
use Matomo\Plugins\DbipUpdater\Settings;
use Exception;

/**
 * Task to automatically update the DB-IP MMDB URL from a JSON source
 *
 * This scheduled task connects to a configurable JSON endpoint and extracts
 * the latest mmdb URL, which is then stored in Matomo's configuration.
 *
 * Features:
 * - Configurable connection timeout
 * - Retry mechanism for failed connections
 * - Detailed logging for troubleshooting
 * - Robust error handling
 *
 * @author Franz & Franz
 * @copyright Franz & Franz
 */
class UpdateMmdbUrl extends ScheduledTask
{
    public function getName(): string
    {
        return 'DbipUpdater.UpdateMmdbUrlTask';
    }

    /**
     * @var int Number of retry attempts already made
     */
    private $retryCount = 0;
    
    /**
     * @var Settings Plugin settings instance
     */
    private $settings;
    
    /**
     * @var bool Whether detailed logging is enabled
     */
    private $detailedLogging = false;
    
    /**
     * Execute the scheduled task
     *
     * @throws Exception If the task fails despite retry attempts
     */
    public function run(): void
    {
        // Start time for performance logging
        $startTime = microtime(true);
        
        // Retrieve all settings from plugin
        $this->loadSettings();
        
        // Log task start if detailed logging is enabled
        $this->logDetailed("Starting DB-IP URL update task at " . Date::now()->getDatetime());
        
        try {
            // Get the mmdb URL from JSON endpoint
            $mmdbUrl = $this->fetchAndExtractMmdbUrl();
            
            // Update Matomo configuration with new URL
            $this->updateMatomoConfig($mmdbUrl);
            
            // Calculate execution time for logging
            $executionTime = round(microtime(true) - $startTime, 2);
            $this->logDetailed("Task completed successfully in {$executionTime} seconds");
            
            // Log success message
            Log::info("DbipUpdater: Successfully updated DB-IP MMDB URL to {$mmdbUrl}");
        } catch (Exception $e) {
            // Check if retry is possible
            if ($this->retryCount < $this->settings->maxRetries) {
                $this->retryCount++;
                $waitSeconds = $this->retryCount * 5; // Progressive backoff
                
                $this->logDetailed("Attempt {$this->retryCount} failed: {$e->getMessage()}");
                $this->logDetailed("Waiting {$waitSeconds} seconds before retry...");
                
                // Wait before retry
                sleep($waitSeconds);
                
                // Try again recursively
                $this->run();
                return;
            }
            
            // All retries exhausted, log final error
            Log::error("DbipUpdater: All retry attempts failed. Last error: {$e->getMessage()}");
            throw new Exception("Failed to update DB-IP URL after {$this->retryCount} attempts: {$e->getMessage()}");
        }
    }
    
    /**
     * Load all settings from the plugin configuration
     */
    private function loadSettings(): void
    {
        /** @var SettingsManager $settingsManager */
        $settingsManager = Container::get(SettingsManager::class);
        $this->settings = $settingsManager
            ->getContainer(SettingsManager::PLUGIN_SCOPE)
            ->get('Matomo\Plugins\DbipUpdater\Settings');
        
        // Set detailed logging flag based on settings
        $this->detailedLogging = $this->settings->enableDetailedLogging;
        
        $this->logDetailed("Loaded settings: JSON URL={$this->settings->jsonUrl}, " . 
                           "Timeout={$this->settings->connectionTimeout}, " . 
                           "MaxRetries={$this->settings->maxRetries}");
    }
    
    /**
     * Fetch JSON from configured endpoint and extract the mmdb URL
     * 
     * @return string The extracted mmdb URL
     * @throws Exception If fetching or parsing fails
     */
    private function fetchAndExtractMmdbUrl(): string
    {
        $jsonUrl = $this->settings->jsonUrl;
        $timeout = (int)$this->settings->connectionTimeout;
        
        // Create context with timeout from settings
        $context = stream_context_create([
            'http' => [
                'timeout' => $timeout,
                'user_agent' => 'Matomo DbipUpdater Plugin/1.3.0',
                'ignore_errors' => true,
            ]
        ]);
        
        $this->logDetailed("Fetching JSON from: {$jsonUrl}");
        
        // Fetch the JSON data
        $json = @file_get_contents($jsonUrl, false, $context);
        
        // Check for HTTP response headers to provide better error messages
        if (isset($http_response_header)) {
            $statusLine = $http_response_header[0];
            preg_match('{HTTP/\S*\s(\d{3})}', $statusLine, $match);
            $statusCode = $match[1] ?? null;
            
            if ($statusCode && $statusCode != '200') {
                $errorMsg = "HTTP Error: {$statusLine}";
                $this->logDetailed($errorMsg);
                
                if ($statusCode == '403' || $statusCode == '401') {
                    Log::error("DbipUpdater: Authentication failure. Check your DB-IP account credentials.");
                    throw new Exception("Authentication failed: {$errorMsg}");
                }
                
                Log::error("DbipUpdater: HTTP error: {$errorMsg}");
                throw new Exception("HTTP error when accessing {$jsonUrl}: {$errorMsg}");
            }
        }
        
        if ($json === false) {
            $error = error_get_last();
            $errorMsg = $error ? $error['message'] : 'Unknown error';
            Log::error("DbipUpdater: Failed to fetch JSON: {$errorMsg}");
            throw new Exception("Could not retrieve JSON from {$jsonUrl}: {$errorMsg}");
        }
        
        // Decode and extract the mmdb URL
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errorMsg = json_last_error_msg();
            Log::error("DbipUpdater: JSON decode error: {$errorMsg}");
            throw new Exception("JSON decode error: {$errorMsg}");
        }
        
        $this->logDetailed("Successfully decoded JSON response");
        
        // Validate expected data structure
        if (empty($data['mmdb']['url'])) {
            // Check if we received a valid JSON structure but with a different schema
            if (!empty($data)) {
                $receivedStructure = json_encode(array_keys($data));
                $this->logDetailed("Received unexpected JSON structure: {$receivedStructure}");
            }
            
            Log::error("DbipUpdater: Missing 'mmdb.url' in JSON response");
            throw new Exception("Missing 'mmdb.url' key in JSON response");
        }
        
        $mmdbUrl = $data['mmdb']['url'];
        $this->logDetailed("Extracted MMDB URL: {$mmdbUrl}");
        
        // Validate URL format
        if (!filter_var($mmdbUrl, FILTER_VALIDATE_URL)) {
            Log::error("DbipUpdater: Invalid URL format: {$mmdbUrl}");
            throw new Exception("Invalid URL format in JSON response: {$mmdbUrl}");
        }
        
        return $mmdbUrl;
    }
    
    /**
     * Update Matomo's GeoIP2 configuration with the new URL
     * 
     * @param string $mmdbUrl The new mmdb URL to store
     * @throws Exception If the configuration update fails
     */
    private function updateMatomoConfig(string $mmdbUrl): void
    {
        try {
            // Check if URL has changed from previous value
            $configWriter = ConfigWriter::getInstance();
            $currentUrl = $configWriter->getConfigValue('GeoIP2', 'dbipMmdbUrl');
            
            if ($currentUrl !== null && $currentUrl === $mmdbUrl) {
                $this->logDetailed("DB-IP URL unchanged, no update needed");
                return;
            }
            
            // Update the configuration
            $configWriter->updateConfig('GeoIP2', 'dbipMmdbUrl', $mmdbUrl);
            $this->logDetailed("Successfully updated configuration value");
        } catch (Exception $e) {
            Log::error("DbipUpdater: Failed to update config: {$e->getMessage()}");
            throw new Exception("Failed to update Matomo configuration: {$e->getMessage()}");
        }
    }
    
    /**
     * Log detailed information if enabled in settings
     * 
     * @param string $message The message to log
     */
    private function logDetailed(string $message): void
    {
        if ($this->detailedLogging) {
            Log::debug("DbipUpdater: {$message}");
        }
    }
}