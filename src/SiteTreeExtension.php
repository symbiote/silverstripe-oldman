<?php

namespace Symbiote\Cloudflare;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Control\Controller;

class SiteTreeExtension extends DataExtension
{
    public function onAfterPublish()
    {
        $result = Injector::inst()->get(Cloudflare::CLOUDFLARE_CLASS)->purgePage($this->owner);
        if (Controller::has_curr()) {
            foreach ($result->getSuccesses() as $success) {
                var_dump($success); exit;
            }
            $response = Controller::curr()->getResponse();
            $response->addHeader('Oldman-Cloudflare-Cleared-Links', $time);
        }
    }

    public function onAfterUnpublish()
    {
        Injector::inst()->get(Cloudflare::CLOUDFLARE_CLASS)->purgePage($this->owner);
    }
}
