<?php

namespace Symbiote\Cloudflare\Tests;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\FunctionalTest;
use Symbiote\Cloudflare\Cloudflare;

class CloudflareMock extends Cloudflare {
    protected $purgePageCalled = false;

    public function purgePage(SiteTree $page)
    {
        if (!$this->config()->enabled) {
            return;
        }
        $this->purgePageCalled = true;
    }

    public function wasPurgePageCalled()
    {
        $this->purgePageCalled = true;
    }
}

class CloudflarePurgePageTest extends FunctionalTest
{
    protected static $disable_themes = true;

    /**
     * This test ensures that config files are setup correctly and that
     * the `purgePage` function gets called when a SiteTree object is published.
     */
    public function testPurgePage()
    {
        Config::inst()->update(Cloudflare::class, 'enabled', true);
        Config::inst()->update(Injector::class, Cloudflare::class, 'class', CloudflareMock::class);

        $record = SiteTree::create();
        $record->write();
        $record->publishSingle();

        $this->assertTrue(
            Injector::inst()->get(CloudflareMock::class)->wasPurgePageCalled(),
            'Expected "Cloudflare::purgePage" to be called during SiteTree::publishSingle()'
        );
    }
}
