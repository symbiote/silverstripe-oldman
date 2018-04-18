<?php

namespace Symbiote\Cloudflare;

use Injector;
use DataExtension;

class SiteTreeExtension extends DataExtension
{
    public function onAfterPublish()
    {
        Injector::inst()->get(Cloudflare::class)->purgePage($this->owner);
    }

    public function onAfterUnpublish()
    {
        Injector::inst()->get(Cloudflare::class)->purgePage($this->owner);
    }
}
