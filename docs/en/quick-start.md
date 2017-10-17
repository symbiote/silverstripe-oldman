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

3. Publishing / unpublishing a page will now clear the Cloudflare cache for that URL.

4. For clearing CSS, JavaScript and images from the Cloudflare cache, see the [Advanced Usage](advanced-usage.md) section.
