<?php

namespace Symbiote\Cloudflare;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Control\Controller;
use Symbiote\Cloudflare\CloudflareResult;

class SiteTreeExtension extends DataExtension
{
    public function onAfterPublish()
    {
        $cloudflareResult = Injector::inst()->get(Cloudflare::CLOUDFLARE_CLASS)->purgePage($this->owner);
        $this->addInformationToHeader($cloudflareResult);
    }

    public function onAfterUnpublish()
    {
        Injector::inst()->get(Cloudflare::CLOUDFLARE_CLASS)->purgePage($this->owner);
        $this->addInformationToHeader($cloudflareResult);
    }

    private function addInformationToHeader(CloudflareResult $cloudflareResult) {
        if (!Controller::has_curr()) {
            return false;
        }
        if (!$cloudflareResult) {
            return false;
        }
        $result = false;
        $urls = $cloudflareResult->getSuccesses();
        $errors = $cloudflareResult->getErrors();
        $response = Controller::curr()->getResponse();
        if ($urls) {
            $response->addHeader('Oldman-Cloudflare-Cleared-Links', implode(",\n", $urls));
            $result = true;
        }
        if ($errors) {
            $response->addHeader('Oldman-Cloudflare-Errors', implode(",\n", $errors));
            $result = true;
        }
        return $result;
    }
}
