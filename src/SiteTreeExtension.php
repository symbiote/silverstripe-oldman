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
            $urls = $result->getSuccesses();
            $errors = $result->getErrors();
            $response = Controller::curr()->getResponse();
            $response->addHeader('Oldman-Cloudflare-Cleared-Links', implode(",\n", $urls));
            if ($errors) {
                $response->addHeader('Oldman-Cloudflare-Errors', implode(",\n", $errors));
            }
        }
    }

    public function onAfterUnpublish()
    {
        Injector::inst()->get(Cloudflare::CLOUDFLARE_CLASS)->purgePage($this->owner);
    }
}
