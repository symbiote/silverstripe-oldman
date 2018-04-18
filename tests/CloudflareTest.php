<?php

namespace Symbiote\Cloudflare\Tests;

use SiteTree;
use Injector;
use Symbiote\Cloudflare\Cloudflare;

class CloudflareTest extends \FunctionalTest
{
    protected static $disable_themes = true;

    /**
     * Effectively a test stub.
     */
    public function testPurgePageFailure()
    {
        $page = new SiteTree();

        $result = Injector::inst()->get(Cloudflare::CloudflareClass)->purgePage($page);
        // Expects `null` when not configured.
        $this->assertNull($result);
    }
}
