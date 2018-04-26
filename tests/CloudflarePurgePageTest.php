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
use SilverStripe\Control\Controller;
//use Symbiote\Multisites\Model\Site;

class CloudflarePurgePageTest extends FunctionalTest
{
    protected static $disable_themes = true;

    public function setUp() {
        parent::setUp();
        if (!defined('SS_BASE_URL')) {
            define('SS_BASE_URL', 'https://localhost/');
        }
    }

    /**
     * This test ensures that the home page will be purged.
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

        $record = SiteTree::create();
        $record->Title = "My Site Title";
        $record->URLSegment = $homeSlug;
        $record->write();
        $record->publishSingle();

        $homePage = SiteTree::get()->filter(array('URLSegment' => $homeSlug))->first();
        $linksBeingCleared = $this->getLinksToPurgeByPage($homePage);

        $baseUrl = rtrim(SS_BASE_URL, '/');
        $this->assertEquals(
            array(
                '',
                $baseUrl,
                $baseUrl.'/',
            ),
            $linksBeingCleared,
            'Expected "Cloudflare::purgePage" on a home page record to return both the base url and /'
        );
    }

    /**
     * This test ensures that a random page will be purged as expected.
     *
     * @useDatabase
     */
    public function testPurgeRandomPage()
    {
        $record = SiteTree::create();
        $record->Title = "Cloudflare Test Page";
        $record->URLSegment = 'cloudflare-test-page';
        $record->write();
        $record->publishSingle();

        $record = SiteTree::get()->filter(array('URLSegment' => 'cloudflare-test-page'))->first();
        $linksBeingCleared = $this->getLinksToPurgeByPage($record);

        $baseUrl = rtrim(SS_BASE_URL, '/');
        $this->assertEquals(
            array(
                '',
                $baseUrl,
                $baseUrl.'/',
            ),
            $linksBeingCleared,
            'Expected "Cloudflare::purgePage" on a home page record to return both the base url and /'
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
