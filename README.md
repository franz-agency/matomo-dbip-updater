# DbipUpdater - Matomo Plugin

## Description
DbipUpdater is a Matomo plugin that automatically updates the DB-IP MMDB URL for GeoIP2 integration. It fetches download links from a configurable JSON endpoint and updates the Matomo configuration on a monthly schedule.

## Features
- Automatically updates DB-IP MMDB URLs in Matomo's GeoIP2 configuration
- Monthly scheduled task (runs on the 2nd day of each month)
- Configurable JSON endpoint
- Detailed error logging
- Robust error handling

## Requirements
- Matomo 5.0.0 or newer
- PHP 7.4 or newer
- A valid DB-IP account with access to the API

## Installation
1. Download the latest version of the plugin from the Matomo Marketplace
2. Login as a superuser to your Matomo installation
3. Go to Administration > Plugins > Manage Plugins
4. Upload the plugin zip file
5. Activate the plugin

## Configuration
1. Go to Administration > System > Geolocation. The DB-IP Updater settings will be displayed in a section on this page if the UserCountry plugin is active.
2. Alternatively, go to Administration > Plugins > DbipUpdater for a direct settings page.
3. Enter the URL to your DB-IP JSON endpoint
   - Default format: `https://db-ip.com/account/YOUR_ACCOUNT_ID/db/ip-to-location/`
   - Make sure to replace `YOUR_ACCOUNT_ID` with your actual DB-IP account ID

## JSON Response Format
The plugin expects the JSON endpoint to return a response in the following format:
```json
{
  "mmdb": {
    "url": "https://download.db-ip.com/key/your-download-key.mmdb"
  }
}
```

## Troubleshooting
If you encounter issues:
1. Check the Matomo error logs (Administration > Diagnostics > Log viewer).
2. Verify your JSON endpoint is accessible and returns the correct format.
3. Ensure your DB-IP account has valid permissions.
4. Enable "Detailed Logging" in the plugin settings for more verbose logs from the update task.

## Support
For support, please:
- Submit issues on [GitHub](https://github.com/franz-agency/matomo-dbip-updater/issues)
- Visit our [Website](https://franz.agency)

## License
GPL v3 or later

## Author
Franz & Franz
