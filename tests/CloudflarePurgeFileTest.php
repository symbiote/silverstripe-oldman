<?php

namespace Symbiote\Cloudflare\Tests;

use ReflectionObject;
use SiteTree;
use Injector;
use Requirements;
use Config;
use FunctionalTest;
use Symbiote\Cloudflare\Cloudflare;
use Symbiote\Cloudflare\Filesystem;

class CloudflarePurgeFileTest extends FunctionalTest
{
    /**
     * The assets used by the tests
     */
    const ASSETS_DIR = 'oldman/tests/assets';

    /**
     * This is used to determine if the 'framework' folder was scanned
     * for CSS/JS files.
     */
    const FRAMEWORK_CSS_FILE = 'framework/css/Security_login.css';

    protected static $disable_themes = true;

    /**
     * This tests if we get the correct files from a project when
     * purging CSS and JS.
     *
     * This means that CSS/JS files within "framework", "vendor" and other
     * folders should be ignored.
     *
     */
    public function testPurgeCSSAndJS()
    {
        // Generate combined files
        Requirements::combine_files(
            'combined.min.css', array(
            self::ASSETS_DIR.'/test_combined_css_a.css',
            self::ASSETS_DIR.'/test_combined_css_b.css',
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
        $expectedFiles = array(
            // Make sure we purge the _combinedfile
            'assets/_combinedfiles/combined.min.css',
            'oldman/tests/assets/test_combined_css_a.css',
            'oldman/tests/assets/test_combined_css_b.css',
        );
        // Search for matches
        $matchCount = 0;
        foreach ($files as $file) {
            foreach ($expectedFiles as $expectedFile) {
                if (strpos($file, $expectedFile) !== FALSE) {
                    $matchCount++;
                    break;
                }
            }
        }
        $this->assertEquals(
            count($expectedFiles),
            $matchCount,
            "Expected file list:\n".print_r($expectedFiles, true)."Instead got:\n".print_r($files, true)
        );

        // If it has a file from the 'framework' module, fail this test as it should be ignored.
        $hasFramework = false;
        foreach ($files as $file) {
            $hasFramework = $hasFramework || (strpos($file, self::FRAMEWORK_CSS_FILE) !== FALSE);
        }
        $this->assertFalse($hasFramework, 'Expected to specifically not get the "framework" file: '.self::FRAMEWORK_CSS_FILE);
    }

    /**
     * Test if this can detect the CSS file in framework when the default blacklist is disabled.
     */
    public function testAllowBlacklistedDirectories() {
        Config::inst()->update(Cloudflare::FilesystemClass, 'disable_default_blacklist_absolute_pathnames', true);
        $files = $this->getFilesToPurgeByExtensions(
            array(
            'css',
            'js',
            'json',
            )
        );
        Config::inst()->update(Cloudflare::FilesystemClass, 'disable_default_blacklist_absolute_pathnames', false);

        // If it has a file from the 'framework' module, fail this test as it should be ignored.
        $hasFramework = false;
        foreach ($files as $file) {
            $hasFramework = $hasFramework || (strpos($file, self::FRAMEWORK_CSS_FILE) !== FALSE);
        }
        $this->assertTrue(
            $hasFramework,
            'Expected to get "framework" file: '.self::FRAMEWORK_CSS_FILE."\nInstead got:".print_r($files, true)
        );
    }

    /**
     * Wrapper to expose private method 'getFilesToPurgeByExtensions'
     *
     * @return array
     */
    private function getFilesToPurgeByExtensions(array $fileExtensions)
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
