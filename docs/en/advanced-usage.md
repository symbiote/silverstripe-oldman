# Advanced Usage

## Key Differences with [Steadlane Module](https://github.com/steadlane/silverstripe-cloudflare)

* Zone ID isn't automatically inferred and stored in cache, instead it's configured in YML.
* Purging actions are /dev/tasks, rather than exposed in the ModelAdmin.
* Default behaviour is to *never* purge the entire cache on SiteTree publish, even if a Title, MenuTitle or URLSegment was changed.

## Dev/Tasks

**Cloudflare Purge: All**
- CLI Command: `php framework/cli-script.php dev/tasks/Symbiote-Cloudflare-PurgeAllTask`
- Purge everything from the Cloudflare cache. NOTE: This is not recommended on high-traffic websites.

**Cloudflare Purge: CSS and JavaScript**
- CLI Command: `php framework/cli-script.php dev/tasks/Symbiote-Cloudflare-PurgeCSSAndJavascriptTask`
- Gets all CSS and JavaScript files in the Silverstripe project folder recursively and File records stored in the database, then tells Cloudflare they need purging.

**Cloudflare Purge: Images**
- CLI Command: `php framework/cli-script.php dev/tasks/Symbiote-Cloudflare-PurgeImagesTask`
- Gets all image files in the Silverstripe project folder recursively and File records stored in the database, then tells Cloudflare they need purging. The file extensions are taken from the File::app_categories['image'] config and Cloudflare::image_file_extensions config respectively.

**Cloudflare Purge: URL**
- CLI Command: `php framework/cli-script.php dev/tasks/Symbiote-Cloudflare-PurgeURLTask "purge_url=admin/,Security/,https://myproduction.com/admin"`
- Clears a comma-delimited list of URLs. Can either be relative or absolute URLs.

## Configuration

ie.
```yml
Symbiote\Cloudflare\Cloudflare:
  enabled: true
  email: 'silverstripe@gmail.com'
  auth_key: '24ca61e15fb2aa62a31212a90f2674f3451f8'
  zone_id: '73a40b2c0c10f468cb658f67b9d46fff'
  # Optional, specify a URL to use instead of Director::baseURL()
  base_url: 'https://www.production-website.com/'
  # Add additional image file types to purge that aren't included in File::app_categories['image']
  image_file_extensions:
    - 'svg'
    - 'webp'
```
