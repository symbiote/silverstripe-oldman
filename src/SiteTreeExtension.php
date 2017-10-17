<?php

namespace Symbiote\Cloudflare;

class SiteTreeExtension extends \DataExtension
{
    public function onAfterPublish()
    {
        \Injector::inst()->get('Symbiote\Cloudflare\Cloudflare')->purgePage($this->owner);
    }

    public function onAfterUnpublish()
    {
        \Injector::inst()->get('Symbiote\Cloudflare\Cloudflare')->purgePage($this->owner);
    }
}
