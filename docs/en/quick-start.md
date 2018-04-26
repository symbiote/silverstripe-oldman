# Quick Start

1. Install via composer.

2. Configure in YML, example below:
```yml
Symbiote\Cloudflare\Cloudflare:
  enabled: true
  email: 'silverstripe@gmail.com'
  auth_key: '24ca61e15fb2aa62a31212a90f2674f3451f8'
  zone_id: '73a40b2c0c10f468cb658f67b9d46fff'
```

3. Publishing / unpublishing a page from the CMS will now clear the Cloudflare cache for that URL.

4. For clearing CSS, JavaScript and images from the Cloudflare cache, see the [Advanced Usage](advanced-usage.md) section.

## Debugging

From SilverStripe 4.0 and onwards, Oldman module will print out the URLs purged when you publish a page in a custom header. This will allow anybody to confirm whether CloudFlare is working as expected by using the "Network" tab in Chrome or Firefox, and inspecting the headers.

These values are sent as-is to CloudFlare, so if the urls in the header are incorrect, chances are something is incorrect in this module or your user-code.

ie:
```
oldman-cloudflare-cleared-links: https://mycoolwebsite.com/about-us,https://mycoolwebsite.com/about-us/
```
