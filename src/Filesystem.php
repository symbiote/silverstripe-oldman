<?php

namespace Symbiote\Cloudflare;

use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Director;
use Symbiote\Cloudflare\Cloudflare;

class Filesystem
{
    /**
     * File/folder names to skip over on a per-directory basis.
     *
     * @config
     * @var    array
     */
    private static $blacklist_filenames = array(
        '.',
        '..',
        '.git',
        '.svn',
        '.hg',
        'composer.json', // ignore Composer
        'package.json', // ignore NPM/Yarn
        'node_modules', // very large folder on local dev machines
    );

    /**
     * Ban certain directories from being traversed to increase purge response time.
     *
     * @config
     * @var    array
     */
    private static $blacklist_absolute_pathnames = array(
    );

    /**
     * @config
     * @var boolean
     */
    private static $disable_default_blacklist_absolute_pathnames = false;

    /**
     * The default directories that are ignored from being traversed to increase purge response time.
     * ie. - Without blacklisting framework/cms, purging CSS/JS takes ~45 seconds.
     *     - Blacklisting framework/cms, purging CSS/JS takes ~6 seconds.
     *
     * @var array
     */
    protected $defaultBlacklistAbsolutePathnames = array(
        '%BASE_FOLDER%/framework',
        '%BASE_FOLDER%/cms',
        '%BASE_FOLDER%/assets',
        '%BASE_FOLDER%/vendor',
        '%BASE_FOLDER%/silverstripe-cache',
    );

    /**
     * Walk the directory provided recursively and get all files that match
     * the provided extensions.
     *
     * @return array
     */
    public function getFilesWithExtensionsRecursively($directory, array $extensionsToMatch)
    {
        $directory_stack = array($directory);

        $ignored_filename_list = Config::inst()->get(__CLASS__, 'blacklist_filenames');
        $ignored_pathname_list = array();
        if (!Config::inst()->get(__CLASS__, 'disable_default_blacklist_absolute_pathnames')) {
            $ignored_pathname_list = $this->defaultBlacklistAbsolutePathnames;
        }
        $custom_ignored_pathname_list = Config::inst()->get(__CLASS__, 'blacklist_absolute_pathnames');
        if ($custom_ignored_pathname_list) {
            $ignored_pathname_list = array_merge($ignored_pathname_list, $custom_ignored_pathname_list);
        }

        $base_folder = Director::baseFolder();
        $base_folder = str_replace('\\', '/', $base_folder);
        $base_url = Config::inst()->get(Cloudflare::CLOUDFLARE_CLASS, 'base_url');
        if (!$base_url) {
            $base_url = Director::absoluteURL('/');
        }
        $base_url = rtrim($base_url, '/');

        // Convert from flat arrays to lookup for speed
        $ignored_filename_lookup = array();
        if ($ignored_filename_list) {
            foreach ($ignored_filename_list as $ignored_filename) {
                $ignored_filename_lookup[$ignored_filename] = true;
            }
        }
        $ignored_pathname_lookup = array();
        if ($ignored_pathname_list) {
            foreach ($ignored_pathname_list as $ignored_pathname) {
                $ignored_pathname = str_replace(array('%BASE_FOLDER%', '\\'), array($base_folder, '/'), $ignored_pathname);
                $ignored_pathname_lookup[$ignored_pathname] = true;
            }
        }

        // Get all files
        $result_file_list = array();
        while ($directory_stack) {
            $current_directory = array_shift($directory_stack);
            $current_directory = str_replace('\\', '/', $current_directory);
            $files = scandir($current_directory);
            foreach ($files as $filename) {
                $filename = str_replace('\\', '/', $filename);
                //  Skip all files/directories with:
                //      - (Disabled) A starting '.'
                //      - (Disabled) A starting '_'
                if (isset($filename[0])
                    && isset($ignored_filename_lookup[$filename])
                ) {
                    continue;
                }

                // Ignore folder paths
                //    - %BASE_FOLDER%/vendor
                //    - %BASE_FOLDER%/assets
                if (isset($ignored_pathname_lookup[$current_directory])) {
                    continue;
                }

                $pathname = $current_directory.'/'.$filename;

                if (is_dir($pathname) === true) {
                    $directory_stack[] = $pathname;
                    continue;
                }
                $file_extension = pathinfo($pathname, PATHINFO_EXTENSION);
                if (in_array($file_extension, $extensionsToMatch)) {
                    // Two things:
                    // - Convert path to URL
                    // ie. "/shared/httpd/{project-folder}/htdocs/betterbuttons/css/betterbuttons_nested_form.css"
                    // to: "http://{project-folder}.symlocal/betterbuttons/css/betterbuttons_nested_form.css"
                    //
                    $pathname = str_replace(array($base_folder), array($base_url), $pathname);

                    $result_file_list[] = $pathname;
                }
            }
        }
        return $result_file_list;
    }
}
