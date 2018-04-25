<?php

namespace Symbiote\Cloudflare;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataExtension;

class SiteTreeExtension extends DataExtension
{
    public function onAfterPublish()
    {
        Injector::inst()->get(Cloudflare::CLOUDFLARE_CLASS)->purgePage($this->owner);
    }

    public function onAfterUnpublish()
    {
        Injector::inst()->get(Cloudflare::CLOUDFLARE_CLASS)->purgePage($this->owner);
    }
}
