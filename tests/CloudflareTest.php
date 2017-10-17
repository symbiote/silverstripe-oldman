<?php

namespace Symbiote\Cloudflare\Tests;

class CloudflareTest extends \FunctionalTest
{
    protected static $disable_themes = true;

    /**
     * Effectively a test stub.
     */
    public function testPurgePageFailure()
    {
        $page = new \SiteTree();

        $result = \Injector::inst()->get('Symbiote\Cloudflare\Cloudflare')->purgePage($page);
        // Expects `null` when not configured.
        $this->assertNull($result);
    }
}
