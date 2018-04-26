<?php

namespace Symbiote\Cloudflare\Tests;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\FunctionalTest;
use Symbiote\Cloudflare\Cloudflare;

class CloudflarePurgePageTest extends FunctionalTest
{
    protected static $disable_themes = true;

    /**
     * This test ensures that config files are setup correctly and that
     * the `purgePage` function gets called when a SiteTree object is published.
     *
     * @useDatabase
     */
    public function testPurgePage()
    {
        Config::inst()->update(Cloudflare::class, 'enabled', true);

        $wasPurgePageCalled = false;
        $record = SiteTree::create();
        $record->write();
        try {
            $record->publishSingle();
        } catch (\Cloudflare\Exception\AuthenticationException $e) {
            // NOTE(Jake): 2018-04-26
            //
            // This is expected behaviour. Since we're running `purgePage` with Cloudflare
            // enabled but with no zone ID / auth key, it's getting an error.
            //
            // This at least proves that CloudFlare code was called however.
            //
            $wasPurgePageCalled = true;
        }

        $this->assertTrue(
            $wasPurgePageCalled,
            'Expected "Cloudflare::purgePage" to be called during SiteTree::publishSingle()'
        );
    }
}
