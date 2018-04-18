<?php

namespace Symbiote\Cloudflare\Tests;

use ReflectionObject;
use SiteTree;
use Injector;
use Requirements;
use FunctionalTest;
use Symbiote\Cloudflare\Cloudflare;

class CloudflarePurgeFileTest extends FunctionalTest
{
    const ASSETS_DIR_RELATIVE = 'oldman/tests/assets';

    protected static $disable_themes = true;

    /**
     * This tests if we get the correct files from a project when
     * purging CSS and JS.
     */
    public function testPurgeCSSAndJS()
    {
        // Generate combined files
        Requirements::combine_files(
            'combined.min.css', array(
            self::ASSETS_DIR_RELATIVE.'/test_combined_css_a.css',
            self::ASSETS_DIR_RELATIVE.'/test_combined_css_b.css',
            )
        );
        Requirements::process_combined_files();

        //
        $files = $this->getFilesToPurgeByExtensions(
            array(
            'css',
            'js',
            'json',
            )
        );
        // NOTE(Jake): 2018-04-18
        //
        // This list was literally taken from an assert below printing the files
        // in TravisCI
        //
        $expectedFiles = array(
            // Make sure we purge the _combinedfile
            'http://localhost:8000/assets/_combinedfiles/combined.min.css',
            'http://localhost:8000/oldman/tests/assets/test_combined_css_a.css',
            'http://localhost:8000/oldman/tests/assets/test_combined_css_b.css',
            'http://localhost:8000/reports/javascript/ReportAdmin.Tree.js',
            'http://localhost:8000/reports/javascript/ReportAdmin.js',
            'http://localhost:8000/themes/simple/css/editor.css',
            'http://localhost:8000/themes/simple/css/form.css',
            'http://localhost:8000/themes/simple/css/layout.css',
            'http://localhost:8000/themes/simple/css/reset.css',
            'http://localhost:8000/themes/simple/css/typography.css',
            'http://localhost:8000/themes/simple/javascript/script.js',
        );
        $this->assertEquals($files, $expectedFiles, "Expected file list:\n".print_r($files, true)."Instead got:\n".print_r($expectedFiles, true));
    }

    /**
     * Wrapper to expose private method 'getFilesToPurgeByExtensions'
     *
     * @return array
     */
    protected function getFilesToPurgeByExtensions(array $fileExtensions)
    {
        $service = Injector::inst()->get(Cloudflare::CloudflareClass);
        $reflector = new ReflectionObject($service);
        $method = $reflector->getMethod('getFilesToPurgeByExtensions');
        $method->setAccessible(true);
        // NOTE(Jake): 2018-04-18
        //
        // We skip "File::get()" calls with the $skipDatabaseRecords parameter.
        // This is to make executing tests faster.
        //
        $skipDatabaseRecords = true;
        $results = $method->invoke($service, $fileExtensions, $skipDatabaseRecords);
        // NOTE(Jake): 2018-04-18
        //
        // Searching through a directory recursively will have files unordered.
        // We sort in tests so that datasets are more predictable.
        //
        sort($results);
        return $results;
    }
}
