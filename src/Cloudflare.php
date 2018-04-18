<?php

namespace Symbiote\Cloudflare;

use Object;
use Controller;
use Director;
use File;
use Injector;
use SiteTree;
use Site;
use Requirements;
use Cloudflare\Api;
use Cloudflare\Zone\Cache;

class Cloudflare extends Object
{
    /**
     * Cloudflare can only purge 500 files per request.
     */
    const MAX_PURGE_FILES_PER_REQUEST = 500;

    /**
     * String representation of this class.
     * NOTE: Using this as PHP 5.4 does not support `Cloudflare::class`
     *
     * @var string
     */
    const CloudflareClass = 'Symbiote\Cloudflare\Cloudflare';

    /**
     * String representation of the "Filesystem" class.
     * NOTE: Using this as PHP 5.4 does not support `Filesystem::class`
     *
     * @var string
     */
    const FilesystemClass = 'Symbiote\Cloudflare\Filesystem';

    /**
     * String representation of a Multisite "Site" DataObject class.
     * NOTE: Using this as PHP 5.4 does not support `Site::class`
     *
     * @var string
     */
    const SiteClass = 'Site';

    /**
     * @var boolean
     * @config
     */
    private static $enabled = false;

    /**
     * @var string
     * @config
     */
    private static $email = '';

    /**
     * Authentication Key
     *
     * eg. 24ca61e15fb2aa62a31212a90f2674f3451f8
     *
     * @var    string
     * @config
     */
    private static $auth_key = '';

    /**
     * Zone ID
     *
     * eg. 73a40b2c0c10f468cb658f67b9d46fff
     *
     * @var    string
     * @config
     */
    private static $zone_id = '';

    /**
     * This is used as the base url when clearing the cache for pages, CSS and JavaScript.
     * Fallback is Director::absoluteURL().
     *
     * eg. https://silverstripe.org/
     *
     * TODO(Jake): Perhaps change this to a array/list for Multisite support?
     *
     * @var    string
     * @config
     */
    private static $base_url = '';

    /**
     * Files with these extensions to purge when clearing images.
     * The other file extensions are read from File::app_categories['image'].
     *
     * @var array
     */
    private static $image_file_extensions = array(
        'svg',
        'webp',
    );

    /**
     * @var \Cloudflare\Api
     */
    protected $client;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function __construct()
    {
        parent::__construct();
        $this->filesystem = Injector::inst()->get(self::FilesystemClass);
        if ($this->config()->enabled) {
            $this->client = new Api($this->config()->email, $this->config()->auth_key);
        }
    }

    /**
     * @return CloudflareResult|null
     */
    public function purgePage(SiteTree $page)
    {
        if (!$this->client) {
            return null;
        }
        $files = array();

        // Use alternate base url if defined for cache clearing
        $baseURL = $this->config()->base_url;
        $pageLink = '';
        if ($baseURL) {
            $pageLink = Controller::join_links($baseURL, $page->Link());
        } else {
            $pageLink = $page->AbsoluteLink();
        }
        $files[] = $pageLink;

        // If /home/ for HomePage, also add "/" to be cleared.
        if ($this->isHomePage($page)) {
            $files[] = substr($pageLink, 0, (strrpos($pageLink, '/home')));
        }

        $cache = new Cache($this->client);
        $response = $cache->purge_files($this->getZoneIdentifier(), $files);
        $result = new CloudflareResult($files, $response->errors);
        return $result;
    }

    /**
     * @return CloudflareResult|null
     */
    public function purgeAll()
    {
        if (!$this->client) {
            return null;
        }
        $cache = new Cache($this->client);
        $response = $cache->purge($this->getZoneIdentifier(), true);

        $result = new CloudflareResult(array(), $response->errors);
        return $result;
    }

    /**
     * @return CloudflareResult|null
     */
    public function purgeImages()
    {
        $appCategories = File::config()->app_categories;
        if (!isset($appCategories['image'])) {
            user_error('Missing "image" category on File::app_categories.', E_USER_WARNING);
            return null;
        }
        $fileExtensions = $appCategories['image'];
        $additionalFileExtensions = $this->config()->image_file_extensions;
        if ($additionalFileExtensions) {
            $fileExtensions = array_merge($fileExtensions, $additionalFileExtensions);
        }
        $fileExtensions = array_unique($fileExtensions);
        return $this->purgeFilesByExtensions($fileExtensions);
    }

