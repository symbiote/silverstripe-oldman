# Quick Start

1. Install via composer.

2. Configure in YML, example below:
```yml
Symbiote\Cloudflare\Cloudflare:
  enabled: true
  api_token: '24ca61e15fb2aa62a31-212a90f2674f_3451f8' # Needs the the "Cache Purge" permission:
  zone_id: '73a40b2c0c10f468cb658f67b9d46fff'
```

Alternatively you can use you Global API Key:
```yml
Symbiote\Cloudflare\Cloudflare:
  enabled: true
  email: 'silverstripe@gmail.com'
  auth_key: '24ca61e15fb2aa62a31212a90f2674f3451f8'
  zone_id: '73a40b2c0c10f468cb658f67b9d46fff'
```

Note the `email`, `auth_key`, `zone_id`, and `api_token` yaml options can also be represented with service properties so you can store this information in the environment file. For example:

```yml
Symbiote\Cloudflare\Cloudflare:
  enabled: true
  api_token: "`CLOUDFLARE_API_TOKEN`"
  zone_id: "`CLOUDFLARE_ZONE_ID`"
```


3. Publishing / unpublishing a page from the CMS will now clear the Cloudflare cache for that URL.

4. For clearing CSS, JavaScript and images from the Cloudflare cache, see the [Advanced Usage](advanced-usage.md) section.

## Debugging

From SilverStripe 4.0 and onwards, Oldman module will print out the URLs purged when you publish a page in a custom header. This will allow anybody to confirm whether CloudFlare is working as expected by using the "Network" tab in Chrome or Firefox, and inspecting the headers.

These values are sent as-is to CloudFlare, so if the urls in the header are incorrect, chances are something is incorrect in this module or your user-code.
If you aren't seeing these headers when you save and publish a page, then please ensure this module is enabled and configured properly.

ie:
```
oldman-cloudflare-cleared-links: https://mycoolwebsite.com/about-us,https://mycoolwebsite.com/about-us/
```
