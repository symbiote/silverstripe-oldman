<?php

namespace Symbiote\Cloudflare\Tests;

use ReflectionObject;
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

    /**
     * Effectively a test stub.
     */
    public function testPurgeCSSAndJS()
    {
        $files = $this->getFilesToPurgeByExtensions(array(
            'css',
            'js',
            'json',
        ));
        var_dump($files); exit;
    }

    /**
     * Wrapper to expose private method 'getFilesToPurgeByExtensions'\
     *
     * @return array
     */
    protected function getFilesToPurgeByExtensions(array $fileExtensions)
    {
        $service = Injector::inst()->get(Cloudflare::CloudflareClass);
        $reflector = new ReflectionObject($service);
        $method = $reflector->getMethod('getFilesToPurgeByExtensions');
        $method->setAccessible(true);
        return $method->invoke($service, $fileExtensions);
    }
}
