<?php

namespace Symbiote\Cloudflare\Tests;

use ReflectionObject;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Control\Director;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\FunctionalTest;
use Symbiote\Cloudflare\Cloudflare;
use SilverStripe\CMS\Controllers\RootURLController;
//use Symbiote\Multisites\Model\Site;

class CloudflarePurgePageTest extends FunctionalTest
{
    protected static $disable_themes = true;

    /**
     * This test ensures that the home page
     *
     * @useDatabase
     */
    public function testPurgeHomePage()
    {
        // NOTE(Jake): 2018-04-26
        //
        // This was 'home' at the time of writing and has been for the lifecycle of
        // SilverStripe 3.X
        //
        $homeSlug = RootURLController::config()->default_homepage_link;
        Director::config()->default_base_url = 'https://localhost/';

        $record = SiteTree::create();
        $record->URLSegment = $homeSlug;
        $record->write();

        $baseUrl = Director::baseURL();
        $homePage = SiteTree::get()->filter(array('URLSegment' => $homeSlug))->first();
        $linksBeingCleared = $this->getLinksToPurgeByPage($homePage);

        $this->assertEquals(
            array(
                $baseUrl,
                $baseUrl.'/home/',
            ),
            $linksBeingCleared,
            'Expected "Cloudflare::purgePage" on a home page record to return both the base url and /home/'
        );
    }

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

    /**
     * Wrapper to expose private method 'getLinksToPurgeByPage'
     *
     * @return array
     */
    private function getLinksToPurgeByPage(SiteTree $page)
    {
        $service = Injector::inst()->get(Cloudflare::CLOUDFLARE_CLASS);
        $reflector = new ReflectionObject($service);
        $method = $reflector->getMethod('getLinksToPurgeByPage');
        $method->setAccessible(true);
        $results = $method->invoke($service, $page);
        // NOTE(Jake): 2018-04-26
        //
        // Order does not matter here, so the test will not break if
        // we assert the order.
        //
        sort($results);
        return $results;
    }
}