    /**
     * @return CloudflareResult|null
     */
    public function purgeCSSAndJavascript()
    {
        return $this->purgeFilesByExtensions(
            array(
                'css',
                'js',
                'json',
            )
        );
    }

    /**
     * Purge list of URL strings
     *
     * @return CloudflareResult
     */
    public function purgeURLs(array $absoluteOrRelativeURLList)
    {
        // Get base URL (for conversion of relative URL to absolute URL)
        $baseURL = $this->config()->base_url;
        if (!$baseURL) {
            $baseURL = Director::absoluteBaseURL();
        }

        // Process list of relative/absolute URLs
        $urlsToPurge = array();
        foreach ($absoluteOrRelativeURLList as $absoluteOrRelativeURL) {
            $isAbsoluteURL = strpos($absoluteOrRelativeURL, 'http://') !== false ||
                            strpos($absoluteOrRelativeURL, 'https://') !== false;

            // Convert to absolute URL
            if (!$isAbsoluteURL) {
                $urlsToPurge[] = Controller::join_links($baseURL, $absoluteOrRelativeURL);
                continue;
            }
            $urlsToPurge[] = $absoluteOrRelativeURL;
        }

        $cache = new Cache($this->client);
        $response = $cache->purge_files($this->getZoneIdentifier(), $urlsToPurge);

        $result = new CloudflareResult($urlsToPurge, $response->errors);
        return $result;
    }

    /**
     * @return string
     */
    public function getZoneIdentifier()
    {
        return $this->config()->zone_id;
    }

    /**
     * @return CloudflareResult|null
     */
    protected function purgeFilesByExtensions(array $fileExtensions)
    {
        if (!$this->client) {
            return null;
        }
        $files = $this->getFilesToPurgeByExtensions($fileExtensions, false);

        // Purge files
        $cache = new Cache($this->client);
        $zoneIdentifier = $this->getZoneIdentifier();
        $errors = array();
        foreach (array_chunk($files, self::MAX_PURGE_FILES_PER_REQUEST) as $filesChunk) {
            $response = $cache->purge_files($zoneIdentifier, $filesChunk);
            if (!$response->success) {
                $errors = array_merge($errors, $response->errors);
            }
        }

        //
        $result = new CloudflareResult($files, $errors);
        return $result;
    }

    /**
     * Check if page is the home page.
     * Supports Multisites. (ie. "Site" record exists at top of tree)
     *
     * @return boolean
     */
    protected function isHomePage(SiteTree $page)
    {
        $parent = $page->Parent();
        return $page->URLSegment === 'home' && ((class_exists(self::SiteClass) && $parent instanceof Site) || !$parent->exists());
    }

    /**
     * @return array
     */
    private function getFilesToPurgeByExtensions(array $fileExtensions, $ignoreDatabaseRecords)
    {
        // Scan files in the project directory to purge
        $folderList = array(
            // Get all files built by `Requirements` system (*.css, *.js)
            Director::baseFolder().'/'.Requirements::backend()->getCombinedFilesFolder(),
            // Get all module / theme files
            Director::baseFolder()
        );
        $files = array();
        foreach ($folderList as $folder) {
            $files = array_merge($files, $this->filesystem->getFilesWithExtensionsRecursively($folder, $fileExtensions));
        }

        // Get all files in database and purge (not using local scan for /assets/ so we can support remotely hosted files in S3/etc)
        if ($ignoreDatabaseRecords) {
            $fileExtensionsPrefixedWithDot = array();
            foreach ($fileExtensions as $fileExtension) {
                $fileExtensionsPrefixedWithDot[] = '.'.$fileExtension;
            }
            $fileRecordList = File::get()->filter(
                array(
                'Filename:EndsWith' => $fileExtensionsPrefixedWithDot
                )
            );
            $files = array_merge($files, $fileRecordList->map('ID', 'Link')->toArray());
        }
        return $files;
    }
}
